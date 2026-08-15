<?php
/**
 * F9-5: Performance Budget Test
 * Checks if assets exceed size budgets
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "📊 Performance Budget Test\n";
echo "===========================\n\n";

$budgets = [
    'css/wp-amsawal-style-h5p.css' => 50000, // 50KB
    'js/pure-js-script.js' => 100000, // 100KB
];

$passed = 0;
$failed = 0;

foreach ($budgets as $file => $budget) {
    $path = dirname(__DIR__) . '/' . $file;
    
    if (!file_exists($path)) {
        echo "❌ $file not found\n";
        $failed++;
        continue;
    }
    
    $size = filesize($path);
    $size_kb = round($size / 1024, 2);
    $budget_kb = round($budget / 1024, 2);
    
    if ($size <= $budget) {
        echo "✅ $file: {$size_kb}KB (budget: {$budget_kb}KB)\n";
        $passed++;
    } else {
        echo "❌ $file: {$size_kb}KB exceeds budget {$budget_kb}KB\n";
        $failed++;
    }
}

echo "\n===========================\n";
echo "Results: $passed passed, $failed failed\n";
echo "===========================\n";

exit($failed === 0 ? 0 : 1);
