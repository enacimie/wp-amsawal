#!/bin/bash
# F19-7: WordPress setup for CI/CD
set -e

echo "Setting up WordPress for testing..."

# Download WordPress
if [ ! -d "wordpress" ]; then
    curl -O https://wordpress.org/latest.tar.gz
    tar -xzf latest.tar.gz
    rm latest.tar.gz
fi

# Install WordPress
cd wordpress
wp core install --url="http://localhost:8080" --title="Amsawal Test" --admin_user=admin --admin_password=password123 --admin_email=admin@example.com

# Install plugin
wp plugin install ../wp-amsawal --activate

# Import test data
wp db import tests/test-data.sql

echo "WordPress setup complete!"
