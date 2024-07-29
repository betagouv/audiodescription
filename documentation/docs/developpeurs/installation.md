# Installation
```
docker compose up -d --build
docker compose exec php bash
composer install
vendor/bin/drush si
vendor/bin/drush upwd admin "Pass;1234"
vendor/bin/drush thin audiodescription_theme
