#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# 1. Scaffold a fresh Laravel project on first boot (volume is empty)
# ---------------------------------------------------------------------------
if [ ! -f /var/www/html/artisan ]; then
    echo "============================================"
    echo "  Creating Laravel project (first boot)..."
    echo "  This may take a few minutes."
    echo "============================================"

    cd /tmp
    composer create-project laravel/laravel laravel --prefer-dist --no-interaction

    cp -r /tmp/laravel/. /var/www/html/
    rm -rf /tmp/laravel

    chown -R www-data:www-data /var/www/html
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

    echo "============================================"
    echo "  Laravel project scaffolded!"
    echo "============================================"

    # Install app-specific packages that aren't in the base scaffold
    cd /var/www/html
    composer require laravel/sanctum easypost/easypost-php \
        darkaonline/l5-swagger doctrine/annotations \
        --no-interaction --no-progress
fi

cd /var/www/html

# ---------------------------------------------------------------------------
# 2. Ensure all composer dependencies are installed / up to date
#    (fast no-op when vendor is already in sync with composer.lock)
# ---------------------------------------------------------------------------
composer install --no-interaction --no-progress --optimize-autoloader

# ---------------------------------------------------------------------------
# 3. Ensure .env exists and is configured
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Patch DB credentials
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION:-mysql}|"    .env
sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-mysql}|"                      .env
sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|"                       .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-shipping_labels}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-laravel}|"        .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-secret}|"         .env

# Patch EasyPost key — replace existing line or append safely
if grep -q "^EASYPOST_API_KEY=" .env; then
    sed -i "s|^EASYPOST_API_KEY=.*|EASYPOST_API_KEY=${EASYPOST_API_KEY:-}|" .env
else
    # Guarantee the file ends with a newline before appending
    [ -n "$(tail -c1 .env)" ] && echo "" >> .env
    echo "EASYPOST_API_KEY=${EASYPOST_API_KEY:-}" >> .env
fi

# Generate app key if missing
php artisan key:generate --force

# Publish Sanctum migrations only on first time — re-publishing regenerates
# the file with a new timestamp, which makes Laravel see it as an unrun
# migration and attempt to CREATE a table that already exists.
if ! ls database/migrations/*personal_access_tokens* > /dev/null 2>&1; then
    php artisan vendor:publish \
        --provider="Laravel\Sanctum\SanctumServiceProvider" \
        --tag=sanctum-migrations \
        --quiet
fi

# ---------------------------------------------------------------------------
# 4. Wait for MySQL to accept connections
# ---------------------------------------------------------------------------
echo "Waiting for MySQL to be ready..."
until php -r "
    try {
        \$dsn = 'mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-shipping_labels}';
        new PDO(\$dsn, '${DB_USERNAME:-laravel}', '${DB_PASSWORD:-secret}');
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    echo "  MySQL not ready yet — retrying in 3 s..."
    sleep 3
done
echo "MySQL is ready."

# ---------------------------------------------------------------------------
# 5. Run migrations
# ---------------------------------------------------------------------------
php artisan migrate --force
echo "Migrations complete."

# ---------------------------------------------------------------------------
# 6. Generate OpenAPI / Swagger documentation
# ---------------------------------------------------------------------------
php artisan l5-swagger:generate
echo "Swagger docs generated."

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec apache2-foreground
