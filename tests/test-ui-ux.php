<?php
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
            echo "  ✅ PASS: $message\n";
        } else {
            $this->failed++;
            echo "   FAIL: $message\n";
        }
    }
    
    public function assertEquals($expected, $actual, $message) {
        if ($expected === $actual) {
            $this->passed++;
            echo "  ✅ PASS: $message\n";
        } else {
            $this->failed++;
            echo "  ❌ FAIL: $message (expected: $expected, got: $actual)\n";
        }
    }
    
    public function run() {
        echo "Running " . count($this->tests) . " tests...\n\n";
        
        foreach ($this->tests as $test) {
            echo "Test: {$test['name']}\n";
            try {
                ($test['callback'])($this);
            } catch (Exception $e) {
                $this->failed++;
                echo "  ❌ EXCEPTION: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
        
        echo "===================\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
        echo "===================\n";
        
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

// F9-2: Test de accesibilidad - alto contraste (reemplaza dark mode)
$test->test('High contrast coverage', function($t) {
    $modules = ['_gamification.css', '_learning-path.css', '_ai-components.css', '_breadcrumbs.css', '_leaderboard.css'];
    $hc_css = file_get_contents(dirname(__DIR__) . '/css/modules/_high-contrast.css');
    $t->assert(strpos($hc_css, '[data-theme="high-contrast"]') !== false, "High contrast base styles exist");
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

$test->test('Semantic header topbar', function($t) {
    $menu = file_get_contents(dirname(__DIR__) . '/wp-amsawal-menu.php');
    $t->assert(strpos($menu, '<header class="duo-topbar"') !== false, 'Topbar uses semantic <header>');
    $t->assert(strpos($menu, 'role="banner"') !== false, 'Topbar header has role=banner');
    $t->assert(strpos($menu, '<aside class="duo-sidebar"') !== false, 'Sidebar is semantic <aside>');
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
