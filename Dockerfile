FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

COPY . /var/www/app

RUN chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache

RUN rm -rf vendor
RUN composer install --no-interaction

# Configurações do PHP para tamanho máximo de requisições e upload de arquivos
RUN echo "post_max_size=200M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=200M" >> /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD php artisan queue:work --daemon & php-fpm
