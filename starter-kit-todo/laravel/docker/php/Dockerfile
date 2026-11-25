# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base commune : PHP en ligne de commande avec l'extension APCu (mémoire partagée)
# ---------------------------------------------------------------------------
FROM php:8.4-cli-alpine AS base
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
 && pecl install apcu \
 && docker-php-ext-enable apcu \
 && apk del $PHPIZE_DEPS linux-headers
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Développement : le code est monté depuis l'hôte, Composer est disponible
# ---------------------------------------------------------------------------
FROM base AS dev
ARG UID=1000
ARG GID=1000
RUN apk add --no-cache git unzip shadow \
 && groupmod -o -g "${GID}" www-data \
 && usermod -o -u "${UID}" -g "${GID}" www-data
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.dev.sh /usr/local/bin/entrypoint
# Supprime d'éventuels retours chariot Windows, puis rend le script exécutable
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint && chmod +x /usr/local/bin/entrypoint
ENV COMPOSER_HOME=/tmp/composer
USER www-data
EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]

# ---------------------------------------------------------------------------
# Dépendances de production : aucune dépendance de développement
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------------------------------------------------------------------------
# Image du TP : code et dépendances copiés dans l'image, utilisateur non root.
# Elle démontre une image autonome. Une vraie production utiliserait PHP-FPM derrière un reverse proxy.
# ---------------------------------------------------------------------------
FROM base AS prod
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
USER www-data
ENV PHP_CLI_SERVER_WORKERS=1
EXPOSE 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
