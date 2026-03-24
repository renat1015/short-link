#!/bin/bash

echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist

echo "Applying migrations..."
php yii migrate --interactive=0

echo "Initialization completed!"
