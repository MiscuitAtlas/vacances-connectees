# Démo en ligne : PHP 8.3 et Apache. Chaque visiteur reçoit sa propre copie d'une base SQLite.
FROM php:8.3-apache

# intl pour les dates et les prix en français, gd pour les photos.
# pdo_sqlite et mbstring sont déjà incluses dans l'image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install intl gd opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p var/demo var/log \
    && chown -R www-data:www-data var \
    && chmod +x docker/entrypoint.sh

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf

ENV PORT=10000 \
    APP_ENV=prod \
    DEMO_MODE=true \
    MAIL_TRANSPORT=log

EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
