FROM dunglas/frankenphp:1-php8.4-alpine

# Installer les extensions PHP requises pour Symfony & MariaDB
RUN install-php-extensions \
    pdo_mysql \
    intl \
    opcache \
    zip

WORKDIR /app

# Copier le projet
COPY . /app

ENV SERVER_NAME=":80"
