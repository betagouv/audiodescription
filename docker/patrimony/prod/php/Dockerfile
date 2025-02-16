FROM php:8.3 AS build
RUN apt update && apt install -y git openssl zip
WORKDIR /app/public
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
COPY ./symfony/composer.json .
COPY ./symfony/composer.lock .
COPY ./symfony/symfony.lock .
RUN composer install --no-dev --no-scripts
COPY ./symfony .


FROM dunglas/frankenphp:1.2-php8.3-bookworm
WORKDIR /app/public

RUN apt update && apt install -y \
    git openssl

# add additional extensions here:
RUN install-php-extensions \
    opcache \
    pdo_pgsql \
    gd \
    intl \
    zip \
    pcov

COPY --from=build /app/public .

COPY ./docker/prod/caddy/Caddyfile /etc/caddy/Caddyfile
COPY ./docker/prod/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app/public

ENV APP_ENV=prod
ENV DATABASE_URL="postgresql://patrimony:patrimony@postgres:5432/patrimony?serverVersion=16&charset=utf8"
RUN rm .env && touch .env

RUN chmod +x /app/public/startup.sh
CMD ["/app/public/startup.sh"]