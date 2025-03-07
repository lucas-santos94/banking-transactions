#!/bin/bash

set -e

echo "Corrigindo permissões..."
chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache
chmod -R 775 /var/www/app/storage /var/www/app/bootstrap/cache

echo "Executando composer install..."
composer install --no-interaction --optimize-autoloader

echo "Aguardando PostgreSQL estar pronto..."
/wait-for-it.sh postgres:5432 --timeout=30 --strict -- echo "PostgreSQL está pronto!"

echo "Executando migrations..."
php artisan migrate --force

echo "Iniciando PHP-FPM e Workers..."
php artisan queue:work --daemon & php-fpm