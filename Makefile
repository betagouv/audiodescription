.DEFAULT_GOAL := help
##help: List available tasks on this project
help:
	@echo ""
	@echo "These are the available commands"
	@echo ""
	@grep -E '\#\#[a-zA-Z\.\-]+:.*$$' $(MAKEFILE_LIST) \
		| tr -d '##' \
		| awk 'BEGIN {FS = ": "}; {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}' \

##up: Start dockers
.PHONY:up
up:
	docker compose up -d --build --remove-orphans -t 0

##down: Down dockers
.PHONY:down
down:
	docker compose down -t 0

.PHONY: prd-up
prd-up:
	cd /opt/audiodescription
	docker compose -f compose.prod.yml up -d --build --remove-orphans -t 0

.PHONY: prd-down
prd-down:
	cd /opt/audiodescription
	docker compose -f compose.prod.yml down -t 0

.PHONY: prd-install
prd-install:
	make prd-install-patrimony
	make prd-install-drupal

.PHONY: prd-install-drupal
prd-install-drupal:
	docker compose exec drupal composer install
	docker compose exec drupal vendor/bin/drush si -y
	docker compose exec drupal vendor/bin/drush cset system.site uuid e6c1838c-d5b1-4d0c-8c20-9405ca9991f6 -y
	docker compose exec drupal vendor/bin/drush cr
	docker compose exec drupal vendor/bin/drush entity:delete shortcut
	docker compose exec drupal vendor/bin/drush cim -y || true
	docker compose exec drupal vendor/bin/drush cim -y
	docker compose exec drupal vendor/bin/drush updb -y
	docker compose exec drupal vendor/bin/drush cr
	docker compose exec drupal vendor/bin/drush locale:check
	docker compose exec drupal vendor/bin/drush locale:update
	docker compose exec drupal vendor/bin/drush cr
	docker compose exec drupal vendor/bin/drush adia
	docker compose exec drupal vendor/bin/drush adum
	docker compose exec drupal vendor/bin/drush aduhp
	docker compose exec drupal vendor/bin/drush cr

.PHONY: prd-install-patrimony
prd-install-patrimony:
	docker compose exec patrimony composer install
	docker compose exec patrimony php bin/console doctrine:database:create --if-not-exists
	docker compose exec patrimony php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec patrimony php bin/console c:c
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:canalvod-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:canalreplay-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:orangevod-csv --create-movies=true
	docker compose exec patrimony php bin/console ad:import:lacinetek-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:artetv-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:francetv-csv --create-movies=true

.PHONY:pt-import-all
pt-import-all:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:canalvod-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:canalreplay-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:orangevod-csv --create-movies=true
	docker compose exec patrimony php bin/console ad:import:lacinetek-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:artetv-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:francetv-csv --create-movies=true

.PHONY:pt-import-orange
pt-import-orange:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:orangevod-csv --create-movies=true

.PHONY:pt-import-francetv
pt-import-francetv:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:francetv-csv --create-movies=true

.PHONY:pt-import-artetv
pt-import-artetv:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:artetv-api --create-movies=true

.PHONY:pt-import-canal
pt-import-canal:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:canalvod-api --create-movies=true

.PHONY:pt-import-canal-replay
pt-import-canal-replay:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:canalreplay-api --create-movies=true

.PHONY:pt-import-lacinetek
pt-import-lacinetek:
	docker compose exec patrimony php bin/console ad:import:cnc-public
	docker compose exec patrimony php bin/console ad:import:lacinetek-api --create-movies=true

.PHONY:pt-import
pt-import:
	docker compose exec patrimony php bin/console ad:import:mycanal-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:lacinetek-api --create-movies=true
	docker compose exec patrimony php bin/console ad:import:artetv-api --create-movies=true

.PHONY:d-import
d-import:
	docker compose exec drupal vendor/bin/drush adia
	docker compose exec drupal vendor/bin/drush adum
	docker compose exec drupal vendor/bin/drush aduhp
	docker compose exec drupal vendor/bin/drush cr

.PHONY:sh
sh:
	docker compose exec drupal bash

# Code quality
.PHONY: phpstan
phpstan:
	docker compose exec drupal vendor/bin/phpstan.phar

.PHONY: phpmd
phpmd:
	docker compose exec drupal vendor/bin/phpmd web/modules/custom/ web/themes/ad_theme/ ansi phpmd.xml

.PHONY: phpcs
phpcs:
	docker compose exec drupal vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/ || true
	docker compose exec drupal vendor/bin/phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/

.PHONY: quality
quality:
	make phpstan || true
	make phpmd || true
	make phpcs || true

# Drush commands
.PHONY:reset-db
reset-db:
	docker compose exec drupal vendor/bin/drush entity:delete node --bundle=movie
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=genre
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=offer
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=partner
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=nationality
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=public
	docker compose exec drupal vendor/bin/drush entity:delete taxonomy_term --bundle=director

.PHONY:drush-import
drush-import:
	docker compose exec drupal vendor/bin/drush ad:import:publics
	docker compose exec drupal vendor/bin/drush ad:import:movies CNC_CSV

.PHONY:drush-cr
drush-cr:
	docker compose exec drupal vendor/bin/drush cr

.PHONY:drush
drush:
	@docker compose exec drupal vendor/bin/drush $(filter-out $@,$(MAKECMDGOALS))
%:
	@:

.PHONY: pt-up
pt-up:
	docker compose up -d --remove-orphans

.PHONY: pt-down
pt-down:
	docker compose down

.PHONY: pt-sh
pt-sh:
	docker compose exec patrimony bash

.PHONY: pt-install
pt-install:
	docker compose exec patrimony composer install
	make tests-setup

.PHONY: pt-schema-reset
pt-schema-reset:
	docker compose exec patrimony php bin/console doctrine:database:drop --force --env=dev
	docker compose exec patrimony php bin/console doctrine:database:create --env=dev
	docker compose exec patrimony php bin/console doctrine:migrations:migrate --no-interaction --env=dev

.PHONY: pt-migrate-diff
pt-migrate-diff:
	docker compose exec patrimony php bin/console doctrine:migrations:diff

.PHONY: pt-fixtures-load
pt-fixtures-load:
	docker compose exec patrimony php bin/console doctrine:fixtures:load --env=dev --group=dev --no-interaction
	docker compose exec patrimony php bin/console import:rgaa

.PHONY: pt-migrate
pt-migrate:
	docker compose exec patrimony php bin/console doctrine:migrations:migrate

.PHONY: pt-phpstan
pt-phpstan:
	docker compose exec patrimony php vendor/bin/phpstan analyse -c phpstan.neon

.PHONY: pt-phpmd
pt-phpmd:
	docker compose exec patrimony php vendor/bin/phpmd src ansi phpmd.xml

.PHONY: pt-phpcs
pt-phpcs:
	docker compose exec patrimony vendor/bin/phpcs

.PHONY: pt-phpcbf
pt-phpcbf:
	docker compose exec patrimony php vendor/bin/phpcbf

.PHONY: pt-quality
pt-quality:
	make phpcbf || true
	make phpcs || true
	make phpstan || true
	make phpmd || true

.PHONY: pt-grumphp
pt-grumphp:
	docker compose exec patrimony php vendor/bin/grumphp run

.PHONY: pt-tests-build
pt-tests-build:
	docker compose exec patrimony php vendor/bin/codecept build

.PHONY: pt-tests
pt-tests:
	make tests-setup
	docker compose exec patrimony php vendor/bin/codecept run

.PHONY: pt-tests-coverage
pt-tests-coverage:
	make tests-setup
	docker compose exec patrimony php vendor/bin/codecept run --coverage --coverage-xml --coverage-html

.PHONY: pt-tests-unit
pt-tests-unit:
	make tests-setup
	docker compose exec patrimony php vendor/bin/codecept run Unit

.PHONY: pt-tests-api
pt-tests-api:
	make tests-setup
	docker compose exec patrimony php vendor/bin/codecept run Api

.PHONY: pt-tests-setup
pt-tests-setup:
	docker compose exec patrimony php bin/console doctrine:database:drop --force --env=test
	docker compose exec patrimony php bin/console doctrine:database:create --if-not-exists --env=test
	docker compose exec patrimony php bin/console doctrine:migrations:migrate --env=test --no-interaction
	docker compose exec patrimony php bin/console doctrine:fixtures:load --env=test --group=test --no-interaction

.PHONE: pt-tests-run
pt-tests-run:
	make tests-setup
	docker compose exec patrimony php vendor/bin/codecept run $(filter-out $@,$(MAKECMDGOALS))

.PHONY:cron-import
cron-import:
	docker compose exec -T patrimony php bin/console ad:import:canalvod-api --create-movies=true || true
	docker compose exec -T patrimony php bin/console ad:import:canalreplay-api --create-movies=true || true
	docker compose exec -T patrimony php bin/console ad:import:lacinetek-api --create-movies=true || true
	docker compose exec -T patrimony php bin/console ad:import:artetv-api --create-movies=true || true
	docker compose exec -T patrimony php bin/console ad:import:francetv-csv --create-movies=true || true
	docker compose exec -T drupal vendor/bin/drush adia || true
	docker compose exec -T drupal vendor/bin/drush adum || true
	docker compose exec -T drupal vendor/bin/drush aduhp || true
	docker compose exec -T drupal vendor/bin/drush cr