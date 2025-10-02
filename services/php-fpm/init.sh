#!/bin/bash
# Init script: installs dependencies, sets permissions, waits for DB

set -e

FLAG_FILE="/flag"
WORKDIR="/var/www/html"

if [ ! -f "$FLAG_FILE" ]; then
    echo "=== Initialization started ==="

    # --- Wait for database ---
    echo "Waiting for database..."
    until mysql -u "${MYSQL_USER}" -h "${MYSQL_HOST}" -p"${MYSQL_PASSWORD}" -e "USE ${MYSQL_DATABASE};" 2>/dev/null; do
        echo "Database not ready, retrying in 2s..."
        sleep 2
    done
    echo "Database ready!"

    # --- Composer install ---
    echo "Running composer install..."
    cd "$WORKDIR"
    composer install --no-interaction --no-ansi --optimize-autoloader --apcu-autoloader --classmap-authoritative

    # --- Permissions ---
    echo "Setting permissions..."
    chown -R www-data:www-data "$WORKDIR"
    chmod -R 775 "$WORKDIR/var" "$WORKDIR/public/uploads"

    # --- Optional: Doctrine schema update ---
    # php bin/console doctrine:schema:update --force

    # --- Flag file to avoid re-running ---
    touch "$FLAG_FILE"
    echo "Initialization complete. Flag created at $FLAG_FILE"
fi

# --- Start PHP-FPM ---
exec php-fpm
