#!/usr/bin/env bash
# EasyInvoice - Quick start (standalone Laravel app)
set -e

echo "EasyInvoice installatie"
echo "======================="

command -v php >/dev/null 2>&1 || { echo "✗ PHP 8.2+ vereist"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "✗ Composer vereist"; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "✗ Node.js + npm vereist"; exit 1; }

echo "→ Composer dependencies..."
composer install

echo "→ NPM dependencies..."
npm install

echo "→ Environment..."
[ -f .env ] || cp .env.example .env
php artisan key:generate

echo "→ Database (SQLite)..."
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --seed

echo "→ Storage link..."
php artisan storage:link

echo "→ Frontend build..."
npm run build

echo ""
echo "✓ Klaar! Start met:  php artisan serve"
echo "  Login: demo@easyinvoice.test / password"
