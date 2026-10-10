FROM php:8.3-fpm

# Instalar dependencias del sistema y librerías para PostgreSQL
RUN apt-get update && apt-get install -y \
    nginx libpq-dev libzip-dev libpng-dev libxml2-dev libonig-dev unzip curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd mbstring xml bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de producción de Composer
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Crear directorios de almacenamiento y configurar permisos
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/testing \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache

# Script de inicio
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
