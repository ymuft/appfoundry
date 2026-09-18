FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite \
    && a2enmod headers expires \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/apache2/sites-enabled/000-default.conf
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY docker/apache.conf /etc/apache2/sites-enabled/appfoundry.conf
RUN mkdir -p storage \
    && chown -R www-data:www-data storage
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=3s --retries=3 CMD curl -fsS http://127.0.0.1/health || exit 1
