<?php
/**
 * F13-10: PWA Compliance Tests
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "📱 PWA Compliance Test\n";
echo "========================\n\n";

$tests = [
    'manifest.json exists' => file_exists(dirname(__DIR__) . '/manifest.json'),
    'sw.js exists' => file_exists(dirname(__DIR__) . '/sw.js'),
    'offline.html exists' => file_exists(dirname(__DIR__) . '/offline.html'),
    'manifest is valid JSON' => json_decode(file_get_contents(dirname(__DIR__) . '/manifest.json')) !== null,
];

// Check manifest content
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/manifest.json'), true);
if ($manifest) {
    $tests['manifest has name'] = isset($manifest['name']);
    $tests['manifest has short_name'] = isset($manifest['short_name']);
    $tests['manifest has start_url'] = isset($manifest['start_url']);
    $tests['manifest has display'] = isset($manifest['display']);
    $tests['manifest has icons'] = isset($manifest['icons']) && count($manifest['icons']) > 0;
    $tests['manifest has theme_color'] = isset($manifest['theme_color']);
}

$passed = 0;
$failed = 0;

foreach ($tests as $name => $result) {
    if ($result) {
        echo "✅ $name\n";
        $passed++;
    } else {
        echo "❌ $name\n";
        $failed++;
    }
}

echo "\n========================\n";
echo "Results: $passed passed, $failed failed\n";
echo "========================\n";

exit($failed === 0 ? 0 : 1);
