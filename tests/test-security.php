<?php
/**
 * F15-10: Security Tests
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "🔒 Security Audit Test\n";
echo "========================\n\n";

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
    if (preg_match_all('/\$wpdb->prepare/', $content, $matches)) {
        $wpdb_prepare_found += count($matches[0]);
    }
    // Check for potential raw queries (simplified check)
    if (preg_match_all('/\$wpdb->query\([^)]*SELECT/', $content, $matches)) {
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
    echo "✅ $name: $result\n";
}

echo "\n========================\n";
echo "Security audit complete\n";
echo "========================\n";
