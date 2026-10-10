#!/bin/bash
set -e
umask 0000

cd /var/www/html

# Puerto asignado dinámicamente por AWS / Railway o 8080 por defecto
APP_PORT="${PORT:-8080}"

echo "=== Iniciando Simulador DIAN 2676 en puerto ${APP_PORT} ==="

# ── PHP ini ────────────────────────────────────────────────────────────────
PHP_INI_DIR=$(php --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | xargs dirname 2>/dev/null || echo "/usr/local/etc/php")
mkdir -p "${PHP_INI_DIR}/conf.d"
cat > "${PHP_INI_DIR}/conf.d/99-dian-settings.ini" << 'PHP_INI'
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 120
PHP_INI
echo "=== PHP ini configurado ==="

# ── Nginx ──────────────────────────────────────────────────────────────────
mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled /etc/nginx/conf.d
rm -f /etc/nginx/conf.d/* /etc/nginx/sites-enabled/*

cat > /etc/nginx/sites-available/default << NGINX_CONF
server {
    listen ${APP_PORT} default_server;
    listen [::]:${APP_PORT} default_server;
    root /var/www/html/public;
    index index.php index.html;

    access_log /dev/stdout;
    error_log /dev/stderr;

    client_max_body_size 25M;

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HTTP_HOST \$http_host;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX_CONF

ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
echo "=== Nginx configurado en puerto ${APP_PORT} ==="

# ── Generar .env si no existe o actualizar para producción ───────────────────
if [ ! -f .env ]; then
    echo "=== Creando .env de producción ==="
    cat > .env << EOF
APP_NAME="${APP_NAME:-Simulador DIAN 2676}"
APP_ENV=${APP_ENV:-production}
APP_KEY="${APP_KEY}"
APP_DEBUG=${APP_DEBUG:-false}
APP_URL="${APP_URL:-http://localhost:${APP_PORT}}"
APP_TIMEZONE=America/Bogota
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-dian2676_db}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-}"
DATABASE_URL="${DATABASE_URL}"

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

LOG_CHANNEL=stderr
LOG_LEVEL=error
EOF
fi

# ── Permisos de Storage y Bootstrap ────────────────────────────────────────
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# ── Optimización Laravel ────────────────────────────────────────────────────
php artisan storage:link 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# ── PHP-FPM ────────────────────────────────────────────────────────────────
echo "=== Iniciando PHP-FPM ==="
php-fpm -D
sleep 1

# ── Validar e Iniciar Nginx ─────────────────────────────────────────────────
nginx -t
echo "=== Nginx iniciando en puerto ${APP_PORT} ==="
nginx -g 'daemon off;' &
NGINX_PID=$!

# ── Migraciones y Seeders (en background para no retrasar inicio HTTP) ──────
(
    echo "=== Ejecutando migraciones de base de datos ==="
    php artisan migrate --force && echo "=== Migraciones completadas exitosamente ===" || echo "=== ERROR o aviso en migraciones ==="

    echo "=== Ejecutando Seeder DIAN (Perfiles, Ejes y Preguntas Oficiales) ==="
    php artisan db:seed --class=DianSeeder --force 2>&1 || true

    echo "=== Sistema DIAN 2676 listo y operativo ==="
) &

# ── Mantener contenedor vivo esperando proceso de Nginx ───────────────────
wait $NGINX_PID
