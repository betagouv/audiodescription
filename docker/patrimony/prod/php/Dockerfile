FROM php:8.3 AS build
RUN apt update && apt install -y git openssl zip
WORKDIR /app/public
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
COPY ./patrimony/composer.json .
COPY ./patrimony/composer.lock .
COPY ./patrimony/symfony.lock .
RUN composer install --no-dev --no-scripts
COPY ./patrimony .


FROM dunglas/frankenphp:1.4-php8.3-bookworm
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

COPY ./docker/patrimony/prod/caddy/Caddyfile /etc/caddy/Caddyfile
COPY ./docker/patrimony/prod/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app/public

ENV APP_ENV=prod
ENV DATABASE_URL="postgresql://patrimony:patrimony@postgres:5432/patrimony?serverVersion=16&charset=utf8"
RUN touch .env

RUN chmod +x /app/public/patrimony/startup.sh
CMD ["/app/public/patrimony/startup.sh"]