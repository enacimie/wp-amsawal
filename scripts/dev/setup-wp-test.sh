#!/usr/bin/env bash
# setup-wp-test.sh — Setup rápido del entorno de pruebas WP Amsawal
# Uso: bash setup-wp-test.sh

set -e

echo "🚀 Levantando entorno Docker..."
docker compose up -d

echo ""
echo "🛠️ Instalando WP-CLI en el contenedor..."
docker compose exec -T wordpress bash -c "if ! command -v wp &> /dev/null; then curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp; fi"

echo ""
echo "⏳ Esperando a que WordPress esté listo..."
until docker compose exec -T wordpress wp core is-installed --allow-root 2>/dev/null; do
  sleep 3
done
echo "✅ WordPress listo en http://localhost:8080"

echo ""
echo "📦 Instalando dependencias..."
echo "  → instalando plugins..."
docker compose exec -T wordpress wp plugin install h5p gamipress buddypress --activate --allow-root 2>/dev/null || true
echo "  → activando wp-amsawal..."
docker compose exec -T wordpress wp plugin activate wp-amsawal --allow-root 2>/dev/null || true

echo ""
echo "🔧 Aplicando parches de compatibilidad..."
echo "  → parche H5P embedType..."
docker compose exec -T wordpress php /var/www/html/wp-content/plugins/wp-amsawal/bin/patch-h5p-embedtype.php 2>/dev/null || true

echo ""
echo "🔍 Verificando instalación..."
docker compose exec -T wordpress wp plugin list --name=wp-amsawal,h5p,gamipress,buddypress --format=table --allow-root 2>/dev/null || true

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Todo listo!"
echo ""
echo "   WP Admin: http://localhost:8080/wp-admin"
echo ""
echo "   Logs:      docker compose logs -f"
echo "   Tumbar:    docker compose down"
echo "   Reiniciar: docker compose down -v && bash setup-wp-test.sh"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
