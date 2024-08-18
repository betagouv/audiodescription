.PHONY:reset-db
reset-db:
	docker compose exec php vendor/bin/drush entity:delete node --bundle=movie
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=genre
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=nationality
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=public
	docker compose exec php vendor/bin/drush entity:delete taxonomy_term --bundle=director


.PHONY:run
run:
	docker compose run --build python poetry run python transform.py