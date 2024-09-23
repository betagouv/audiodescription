.PHONY:sh
sh:
	@docker compose exec php bash

# Code quality
.PHONY: phpstan
phpstan:
	docker compose exec php vendor/bin/phpstan.phar

.PHONY: phpmd
phpmd:
	docker compose exec php vendor/bin/phpmd web/modules/custom/ web/themes/ad_theme/ ansi phpmd.xml

.PHONY: phpcs
phpcs:
	docker compose exec php vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/ || true
	docker compose exec php vendor/bin/phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/

.PHONY: quality
quality:
	make phpstan || true
	make phpmd || true
	make phpcs || true

# Drush commands
.PHONY:reset-db
reset-db:
	docker compose exec php vendor/bin/drush entity:delete node --bundle=movie
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=genre
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=nationality
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=public
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=director

.PHONY:drush-adim
drush-adim:
	docker compose exec php vendor/bin/drush adim CNC_CSV

.PHONY:drush-cr
drush-cr:
	docker compose exec php vendor/bin/drush cr

.PHONY:drush
drush:
	@docker compose exec php vendor/bin/drush $(filter-out $@,$(MAKECMDGOALS))
%:
	@: