FROM node:22-bookworm-slim AS frontend
WORKDIR /web
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build
FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libzip-dev unzip && docker-php-ext-install pdo_mysql mbstring zip && a2enmod rewrite
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY backend/ ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY --from=frontend /web/dist/ /var/www/html/public/
COPY infrastructure/apache.conf /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80

