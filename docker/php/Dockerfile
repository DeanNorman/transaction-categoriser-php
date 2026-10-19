# The application image: PHP-FPM with the one extension the app needs (pdo_mysql)
# and the Composer-installed dependencies baked in, so `docker compose up` needs
# nothing on the host but Docker. Dev dependencies (PHPUnit) are included on
# purpose: the test suites run inside this image against the running stack.
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-req=ext-pdo_mysql --ignore-platform-req=ext-pdo

FROM php:8.3-fpm-alpine
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/app
COPY --from=deps /app/vendor ./vendor
COPY composer.json ./
COPY public ./public
COPY src ./src
COPY tests ./tests
COPY phpunit.xml ./
COPY --from=deps /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-interaction
