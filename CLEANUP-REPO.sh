#!/usr/bin/env bash
# Verwijdert ALLE bekende oude/verkeerd geplaatste bestanden die de Railway-build saboteren.
# Draai dit IN DE ROOT van je gekloonde GitHub-repo (ddegraaf-max/easyinvoice).
#
# Gebruik:
#   cd <jouw-easyinvoice-repo>
#   bash CLEANUP-REPO.sh
#   git add -A && git commit -m "Verwijder oude build-rommel" && git push

echo "→ Oude/gegenereerde build-bestanden verwijderen..."

# 1. Gegenereerd build-plan met verkeerde commando's (hoofdprobleem #1)
rm -f railpack-plan.json nixpacks.toml railpack.toml

# 2. Verdwaalde DUPLICATE mappen — een eerdere kopieerfout heeft de app-structuur
#    onder config/ geplaatst, waardoor Controller.php dubbel gedeclareerd wordt (probleem #2)
rm -rf config/app
rm -rf config/Http
rm -rf config/Controllers
rm -rf app/config
rm -rf app/app

# 3. Oude Docker-bestanden
rm -f Dockerfile .dockerignore docker-entrypoint.sh
rm -rf docker/

# 4. Oude Procfile en nixpacks-cache
rm -f Procfile
rm -rf .nixpacks/

echo ""
echo "→ Controle op duplicate Controller.php (mag er maar ÉÉN zijn):"
found=$(find . -name "Controller.php" -not -path "*/vendor/*" 2>/dev/null)
echo "$found"
count=$(echo "$found" | grep -c "Controller.php")
if [ "$count" -eq 1 ]; then
  echo "  ✓ Precies één Controller.php (correct)"
else
  echo "  ✗ Er zijn er $count — verwijder de verkeerde handmatig"
fi

echo ""
echo "→ Controle op build-saboterende bestanden:"
for f in railpack-plan.json nixpacks.toml Dockerfile Procfile; do
  [ -e "$f" ] && echo "  ✗ $f bestaat nog" || echo "  ✓ $f weg"
done
[ -d config/app ] && echo "  ✗ config/app/ bestaat nog" || echo "  ✓ config/app/ weg"

echo ""
echo "Volgende stappen:"
echo "  git add -A"
echo "  git commit -m \"Verwijder oude build-rommel en duplicate Controller\""
echo "  git push"
