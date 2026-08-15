#!/bin/bash
# F19-4: Deployment script
set -e

echo "🚀 Deploying WP Amsawal..."

# Check if on correct branch
BRANCH=$(git branch --show-current)
if [ "$BRANCH" != "main" ] && [ "$BRANCH" != "genai" ]; then
    echo "❌ Must be on main or genai branch"
    exit 1
fi

# Pull latest changes
git pull origin $BRANCH

# Copy files to WordPress
echo "📦 Copying files..."
docker compose cp css/ wordpress:/var/www/html/wp-content/plugins/wp-amsawal/
docker compose cp js/ wordpress:/var/www/html/wp-content/plugins/wp-amsawal/
docker compose cp *.php wordpress:/var/www/html/wp-content/plugins/wp-amsawal/

# Flush cache
echo " Flushing cache..."
docker compose exec -T wordpress bash -c 'cd /var/www/html && php -r "define("WP_USE_THEMES", false); require "wp-load.php"; wp_cache_flush();"'

# Run tests
echo " Running tests..."
bash tests/run-tests.sh

echo "✅ Deployment complete!"
