FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    opcache

# Redis extension (via GitHub tarball — PECL has network issues in some regions)
RUN apk add --no-cache $PHPIZE_DEPS \
    && curl -fsSL https://github.com/phpredis/phpredis/archive/6.0.2.tar.gz -o /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext/redis \
    && tar -xzf /tmp/redis.tar.gz -C /usr/src/php/ext/redis --strip-components=1 \
    && docker-php-ext-install redis \
    && rm -rf /tmp/redis.tar.gz \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Create required directories and set permissions
RUN mkdir -p storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/app/reports \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

