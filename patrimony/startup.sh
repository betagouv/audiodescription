php bin/console doctrine:database:create --env=prod --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

frankenphp run --config /etc/caddy/Caddyfile --watch