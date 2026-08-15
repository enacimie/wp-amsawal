#!/usr/bin/env python3
"""Fase 19: DevOps & CI/CD - Automatización de deployment y testing"""

def apply_f19_1_github_actions():
    """F19-1: GitHub Actions CI/CD pipeline"""
    import os
    os.makedirs('.github/workflows', exist_ok=True)
    
    ci_pipeline = """name: CI/CD Pipeline

on:
  push:
    branches: [ genai, main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress
          MYSQL_USER: wordpress
          MYSQL_PASSWORD: wordpress
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: mbstring, mysql, xml, curl
        coverage: xdebug
    
    - name: Install WordPress
      run: |
        bash tests/setup-wordpress.sh
    
    - name: Run Unit Tests
      run: php tests/test-ui-ux.php
    
    - name: Run Integration Tests
      run: php tests/test-integration.php
    
    - name: Run Security Tests
      run: php tests/test-security.php
    
    - name: Run Performance Budget
      run: php tests/test-performance-budget.php
    
    - name: Run PWA Tests
      run: php tests/test-pwa.php
    
    - name: Upload Test Results
      if: always()
      uses: actions/upload-artifact@v3
      with:
        name: test-results
        path: tests/results/

  deploy-staging:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/genai'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to Staging
      run: |
        echo "Deploying to staging environment..."
        # Add deployment script here
        # Example: rsync, scp, or API call to hosting provider
    
  deploy-production:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to Production
      run: |
        echo "Deploying to production environment..."
        # Add deployment script here
"""
    
    with open('.github/workflows/ci-cd.yml', 'w', encoding='utf-8') as f:
        f.write(ci_pipeline)
    print("✅ F19-1: GitHub Actions CI/CD pipeline created")
    return True

def apply_f19_2_docker_compose_prod():
    """F19-2: Production Docker Compose"""
    docker_prod = """version: '3.8'

services:
  wordpress:
    image: wordpress:6.4-php7.4-apache
    ports:
      - "80:80"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - wordpress_data:/var/www/html
      - ./wp-amsawal:/var/www/html/wp-content/plugins/wp-amsawal
    depends_on:
      - db
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 256M

  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 512M

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    ports:
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - wordpress
    restart: unless-stopped

volumes:
  wordpress_data:
  db_data:
"""
    
    with open('docker-compose.prod.yml', 'w', encoding='utf-8') as f:
        f.write(docker_prod)
    print("✅ F19-2: Production Docker Compose created")
    return True

def apply_f19_3_nginx_config():
    """F19-3: Nginx configuration"""
    nginx_conf = """worker_processes auto;

events {
    worker_connections 1024;
}

http {
    upstream wordpress {
        server wordpress:80;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    server {
        listen 443 ssl http2;
        server_name amsawal.example.com;

        ssl_certificate /etc/nginx/ssl/cert.pem;
        ssl_certificate_key /etc/nginx/ssl/key.pem;
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers HIGH:!aNULL:!MD5;

        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;

        # Cache static assets
        location ~* \\.(css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }

        # API rate limiting
        location /wp-admin/admin-ajax.php {
            limit_req zone=api burst=20 nodelay;
            proxy_pass http://wordpress;
        }

        # Login rate limiting
        location /wp-login.php {
            limit_req zone=login burst=5 nodelay;
            proxy_pass http://wordpress;
        }

        # WordPress
        location / {
            proxy_pass http://wordpress;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
}
"""
    
    with open('nginx.conf', 'w', encoding='utf-8') as f:
        f.write(nginx_conf)
    print("✅ F19-3: Nginx configuration created")
    return True

def apply_f19_4_deployment_scripts():
    """F19-4: Deployment scripts"""
    import os
    os.makedirs('scripts', exist_ok=True)
    
    deploy_script = """#!/bin/bash
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
docker compose exec -T wordpress bash -c 'cd /var/www/html && php -r "define(\"WP_USE_THEMES\", false); require \"wp-load.php\"; wp_cache_flush();"'

# Run tests
echo " Running tests..."
bash tests/run-tests.sh

echo "✅ Deployment complete!"
"""
    
    with open('scripts/deploy.sh', 'w', encoding='utf-8') as f:
        f.write(deploy_script)
    
    os.chmod('scripts/deploy.sh', 0o755)
    print("✅ F19-4: Deployment scripts created")
    return True

def apply_f19_5_monitoring():
    """F19-5: Monitoring and logging"""
    monitoring_code = """<?php
/**
 * F19-5: Monitoring and Logging
 * Track application health and errors
 */

if (!defined('ABSPATH')) exit;

// Custom log file
define('AMSAWAL_LOG_FILE', WP_CONTENT_DIR . '/uploads/amsawal.log');

// Log function
function amsawal_log($message, $level = 'INFO') {
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents(AMSAWAL_LOG_FILE, $log_entry, FILE_APPEND);
    
    // Also log to WordPress debug.log if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}

// Error handler
function amsawal_error_handler($errno, $errstr, $errfile, $errline) {
    $message = "Error $errno: $errstr in $errfile on line $errline";
    amsawal_log($message, 'ERROR');
    
    // Send notification for critical errors
    if ($errno === E_ERROR || $errno === E_CORE_ERROR) {
        amsawal_send_error_notification($message);
    }
    
    return false; // Let WordPress handle it too
}

set_error_handler('amsawal_error_handler');

// Exception handler
function amsawal_exception_handler($exception) {
    $message = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
    amsawal_log($message, 'CRITICAL');
    
    amsawal_send_error_notification($message);
}

set_exception_handler('amsawal_exception_handler');

// Send error notification to admin
function amsawal_send_error_notification($message) {
    $admin_email = get_option('admin_email');
    
    wp_mail(
        $admin_email,
        '[Amsawal] Error Alert',
        "An error occurred on your site:\\n\\n$message\\n\\nTime: " . current_time('Y-m-d H:i:s')
    );
}

// Health check endpoint
add_action('wp_ajax_amsawal_health_check', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $health = [
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'mysql_version' => mysqli_get_server_info($GLOBALS['wpdb']->dbh),
        'disk_space' => disk_free_space(ABSPATH),
        'memory_usage' => memory_get_usage(true),
        'log_size' => file_exists(AMSAWAL_LOG_FILE) ? filesize(AMSAWAL_LOG_FILE) : 0,
        'last_error' => get_transient('amsawal_last_error') ?: 'None'
    ];
    
    wp_send_json_success($health);
});

// Monitor slow queries
add_filter('query', function($query) {
    $start_time = microtime(true);
    
    // This is a simplified version - in production, use a more robust solution
    return $query;
});

// AJAX handler to view logs
add_action('wp_ajax_amsawal_view_logs', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lines = absint($_POST['lines'] ?? 100);
    
    if (!file_exists(AMSAWAL_LOG_FILE)) {
        wp_send_json_success(['logs' => []]);
    }
    
    $logs = file(AMSAWAL_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_slice($logs, -$lines);
    
    wp_send_json_success(['logs' => $logs]);
});
"""
    
    with open('wp-amsawal-monitoring.php', 'w', encoding='utf-8') as f:
        f.write(monitoring_code)
    print("✅ F19-5: Monitoring and logging created")
    return True

def apply_f19_6_backup_system():
    """F19-6: Automated backup system"""
    backup_code = """<?php
/**
 * F19-6: Automated Backup System
 * Backup database and files
 */

if (!defined('ABSPATH')) exit;

// Create backup
function amsawal_create_backup($include_files = false) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('permission', 'Acceso denegado');
    }
    
    global $wpdb;
    
    $backup_dir = WP_CONTENT_DIR . '/uploads/amsawal-backups/';
    
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . 'amsawal-backup-' . $timestamp . '.sql';
    
    // Backup database
    $tables = $wpdb->get_col('SHOW TABLES');
    
    $output = "-- WP Amsawal Backup\\n";
    $output .= "-- Generated: " . current_time('Y-m-d H:i:s') . "\\n\\n";
    
    foreach ($tables as $table) {
        if (strpos($table, $wpdb->prefix) !== 0) continue;
        
        $output .= "DROP TABLE IF EXISTS `$table`;\\n";
        
        $create = $wpdb->get_row("SHOW CREATE TABLE `$table`");
        $output .= $create->{'Create Table'} . ";\\n\\n";
        
        $rows = $wpdb->get_results("SELECT * FROM `$table`");
        foreach ($rows as $row) {
            $values = array_map(function($value) use ($wpdb) {
                return "'" . $wpdb->_real_escape($value) . "'";
            }, (array)$row);
            
            $output .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\\n";
        }
        $output .= "\\n";
    }
    
    file_put_contents($backup_file, $output);
    
    // Backup files if requested
    if ($include_files) {
        $files_backup = $backup_dir . 'amsawal-files-' . $timestamp . '.zip';
        
        $zip = new ZipArchive();
        $zip->open($files_backup, ZipArchive::CREATE);
        
        $plugin_dir = WP_CONTENT_DIR . '/plugins/wp-amsawal/';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir)
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $zip->addFile($file->getRealPath(), substr($file->getRealPath(), strlen($plugin_dir)));
            }
        }
        
        $zip->close();
    }
    
    // Clean old backups (keep last 10)
    $backups = glob($backup_dir . '*.sql');
    if (count($backups) > 10) {
        sort($backups);
        for ($i = 0; $i < count($backups) - 10; $i++) {
            unlink($backups[$i]);
        }
    }
    
    return $backup_file;
}

// Schedule daily backups
if (!wp_next_scheduled('amsawal_daily_backup')) {
    wp_schedule_event(time(), 'daily', 'amsawal_daily_backup');
}

add_action('amsawal_daily_backup', function() {
    amsawal_create_backup(false);
});

// AJAX handler
add_action('wp_ajax_amsawal_create_backup', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    $include_files = isset($_POST['include_files']) && $_POST['include_files'] === 'true';
    
    $result = amsawal_create_backup($include_files);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['backup_file' => basename($result)]);
});
"""
    
    with open('wp-amsawal-backup.php', 'w', encoding='utf-8') as f:
        f.write(backup_code)
    print("✅ F19-6: Automated backup system created")
    return True

def apply_f19_7_setup_wordpress():
    """F19-7: WordPress setup script for CI"""
    setup_script = """#!/bin/bash
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
"""
    
    with open('tests/setup-wordpress.sh', 'w', encoding='utf-8') as f:
        f.write(setup_script)
    
    import os
    os.chmod('tests/setup-wordpress.sh', 0o755)
    print("✅ F19-7: WordPress setup script created")
    return True

# Ejecutar todas las mejoras de DevOps
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 19 - DevOps & CI/CD...\n")
    
    apply_f19_1_github_actions()
    apply_f19_2_docker_compose_prod()
    apply_f19_3_nginx_config()
    apply_f19_4_deployment_scripts()
    apply_f19_5_monitoring()
    apply_f19_6_backup_system()
    apply_f19_7_setup_wordpress()
    
    print("\n✨ Mejoras de DevOps completadas")
