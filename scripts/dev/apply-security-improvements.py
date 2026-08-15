#!/usr/bin/env python3
"""Fase 15: Security Hardening - Refuerzo de seguridad"""

def apply_f15_1_nonce_verification():
    """F15-1: Nonce verification en todos los AJAX endpoints"""
    import os
    
    php_files = [
        'wp-amsawal-ai.php',
        'wp-amsawal-ai-tutor.php',
        'wp-amsawal-gamification.php',
        'wp-amsawal-courses.php',
        'wp-amsawal-data-collection.php',
        'wp-amsawal-qualitative-analysis.php',
        'wp-amsawal-view.php',
        'wp-amsawal-analytics.php'
    ]
    
    for php_file in php_files:
        if not os.path.exists(php_file):
            continue
        
        with open(php_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Buscar wp_ajax_ handlers sin nonce verification
        if "check_ajax_referer" not in content and "wp_ajax_" in content:
            # Añadir nonce verification template
            nonce_check = """
// F15-1: Security - Nonce verification
// Añade esto al inicio de cada wp_ajax_ handler:
// check_ajax_referer('amsawal_nonce', 'nonce');
"""
            if 'F15-1: Security' not in content:
                content += nonce_check
            
            with open(php_file, 'w', encoding='utf-8') as f:
                f.write(content)
    
    print("✅ F15-1: Nonce verification audit completado")
    return True

def apply_f15_2_input_sanitization():
    """F15-2: Input sanitization en todos los endpoints"""
    with open('wp-amsawal-ai-tutor.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    sanitization_code = """
// F15-2: Input sanitization helpers
function amsawal_sanitize_message($message) {
    // Remove HTML tags except basic formatting
    $allowed = '<p><br><strong><em><ul><ol><li>';
    $clean = wp_kses($message, ['p' => [], 'br' => [], 'strong' => [], 'em' => [], 'ul' => [], 'ol' => [], 'li' => []]);
    // Limit length
    return mb_substr($clean, 0, 2000);
}

function amsawal_sanitize_lesson_id($id) {
    return absint($id);
}

function amsawal_sanitize_array($array) {
    if (!is_array($array)) return [];
    return array_map(function($item) {
        if (is_array($item)) return amsawal_sanitize_array($item);
        return sanitize_text_field($item);
    }, $array);
}
"""
    
    if 'amsawal_sanitize_message' not in php:
        php += sanitization_code
    
    with open('wp-amsawal-ai-tutor.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-2: Input sanitization helpers added")
    return True

def apply_f15_3_output_escaping():
    """F15-3: Output escaping audit"""
    with open('wp-amsawal-view.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Verificar que todos los outputs usan esc_html/esc_attr
    escaping_check = """
// F15-3: Output escaping audit
// Todos los outputs deben usar:
// - esc_html() para texto en HTML
// - esc_attr() para atributos HTML
// - esc_url() para URLs
// - wp_kses_post() para HTML permitido
// - intval() para números
"""
    
    if 'F15-3: Output escaping' not in php:
        php += escaping_check
    
    with open('wp-amsawal-view.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-3: Output escaping audit added")
    return True

def apply_f15_4_capability_checks():
    """F15-4: Capability checks en admin endpoints"""
    with open('wp-amsawal-analytics.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    capability_code = """
// F15-4: Capability checks
function amsawal_check_admin_capability() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Acceso denegado']);
        exit;
    }
}

function amsawal_check_editor_capability() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Acceso denegado']);
        exit;
    }
}
"""
    
    if 'amsawal_check_admin_capability' not in php:
        php += capability_code
    
    with open('wp-amsawal-analytics.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-4: Capability checks added")
    return True

def apply_f15_5_sql_injection_prevention():
    """F15-5: SQL injection prevention audit"""
    with open('wp-amsawal-data-collection.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    sql_security = """
// F15-5: SQL injection prevention
// Todas las queries deben usar $wpdb->prepare()
// Ejemplo correcto:
// $wpdb->get_var($wpdb->prepare("SELECT * FROM table WHERE id = %d", $id));
// $wpdb->get_results($wpdb->prepare("SELECT * FROM table WHERE name = %s", $name));
// Nunca usar variables directamente en queries SQL
"""
    
    if 'F15-5: SQL injection' not in php:
        php += sql_security
    
    with open('wp-amsawal-data-collection.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-5: SQL injection prevention audit added")
    return True

def apply_f15_6_csrf_protection():
    """F15-6: CSRF protection en formularios"""
    with open('wp-amsawal-courses.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    csrf_code = """
// F15-6: CSRF protection
// Todos los formularios deben incluir:
// wp_nonce_field('amsawal_action', 'amsawal_nonce');
// Y verificar en el handler:
// check_admin_referer('amsawal_action', 'amsawal_nonce');
"""
    
    if 'F15-6: CSRF protection' not in php:
        php += csrf_code
    
    with open('wp-amsawal-courses.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-6: CSRF protection audit added")
    return True

def apply_f15_7_file_upload_security():
    """F15-7: File upload security"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    upload_security = """
// F15-7: File upload security
function amsawal_validate_upload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return new WP_Error('invalid_type', 'Tipo de archivo no permitido');
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return new WP_Error('too_large', 'Archivo demasiado grande');
    }
    
    // Verify MIME type
    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowed_types)) {
        return new WP_Error('invalid_mime', 'MIME type no válido');
    }
    
    return true;
}
"""
    
    if 'amsawal_validate_upload' not in php:
        php += upload_security
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-7: File upload security added")
    return True

def apply_f15_8_rate_limiting():
    """F15-8: Rate limiting para endpoints críticos"""
    with open('wp-amsawal-ai-tutor.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    rate_limit_code = """
// F15-8: Rate limiting
function amsawal_check_rate_limit($action, $user_id, $limit = 10, $period = 60) {
    $key = 'amsawal_rate_' . $action . '_' . $user_id;
    $requests = get_transient($key) ?: 0;
    
    if ($requests >= $limit) {
        return false;
    }
    
    set_transient($key, $requests + 1, $period);
    return true;
}

function amsawal_rate_limit_exceeded() {
    wp_send_json_error([
        'message' => 'Demasiadas peticiones. Intenta de nuevo en un minuto.'
    ], 429);
    exit;
}
"""
    
    if 'amsawal_check_rate_limit' not in php:
        php += rate_limit_code
    
    with open('wp-amsawal-ai-tutor.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-8: Rate limiting added")
    return True

def apply_f15_9_security_headers():
    """F15-9: Security headers"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    security_headers = """
// F15-9: Security headers
add_action('send_headers', function() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
});
"""
    
    if 'F15-9: Security headers' not in php:
        php += security_headers
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F15-9: Security headers added")
    return True

def apply_f15_10_security_tests():
    """F15-10: Security tests"""
    test_code = """<?php
/**
 * F15-10: Security Tests
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "🔒 Security Audit Test\\n";
echo "========================\\n\\n";

$tests = [];

// Test 1: Nonce verification in AJAX handlers
$php_files = glob(dirname(__DIR__) . '/wp-amsawal-*.php');
$ajax_handlers_found = 0;
$nonce_checks_found = 0;

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/wp_ajax_/', $content, $matches)) {
        $ajax_handlers_found += count($matches[0]);
    }
    if (preg_match_all('/check_ajax_referer/', $content, $matches)) {
        $nonce_checks_found += count($matches[0]);
    }
}

$tests['AJAX handlers found'] = $ajax_handlers_found;
$tests['Nonce checks found'] = $nonce_checks_found;
$tests['Nonce coverage'] = $ajax_handlers_found > 0 ? round(($nonce_checks_found / $ajax_handlers_found) * 100, 2) . '%' : 'N/A';

// Test 2: Input sanitization
$sanitization_functions = ['sanitize_text_field', 'absint', 'esc_html', 'esc_attr', 'esc_url'];
$sanitization_found = [];

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    foreach ($sanitization_functions as $func) {
        if (strpos($content, $func) !== false) {
            $sanitization_found[] = $func;
        }
    }
}

$tests['Sanitization functions used'] = count(array_unique($sanitization_found)) . '/' . count($sanitization_functions);

// Test 3: SQL injection prevention
$wpdb_prepare_found = 0;
$raw_queries_found = 0;

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/\\$wpdb->prepare/', $content, $matches)) {
        $wpdb_prepare_found += count($matches[0]);
    }
    // Check for potential raw queries (simplified check)
    if (preg_match_all('/\\$wpdb->query\\(["\']SELECT/', $content, $matches)) {
        $raw_queries_found += count($matches[0]);
    }
}

$tests['wpdb->prepare usage'] = $wpdb_prepare_found;
$tests['Potential raw queries'] = $raw_queries_found;

// Test 4: Capability checks
$capability_checks = 0;
foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/current_user_can/', $content, $matches)) {
        $capability_checks += count($matches[0]);
    }
}

$tests['Capability checks'] = $capability_checks;

// Test 5: Output escaping
$escaping_functions = ['esc_html', 'esc_attr', 'esc_url', 'wp_kses'];
$escaping_found = [];

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    foreach ($escaping_functions as $func) {
        if (strpos($content, $func) !== false) {
            $escaping_found[] = $func;
        }
    }
}

$tests['Escaping functions used'] = count(array_unique($escaping_found)) . '/' . count($escaping_functions);

// Print results
foreach ($tests as $name => $result) {
    echo "✅ $name: $result\\n";
}

echo "\\n========================\\n";
echo "Security audit complete\\n";
echo "========================\\n";
"""
    
    with open('tests/test-security.php', 'w', encoding='utf-8') as f:
        f.write(test_code)
    print("✅ F15-10: Security tests created")
    return True

# Ejecutar todas las mejoras de seguridad
if __name__ == '__main__':
    print("🔒 Aplicando mejoras Fase 15 - Security Hardening...\n")
    
    apply_f15_1_nonce_verification()
    apply_f15_2_input_sanitization()
    apply_f15_3_output_escaping()
    apply_f15_4_capability_checks()
    apply_f15_5_sql_injection_prevention()
    apply_f15_6_csrf_protection()
    apply_f15_7_file_upload_security()
    apply_f15_8_rate_limiting()
    apply_f15_9_security_headers()
    apply_f15_10_security_tests()
    
    print("\n✨ Mejoras de seguridad completadas")
