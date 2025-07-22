#!/bin/sh
# PHP-FPM Health Check Script for CoderStew Website
# Verifies that PHP-FPM is running and responding correctly

set -e

# Check if PHP-FPM is running
if ! pgrep -f php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM process not found"
    exit 1
fi

# Check if PHP-FPM is responding to requests
if ! php -r "echo 'OK';" > /dev/null 2>&1; then
    echo "ERROR: PHP is not responding"
    exit 1
fi

# Check if we can connect to the database (if configured)
if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ]; then
    if ! php -r "
        try {
            \$pdo = new PDO('mysql:host='.\$_ENV['DB_HOST'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);
            \$pdo->query('SELECT 1');
            echo 'DB_OK';
        } catch (Exception \$e) {
            echo 'DB_ERROR: ' . \$e->getMessage();
            exit(1);
        }
    " > /dev/null 2>&1; then
        echo "ERROR: Database connection failed"
        exit 1
    fi
fi

# Check Laravel application status
if [ -f "/var/www/html/artisan" ]; then
    if ! php /var/www/html/artisan --version > /dev/null 2>&1; then
        echo "ERROR: Laravel application not responding"
        exit 1
    fi
fi

echo "Health check passed"
exit 0