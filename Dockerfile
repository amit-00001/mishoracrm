# syntax=docker/dockerfile:1

##
## Stage 1: Build frontend assets (Vite)
##
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/

RUN npm run build


##
## Stage 2: Install PHP dependencies
##
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev


##
## Stage 3: PHP-FPM runtime
##
FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache \
    bash \
    curl \
    nginx \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    zip \
    unzip \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS


##
## Composer
##
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


##
## Application
##
WORKDIR /var/www/html

COPY --from=vendor /app/vendor/ vendor/
COPY . .

COPY --from=frontend /app/public/build/ public/build/


##
## Laravel directories
##
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache


##
## Nginx configuration
##
RUN mkdir -p /run/nginx

COPY nginx.conf /etc/nginx/http.d/default.conf


##
## PHP-FPM configuration
##
RUN sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's|^;clear_env = no|clear_env = no|' /usr/local/etc/php-fpm.d/www.conf


##
## PHP configuration
##
RUN { \
        echo "memory_limit=512M"; \
        echo "upload_max_filesize=50M"; \
        echo "post_max_size=50M"; \
        echo "max_execution_time=120"; \
        echo "opcache.enable=1"; \
        echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/laravel.ini


##
## Permissions
##
RUN chown -R www-data:www-data /var/www/html


##
## Render provides PORT at runtime
##
EXPOSE 8000


##
## Start Nginx + PHP-FPM
##
##CMD ["sh", "-c", "off -i \"s/__PORT__/${PORT:-8000}/g\" /etc/nginx/http.d/default.conf && php artisan migrate --force && php artisan storage:link && php-fpm -D && nginx -g 'daemon off;'"]
## CMD ["sh", "-c", "sed -i \"s/_PORT_/${PORT:-8000}/g\" /etc/nginx/http.d/default.conf; php artisan migrate --force || echo 'migrate failed'; php artisan storage:link || true; php-fpm -D && nginx -g 'daemon off;'"]
# CMD P=$(printf '%s' "${PORT:-3000}" | tr -cd '0-9'); \
#     tr -d '\r' < /etc/nginx/http.d/default.conf > /tmp/default.conf && cat /tmp/default.conf > /etc/nginx/http.d/default.conf; \
#     sed -i "s/_PORT_/${P}/g" /etc/nginx/http.d/default.conf; \
#     php artisan migrate --force || echo 'migrate failed'; \
#     php artisan storage:link || true; \
#     php-fpm -D && nginx -g 'daemon off;'

# CMD P=$(printf '%s' "${PORT:-3000}" | tr -cd '0-9'); \
#     sed -i "s/__*PORT__*/${P}/g" /etc/nginx/http.d/default.conf; \
#     php artisan migrate --force || echo 'migrate failed'; \
#     php artisan storage:link || true; \
#     php-fpm -D && nginx -g 'daemon off;'

CMD P=$(printf '%s' "${PORT:-3000}" | tr -cd '0-9'); \
    sed -i "s/__*PORT__*/${P}/g" /etc/nginx/http.d/default.conf; \
    php artisan migrate --force || echo 'migrate failed'; \
    if [ "$PORTAL_TEST_LOGIN" = "true" ]; then php artisan db:seed --class=PortalTestCustomerSeeder --force || echo 'test customer seed failed'; fi; \
    php artisan storage:link || true; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R ug+rwX storage bootstrap/cache; \
    php-fpm -D && nginx -g 'daemon off;'
