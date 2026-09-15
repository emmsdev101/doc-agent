#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ] && [ -f package.json ]; then
    echo "Node dependencies are installed on the host via npm."
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

exec "$@"
