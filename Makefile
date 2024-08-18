.PHONY:reset-db
reset-db:
	docker compose exec php vendor/bin/drush entity:delete node --bundle=movie
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=genre
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=nationality
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=public
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=director

.PHONY:adim
adim:
	docker compose exec php vendor/bin/drush adim CNC_CSV

.PHONY:cr
cr:
	docker compose exec php vendor/bin/drush cr