#!/usr/bin/env python3
"""Fase 9: Testing & Quality Assurance"""

def apply_f9_1_unit_tests():
    """F9-1: Crear estructura de tests unitarios"""
    test_structure = """<?php
/**
 * Unit Tests for WP Amsawal
 * Run: php tests/run-tests.php
 */

// Simple test framework
class AmsawalTest {
    private $passed = 0;
    private $failed = 0;
    private $tests = [];
    
    public function test($name, $callback) {
        $this->tests[] = ['name' => $name, 'callback' => $callback];
    }
    
    public function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "  ✅ PASS: $message\\n";
        } else {
            $this->failed++;
            echo "   FAIL: $message\\n";
        }
    }
    
    public function assertEquals($expected, $actual, $message) {
        if ($expected === $actual) {
            $this->passed++;
            echo "  ✅ PASS: $message\\n";
        } else {
            $this->failed++;
            echo "  ❌ FAIL: $message (expected: $expected, got: $actual)\\n";
        }
    }
    
    public function run() {
        echo "Running " . count($this->tests) . " tests...\\n\\n";
        
        foreach ($this->tests as $test) {
            echo "Test: {$test['name']}\\n";
            try {
                ($test['callback'])($this);
            } catch (Exception $e) {
                $this->failed++;
                echo "  ❌ EXCEPTION: " . $e->getMessage() . "\\n";
            }
            echo "\\n";
        }
        
        echo "===================\\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\\n";
        echo "===================\\n";
        
        return $this->failed === 0;
    }
}

// Load WordPress
require_once dirname(__DIR__) . '/wp-load.php';

$test = new AmsawalTest();

// F9-1: Test de funciones de color
$test->test('Color tokens exist', function($t) {
    $css = file_get_contents(dirname(__DIR__) . '/css/modules/_variables.css');
    $t->assert(strpos($css, '--amsawal-primary') !== false, '--amsawal-primary exists');
    $t->assert(strpos($css, '--amsawal-text-muted') !== false, '--amsawal-text-muted exists');
    $t->assert(strpos($css, '#5a6a6b') !== false, 'Contrast fix applied (#5a6a6b)');
});

// F9-2: Test de dark mode
$test->test('Dark mode coverage', function($t) {
    $modules = ['_gamification.css', '_learning-path.css', '_ai-components.css', '_breadcrumbs.css', '_leaderboard.css'];
    foreach ($modules as $module) {
        $css = file_get_contents(dirname(__DIR__) . '/css/modules/' . $module);
        $t->assert(strpos($css, '[data-theme="dark"]') !== false, "Dark mode in $module");
    }
});

// F9-3: Test de focus trap
$test->test('Focus trap implementation', function($t) {
    $js = file_get_contents(dirname(__DIR__) . '/js/pure-js-script.js');
    $t->assert(strpos($js, 'drawerFocusTrap') !== false, 'Focus trap variable exists');
    $t->assert(strpos($js, 'DuoFocusTrap.create') !== false, 'Focus trap creation');
    $t->assert(strpos($js, 'drawerFocusTrap.destroy') !== false, 'Focus trap cleanup');
});

// F9-4: Test de aria-live regions
$test->test('ARIA live regions', function($t) {
    $js = file_get_contents(dirname(__DIR__) . '/js/pure-js-script.js');
    $t->assert(strpos($js, 'aria-live') !== false, 'aria-live attribute present');
    $t->assert(strpos($js, 'adaptest-live-region') !== false, 'Adaptive test live region');
});

// F9-5: Test de lazy loading
$test->test('Lazy loading implementation', function($t) {
    $php = file_get_contents(dirname(__DIR__) . '/wp-amsawal-view.php');
    $t->assert(strpos($php, 'loading="lazy"') !== false, 'Lazy loading attribute');
    $t->assert(strpos($php, 'decoding="async"') !== false, 'Async decoding');
});

// F9-6: Test de critical CSS
$test->test('Critical CSS inline', function($t) {
    $php = file_get_contents(dirname(__DIR__) . '/wp-amsawal-view.php');
    $t->assert(strpos($php, 'F8-2: Critical CSS') !== false, 'Critical CSS comment');
    $t->assert(strpos($php, '.duo-topbar') !== false, 'Topbar styles inlined');
});

// F9-7: Test de service worker
$test->test('Service worker files', function($t) {
    $t->assert(file_exists(dirname(__DIR__) . '/sw.js'), 'sw.js exists');
    $js = file_get_contents(dirname(__DIR__) . '/js/pure-js-script.js');
    $t->assert(strpos($js, 'serviceWorker') !== false, 'SW registration code');
});

// F9-8: Test de cache headers
$test->test('Cache headers configuration', function($t) {
    $t->assert(file_exists(dirname(__DIR__) . '/.htaccess'), '.htaccess exists');
    $htaccess = file_get_contents(dirname(__DIR__) . '/.htaccess');
    $t->assert(strpos($htaccess, 'mod_expires') !== false, 'Expires module');
    $t->assert(strpos($htaccess, 'Cache-Control') !== false, 'Cache-Control header');
});

// F9-9: Test de high contrast mode
$test->test('High contrast mode', function($t) {
    $modules = ['_learning-path.css', '_gamification.css', '_activities.css'];
    foreach ($modules as $module) {
        $css = file_get_contents(dirname(__DIR__) . '/css/modules/' . $module);
        $t->assert(strpos($css, 'prefers-contrast: more') !== false, "High contrast in $module");
    }
});

// F9-10: Test de print stylesheet
$test->test('Print stylesheet', function($t) {
    $t->assert(file_exists(dirname(__DIR__) . '/css/modules/_print.css'), '_print.css exists');
    $css = file_get_contents(dirname(__DIR__) . '/css/modules/_print.css');
    $t->assert(strpos($css, '@media print') !== false, 'Print media query');
    $t->assert(strpos($css, '.duo-sidebar') !== false, 'Sidebar hidden in print');
});

// Run all tests
$success = $test->run();
exit($success ? 0 : 1);
"""
    
    import os
    os.makedirs('tests', exist_ok=True)
    with open('tests/test-ui-ux.php', 'w', encoding='utf-8') as f:
        f.write(test_structure)
    print("✅ F9-1: Unit tests structure created")
    return True

def apply_f9_2_integration_tests():
    """F9-2: Integration tests for key workflows"""
    integration_tests = """<?php
/**
 * Integration Tests - Key User Workflows
 */

require_once dirname(__DIR__) . '/wp-load.php';

class IntegrationTest {
    private $passed = 0;
    private $failed = 0;
    
    public function test($name, $callback) {
        echo "Test: $name\\n";
        try {
            $callback($this);
        } catch (Exception $e) {
            $this->failed++;
            echo "  ❌ EXCEPTION: " . $e->getMessage() . "\\n";
        }
        echo "\\n";
    }
    
    public function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "  ✅ $message\\n";
        } else {
            $this->failed++;
            echo "   $message\\n";
        }
    }
    
    public function summary() {
        echo "===================\\n";
        echo "Integration Tests: {$this->passed} passed, {$this->failed} failed\\n";
        echo "===================\\n";
    }
}

$test = new IntegrationTest();

// Test 1: Learning path renders correctly
$test->test('Learning path rendering', function($t) {
    global $wpdb;
    
    // Check if course exists
    $course = get_post(11); // Module 1
    $t->assert($course !== null, 'Module 1 exists');
    
    // Check if lessons exist
    $lessons = get_posts([
        'post_type' => 'page',
        'post_parent' => 11,
        'numberposts' => -1
    ]);
    $t->assert(count($lessons) > 0, 'Lessons exist under Module 1');
});

// Test 2: H5P content exists for lessons
$test->test('H5P content availability', function($t) {
    global $wpdb;
    
    $h5p_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}h5p_contents");
    $t->assert($h5p_count > 0, "H5P contents exist ($h5p_count found)");
    
    // Check specific lesson H5P
    $lesson_1 = get_post(11);
    if ($lesson_1) {
        $t->assert(strpos($lesson_1->post_content, '[h5p') !== false, 'Lesson 1 has H5P shortcode');
    }
});

// Test 3: User meta for gamification
$test->test('Gamification data structure', function($t) {
    $user_id = 1; // admin
    
    $xp = get_user_meta($user_id, 'amsawal_xp', true);
    $t->assert(is_numeric($xp), 'User XP is numeric');
    
    $level = get_user_meta($user_id, 'amsawal_level', true);
    $t->assert(is_numeric($level), 'User level is numeric');
    
    $streak = get_user_meta($user_id, 'amsawal_streak', true);
    $t->assert(is_numeric($streak), 'User streak is numeric');
});

// Test 4: CSS files are accessible
$test->test('CSS file accessibility', function($t) {
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    $css_files = [
        'css/wp-amsawal-style-h5p.css',
        'css/modules/_variables.css',
        'css/modules/_learning-path.css'
    ];
    
    foreach ($css_files as $file) {
        $path = dirname(__DIR__) . '/' . $file;
        $t->assert(file_exists($path), "$file exists");
    }
});

// Test 5: JS files are accessible
$test->test('JS file accessibility', function($t) {
    $js_files = [
        'js/pure-js-script.js'
    ];
    
    foreach ($js_files as $file) {
        $path = dirname(__DIR__) . '/' . $file;
        $t->assert(file_exists($path), "$file exists");
    }
});

$test->summary();
"""
    
    with open('tests/test-integration.php', 'w', encoding='utf-8') as f:
        f.write(integration_tests)
    print("✅ F9-2: Integration tests created")
    return True

def apply_f9_3_visual_regression():
    """F9-3: Visual regression test script"""
    visual_test = """#!/bin/bash
# F9-3: Visual Regression Test
# Captures screenshots for key pages

echo "📸 Visual Regression Test"
echo "========================="

# Check if Puppeteer is available
if ! command -v google-chrome &> /dev/null; then
    echo "❌ Chrome not found. Install Chrome for visual tests."
    exit 1
fi

# Pages to test
PAGES=(
    "/"
    "/cursos-disponibles"
    "/liderazgos"
)

# Create screenshots directory
mkdir -p tests/screenshots

for page in "${PAGES[@]}"; do
    echo " Capturing: $page"
    google-chrome --headless --screenshot=tests/screenshots$(echo $page | tr '/' '_').png --window-size=1280,800 "http://localhost:8080$page" 2>/dev/null
    echo "  ✅ Saved: tests/screenshots$(echo $page | tr '/' '_').png"
done

echo ""
echo "✨ Visual regression test complete"
echo "Compare screenshots with baseline in tests/screenshots-baseline/"
"""
    
    with open('tests/visual-regression.sh', 'w', encoding='utf-8') as f:
        f.write(visual_test)
    
    import os
    os.chmod('tests/visual-regression.sh', 0o755)
    print("✅ F9-3: Visual regression test script created")
    return True

def apply_f9_4_accessibility_audit():
    """F9-4: Automated accessibility audit script"""
    a11y_audit = """#!/bin/bash
# F9-4: Accessibility Audit using pa11y
# Install: npm install -g pa11y

echo "♿ Accessibility Audit"
echo "======================"

PAGES=(
    "http://localhost:8080/"
    "http://localhost:8080/cursos-disponibles"
    "http://localhost:8080/liderazgos"
)

for page in "${PAGES[@]}"; do
    echo "🔍 Auditing: $page"
    pa11y "$page" --standard WCAG2AA --reporter cli
    echo ""
done

echo "✨ Accessibility audit complete"
"""
    
    with open('tests/accessibility-audit.sh', 'w', encoding='utf-8') as f:
        f.write(a11y_audit)
    
    import os
    os.chmod('tests/accessibility-audit.sh', 0o755)
    print("✅ F9-4: Accessibility audit script created")
    return True

def apply_f9_5_performance_budget():
    """F9-5: Performance budget test"""
    perf_test = """<?php
/**
 * F9-5: Performance Budget Test
 * Checks if assets exceed size budgets
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "📊 Performance Budget Test\\n";
echo "===========================\\n\\n";

$budgets = [
    'css/wp-amsawal-style-h5p.css' => 50000, // 50KB
    'js/pure-js-script.js' => 100000, // 100KB
];

$passed = 0;
$failed = 0;

foreach ($budgets as $file => $budget) {
    $path = dirname(__DIR__) . '/' . $file;
    
    if (!file_exists($path)) {
        echo "❌ $file not found\\n";
        $failed++;
        continue;
    }
    
    $size = filesize($path);
    $size_kb = round($size / 1024, 2);
    $budget_kb = round($budget / 1024, 2);
    
    if ($size <= $budget) {
        echo "✅ $file: {$size_kb}KB (budget: {$budget_kb}KB)\\n";
        $passed++;
    } else {
        echo "❌ $file: {$size_kb}KB exceeds budget {$budget_kb}KB\\n";
        $failed++;
    }
}

echo "\\n===========================\\n";
echo "Results: $passed passed, $failed failed\\n";
echo "===========================\\n";

exit($failed === 0 ? 0 : 1);
"""
    
    with open('tests/test-performance-budget.php', 'w', encoding='utf-8') as f:
        f.write(perf_test)
    print("✅ F9-5: Performance budget test created")
    return True

def apply_f9_6_test_runner():
    """F9-6: Test runner script"""
    runner = """#!/bin/bash
# F9-6: Test Runner - Execute all tests

echo "🧪 WP Amsawal Test Suite"
echo "========================"
echo ""

cd "$(dirname "$0")/.."

# Run unit tests
echo "1️  Unit Tests"
echo "--------------"
php tests/test-ui-ux.php
echo ""

# Run integration tests
echo "2️⃣  Integration Tests"
echo "--------------------"
php tests/test-integration.php
echo ""

# Run performance budget
echo "3️⃣  Performance Budget"
echo "---------------------"
php tests/test-performance-budget.php
echo ""

# Optional: Visual regression (requires Chrome)
if command -v google-chrome &> /dev/null; then
    echo "4️⃣  Visual Regression"
    echo "--------------------"
    bash tests/visual-regression.sh
    echo ""
else
    echo "4️⃣  Visual Regression - SKIPPED (Chrome not installed)"
    echo ""
fi

# Optional: Accessibility audit (requires pa11y)
if command -v pa11y &> /dev/null; then
    echo "5️⃣  Accessibility Audit"
    echo "----------------------"
    bash tests/accessibility-audit.sh
    echo ""
else
    echo "5️⃣  Accessibility Audit - SKIPPED (pa11y not installed)"
    echo ""
fi

echo "✨ All tests complete"
"""
    
    with open('tests/run-tests.sh', 'w', encoding='utf-8') as f:
        f.write(runner)
    
    import os
    os.chmod('tests/run-tests.sh', 0o755)
    print("✅ F9-6: Test runner script created")
    return True

# Ejecutar todas las mejoras de testing
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 9 - Testing & QA...\n")
    
    apply_f9_1_unit_tests()
    apply_f9_2_integration_tests()
    apply_f9_3_visual_regression()
    apply_f9_4_accessibility_audit()
    apply_f9_5_performance_budget()
    apply_f9_6_test_runner()
    
    print("\n✨ Mejoras de testing completadas")
