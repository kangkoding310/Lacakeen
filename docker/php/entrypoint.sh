#!/bin/sh
set -e

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL..."
until PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST:-postgres}" -U "${DB_USERNAME:-ailee}" -d "${DB_DATABASE:-ailee}" -c '\q' 2>/dev/null; do
    sleep 1
done
echo "✅ PostgreSQL is ready"

# Install Composer dependencies if vendor doesn't exist or composer.lock changed
if [ ! -d "vendor" ] || [ composer.lock -nt vendor/autoload.php ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Install NPM dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "📦 Installing NPM dependencies..."
    npm install
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --seed

# Create storage link
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Clear caches
php artisan optimize:clear

echo "🚀 Lacakeen is ready!"

# Start PHP-FPM
exec php-fpm
