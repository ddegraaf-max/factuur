#!/usr/bin/env bash
# Bouwt de frontend lokaal zodat Railway de zware Node/Vite-build kan overslaan.
# Gebruik:  bash build-assets-locally.sh
# Daarna:   git add -f public/build && git commit -m "Pre-built assets" && git push
set -e

echo "→ NPM dependencies installeren..."
npm install

echo "→ Frontend bouwen (Vite)..."
npm run build

echo ""
echo "✓ Klaar. De gebouwde assets staan in public/build/"
echo ""
echo "Volgende stappen:"
echo "  git add -f public/build"
echo "  git commit -m \"Commit pre-built frontend assets\""
echo "  git push"
echo ""
echo "En zet in Railway → Variables:  RAILPACK_INSTALL_CMD=composer install --no-dev --optimize-autoloader --no-interaction"
echo "Dat laat Railway de npm-install overslaan."
