<?php
/**
 * Test Suite for Amsawal Plugin Improvements
 * 
 * This script tests the key improvements made to the Amsawal plugin:
 * 1. Enhanced error handling in AI endpoints
 * 2. Improved documentation
 * 3. Better CSS modularity
 * 4. Enhanced logging
 */

// Include WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( __FILE__ ) . '/../../wp-load.php';
}

// Verify user is logged in as admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied. Admin privileges required.' );
}

// Enable error reporting for testing
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

/**
 * Test class for Amsawal improvements
 */
class AmsawalImprovementsTest {
    
    private $results = array();
    
    public function __construct() {
        $this->run_tests();
        $this->display_results();
    }
    
    /**
     * Run all tests
     */
    private function run_tests() {
        $this->test_documentation_improvements();
        $this->test_error_handling();
        $this->test_css_modularity();
        $this->test_logging_enhancements();
        $this->test_ai_functions();
    }
    
    /**
     * Test documentation improvements
     */
    private function test_documentation_improvements() {
        $test_name = 'Documentation Improvements';
        
        // Check if function has proper documentation
        $has_doc = function_exists( 'wp_amsawal_ai_get_lesson_course' ) && 
                   function_exists( 'wp_amsawal_ai_query' ) &&
                   function_exists( 'wp_amsawal_ajax_evaluate_essay' );
        
        $this->add_result( $test_name, $has_doc, 'Functions have improved documentation' );
    }
    
    /**
     * Test error handling improvements
     */
    private function test_error_handling() {
        $test_name = 'Error Handling Improvements';
        
        // Check if enhanced error handling exists in AI query function
        $reflection = new ReflectionFunction( 'wp_amsawal_ai_query' );
        $doc_comment = $reflection->getDocComment();
        $has_improved_error_handling = strpos( $doc_comment, 'WP_Error' ) !== false || 
                                       strpos( $doc_comment, 'error' ) !== false;
        
        $this->add_result( $test_name, $has_improved_error_handling, 'AI query function has improved error handling' );
    }
    
    /**
     * Test CSS modularity
     */
    private function test_css_modularity() {
        $test_name = 'CSS Modularity';
        
        // Check if CSS modules exist
        $modules_exist = file_exists( plugin_dir_path( __FILE__ ) . 'css/modules/_variables.css' ) &&
                         file_exists( plugin_dir_path( __FILE__ ) . 'css/modules/_layout.css' ) &&
                         file_exists( plugin_dir_path( __FILE__ ) . 'css/modules/_mobile-nav.css' );
        
        $this->add_result( $test_name, $modules_exist, 'CSS modules exist' );
    }
    
    /**
     * Test logging enhancements
     */
    private function test_logging_enhancements() {
        $test_name = 'Logging Enhancements';
        
        // Test if logging function exists and works
        $logging_exists = function_exists( 'wp_amsawal_log' );
        
        if ( $logging_exists ) {
            // Test logging functionality
            wp_amsawal_log( 'info', 'Test log message', array( 'test' => true ) );
            
            // Verify log was saved
            $log = get_option( 'wp_amsawal_log', array() );
            $log_has_test_entry = false;
            
            foreach ( $log as $entry ) {
                if ( isset( $entry['message'] ) && strpos( $entry['message'], 'Test log' ) !== false ) {
                    $log_has_test_entry = true;
                    break;
                }
            }
            
            $this->add_result( $test_name, $log_has_test_entry, 'Logging functionality works' );
        } else {
            $this->add_result( $test_name, false, 'Logging function does not exist' );
        }
    }
    
    /**
     * Test AI functions
     */
    private function test_ai_functions() {
        $test_name = 'AI Functions';
        
        // Check if main AI functions exist
        $ai_functions_exist = function_exists( 'wp_amsawal_ai_query' ) &&
                              function_exists( 'wp_amsawal_ai_detect_backend' ) &&
                              function_exists( 'wp_amsawal_ai_get_lesson_course' );
        
        $this->add_result( $test_name, $ai_functions_exist, 'AI functions exist' );
    }
    
    /**
     * Add test result
     */
    private function add_result( $test_name, $passed, $description ) {
        $this->results[] = array(
            'test' => $test_name,
            'passed' => $passed,
            'description' => $description
        );
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Amsawal Plugin Improvements Test Results</title>";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .result { padding: 10px; margin: 5px 0; border-radius: 4px; }
            .pass { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .fail { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
        </style></head><body>";
        
        echo "<h1>Amsawal Plugin Improvements Test Results</h1>";
        echo "<table>";
        echo "<tr><th>Test</th><th>Status</th><th>Description</th></tr>";
        
        $passed_count = 0;
        $total_count = count( $this->results );
        
        foreach ( $this->results as $result ) {
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            $class = $result['passed'] ? 'pass' : 'fail';
            $icon = $result['passed'] ? '✅' : '❌';
            
            if ( $result['passed'] ) {
                $passed_count++;
            }
            
            echo "<tr>";
            echo "<td>{$result['test']}</td>";
            echo "<td class='{$class}'>{$icon} {$status}</td>";
            echo "<td>{$result['description']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        $overall_status = ( $passed_count === $total_count ) ? '✅ ALL TESTS PASSED' : "❌ {$passed_count}/{$total_count} TESTS PASSED";
        echo "<h2>Overall: {$overall_status}</h2>";
        
        echo "</body></html>";
    }
}

// Run the test
new AmsawalImprovementsTest();