FROM dunglas/frankenphp:1.12-php8.5-bookworm

COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer

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

WORKDIR /app/public