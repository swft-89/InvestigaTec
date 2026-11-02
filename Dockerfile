FROM php:8.3-apache

ENV PORT=10000

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libcurl4-openssl-dev unzip \
    && docker-php-ext-install curl mbstring pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    --working-dir=/var/www/html

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=21M\npost_max_size=22M\nmax_execution_time=60\n" \
        > "$PHP_INI_DIR/conf.d/app.ini"

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/perfiles /var/www/html/uploads/proyectos \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
