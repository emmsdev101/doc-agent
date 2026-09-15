FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libicu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pcntl posix zip intl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/docagent.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
