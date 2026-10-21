# Dockerfile
FROM dunglas/frankenphp:1.13.2-php8.5.12-bookworm@sha256:308b7dbf7e263a7200f17c1957c0ed1dfdcd01fd914e1b7e99eef7f92b06d4ae

COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer

RUN pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis

# Installer les dépendances nécessaires
RUN install-php-extensions \
	gd \
    mbstring \
    pdo \
    pdo_pgsql \
    zip \
    xml \
    opcache \
    intl

WORKDIR /app/public
