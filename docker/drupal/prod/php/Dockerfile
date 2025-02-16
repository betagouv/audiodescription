# Dockerfile
FROM dunglas/frankenphp:1.4-php8.3-bookworm

COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

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