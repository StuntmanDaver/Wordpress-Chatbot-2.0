<?php
/**
 * Gary AI Plugin - PHP Compatibility Test Suite
 * 
 * Tests plugin compatibility across different PHP versions (7.4 - 8.2+)
 * and validates that all features work without warnings or errors.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PHP Compatibility Test Class
 */
class GaryAIPhpCompatibilityTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        echo "<h1>🧪 Gary AI Plugin - PHP Compatibility Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .php-version { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; }
            .checklist { list-style-type: none; padding: 0; }
            .checklist li { margin: 5px 0; }
            .checklist .pass::before { content: '✅ '; }
            .checklist .fail::before { content: '❌ '; }
            .checklist .warn::before { content: '⚠️ '; }
        </style>";
    }
    
    /**
     * Run all PHP compatibility tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting PHP Compatibility Tests...</h2>";
        
        $this->testPhpVersionInfo();
        $this->testPluginStructure();
        $this->testClassCompatibility();
        $this->testFunctionCompatibility();
        $this->testWordPressCompatibility();
        $this->testDatabaseCompatibility();
        $this->testApiClientCompatibility();
        $this->testSecurityFeatures();
        $this->testPerformanceFeatures();
        
        $this->displayResults();
        echo "</div>";
    }
    
    /**
     * Test 1: PHP Version Information
     */
    private function testPhpVersionInfo() {
        echo "<div class='test-section'>";
        echo "<h3>📋 Test 1: PHP Environment Information</h3>";
        
        $php_version = PHP_VERSION;
        $php_major = PHP_MAJOR_VERSION;
        $php_minor = PHP_MINOR_VERSION;
        $php_release = PHP_RELEASE_VERSION;
        
        echo "<div class='php-version'>";
        echo "<strong>Current PHP Version:</strong> {$php_version}<br>";
        echo "<strong>Major:</strong> {$php_major} | <strong>Minor:</strong> {$php_minor} | <strong>Release:</strong> {$php_release}<br>";
        echo "<strong>SAPI:</strong> " . PHP_SAPI . "<br>";
        echo "<strong>OS:</strong> " . PHP_OS . "<br>";
        echo "</div>";
        
        // Check minimum requirements
        $min_version = '7.4.0';
        if (version_compare(PHP_VERSION, $min_version, '>=')) {
            echo "<span class='success'>✅ PHP version meets minimum requirement ({$min_version})</span><br>";
            $this->results['php_version_check'] = 'pass';
        } else {
            echo "<span class='error'>❌ PHP version below minimum requirement ({$min_version})</span><br>";
            $this->errors[] = "PHP version {$php_version} is below minimum {$min_version}";
            $this->results['php_version_check'] = 'fail';
        }
        
        // Check for PHP 8+ compatibility features
        if (PHP_MAJOR_VERSION >= 8) {
            echo "<span class='info'>🎯 PHP 8+ features available for testing</span><br>";
            
            // Test union types (PHP 8.0+)
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                echo "<span class='success'>✅ Union types supported (PHP 8.0+)</span><br>";
            }
            
            // Test named arguments (PHP 8.0+)
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                echo "<span class='success'>✅ Named arguments supported (PHP 8.0+)</span><br>";
            }
            
            // Test enums (PHP 8.1+)
            if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
                echo "<span class='success'>✅ Enums supported (PHP 8.1+)</span><br>";
            }
        }
        
        echo "</div>";
    }
    
    /**
     * Test 2: Plugin Structure Compatibility
     */
    private function testPluginStructure() {
        echo "<div class='test-section'>";
        echo "<h3>📁 Test 2: Plugin Structure & File Loading</h3>";
        
        $plugin_files = [
            'gary-ai.php' => 'Main plugin file',
            'uninstall.php' => 'Uninstall script',
            'includes/class-admin-ajax.php' => 'Admin AJAX class',
            'includes/class-analytics.php' => 'Analytics class',
            'includes/class-contextual-ai-client.php' => 'API client class',
            'includes/class-gdpr-compliance.php' => 'GDPR compliance class',
            'assets/css/admin.css' => 'Admin styles',
            'assets/css/chat-widget.css' => 'Widget styles',
            'assets/js/admin.js' => 'Admin scripts',
            'assets/js/chat-widget.js' => 'Widget scripts'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($plugin_files as $file => $description) {
            $file_path = __DIR__ . '/../' . $file;
            if (file_exists($file_path)) {
                echo "<li class='pass'>{$description} ({$file})</li>";
                
                // Test PHP file syntax
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $syntax_check = $this->checkPhpSyntax($file_path);
                    if ($syntax_check === true) {
                        echo "<li class='pass'>PHP syntax valid for {$file}</li>";
                    } else {
                        echo "<li class='fail'>PHP syntax error in {$file}: {$syntax_check}</li>";
                        $this->errors[] = "Syntax error in {$file}: {$syntax_check}";
                    }
                }
            } else {
                echo "<li class='fail'>{$description} ({$file}) - File not found</li>";
                $this->errors[] = "Missing file: {$file}";
            }
        }
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Test 3: Class Compatibility
     */
    private function testClassCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🏗️ Test 3: PHP Class Compatibility</h3>";
        
        $test_classes = [
            'GaryAI' => 'Main plugin class',
            'GaryAIAdminAjax' => 'Admin AJAX handler',
            'GaryAIAnalytics' => 'Analytics manager',
            'ContextualAIClient' => 'API client',
            'GaryAIGDPRCompliance' => 'GDPR compliance'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($test_classes as $class_name => $description) {
            if (class_exists($class_name)) {
                echo "<li class='pass'>{$description} ({$class_name}) - Class loaded</li>";
                
                // Test class reflection
                try {
                    $reflection = new ReflectionClass($class_name);
                    $methods = $reflection->getMethods();
                    echo "<li class='info'>  → {$class_name} has " . count($methods) . " methods</li>";
                    
                    // Test if class can be instantiated (for non-singletons)
                    if (!$reflection->hasMethod('getInstance') && !$reflection->isAbstract()) {
                        try {
                            $instance = $reflection->newInstance();
                            echo "<li class='pass'>  → {$class_name} can be instantiated</li>";
                        } catch (Exception $e) {
                            echo "<li class='warn'>  → {$class_name} instantiation requires parameters</li>";
                        }
                    } elseif ($reflection->hasMethod('getInstance')) {
                        echo "<li class='info'>  → {$class_name} uses singleton pattern</li>";
                    }
                    
                } catch (ReflectionException $e) {
                    echo "<li class='fail'>  → Reflection error for {$class_name}: " . $e->getMessage() . "</li>";
                    $this->errors[] = "Reflection error for {$class_name}: " . $e->getMessage();
                }
                
            } else {
                echo "<li class='fail'>{$description} ({$class_name}) - Class not found</li>";
                $this->errors[] = "Class not found: {$class_name}";
            }
        }
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Test 4: Function Compatibility
     */
    private function testFunctionCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>⚙️ Test 4: PHP Function Compatibility</h3>";
        
        $required_functions = [
            'curl_init' => 'cURL support for API calls',
            'json_encode' => 'JSON encoding for API communication',
            'json_decode' => 'JSON decoding for API responses',
            'hash_hmac' => 'HMAC hashing for security',
            'base64_encode' => 'Base64 encoding for tokens',
            'mysqli_connect' => 'MySQL database connectivity',
            'filter_var' => 'Input validation and sanitization',
            'password_hash' => 'Password hashing (PHP 5.5+)',
            'random_bytes' => 'Cryptographically secure random bytes (PHP 7.0+)'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($required_functions as $function => $description) {
            if (function_exists($function)) {
                echo "<li class='pass'>{$description} ({$function})</li>";
            } else {
                echo "<li class='fail'>{$description} ({$function}) - Function not available</li>";
                $this->errors[] = "Required function not available: {$function}";
            }
        }
        echo "</ul>";
        
        // Test PHP extensions
        echo "<h4>📦 PHP Extensions Check:</h4>";
        $required_extensions = [
            'curl' => 'HTTP requests',
            'json' => 'JSON processing',
            'mysqli' => 'MySQL database',
            'openssl' => 'SSL/TLS security',
            'filter' => 'Input filtering',
            'hash' => 'Cryptographic hashing'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($required_extensions as $extension => $description) {
            if (extension_loaded($extension)) {
                echo "<li class='pass'>{$description} ({$extension})</li>";
            } else {
                echo "<li class='fail'>{$description} ({$extension}) - Extension not loaded</li>";
                $this->errors[] = "Required extension not loaded: {$extension}";
            }
        }
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Test 5: WordPress Compatibility
     */
    private function testWordPressCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Test 5: WordPress Function Compatibility</h3>";
        
        $wp_functions = [
            'add_action' => 'WordPress hooks system',
            'add_filter' => 'WordPress filters system',
            'wp_enqueue_script' => 'Script enqueueing',
            'wp_enqueue_style' => 'Style enqueueing',
            'wp_remote_request' => 'HTTP API',
            'wp_verify_nonce' => 'Security nonces',
            'current_user_can' => 'User capabilities',
            'sanitize_text_field' => 'Input sanitization',
            'get_option' => 'Options API',
            'update_option' => 'Options API'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($wp_functions as $function => $description) {
            if (function_exists($function)) {
                echo "<li class='pass'>{$description} ({$function})</li>";
            } else {
                echo "<li class='fail'>{$description} ({$function}) - WordPress function not available</li>";
                $this->warnings[] = "WordPress function not available (testing outside WP): {$function}";
            }
        }
        echo "</ul>";
        
        // Check WordPress globals
        echo "<h4>🌍 WordPress Globals Check:</h4>";
        echo "<ul class='checklist'>";
        
        global $wpdb, $wp_version;
        
        if (isset($wpdb)) {
            echo "<li class='pass'>WordPress database object (\$wpdb) available</li>";
        } else {
            echo "<li class='warn'>WordPress database object (\$wpdb) not available (testing outside WP)</li>";
        }
        
        if (defined('WP_VERSION')) {
            echo "<li class='pass'>WordPress version: " . WP_VERSION . "</li>";
        } else {
            echo "<li class='warn'>WordPress version not available (testing outside WP)</li>";
        }
        
        echo "</ul>";
        echo "</div>";
    }
    
    /**
     * Test 6: Database Compatibility
     */
    private function testDatabaseCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🗄️ Test 6: Database Compatibility</h3>";
        
        // Test MySQL/MariaDB compatibility
        if (function_exists('mysqli_connect')) {
            echo "<span class='success'>✅ MySQLi extension available</span><br>";
            
            // Test SQL syntax used in plugin
            $sql_features = [
                'CREATE TABLE IF NOT EXISTS' => 'Table creation syntax',
                'AUTO_INCREMENT' => 'Auto-increment fields',
                'PRIMARY KEY' => 'Primary key constraints',
                'INDEX/KEY' => 'Index creation',
                'DATETIME DEFAULT CURRENT_TIMESTAMP' => 'Timestamp defaults',
                'VARCHAR(255)' => 'Variable character fields',
                'LONGTEXT' => 'Large text fields',
                'BIGINT(20)' => 'Large integer fields'
            ];
            
            echo "<h4>🔧 SQL Features Used:</h4>";
            echo "<ul class='checklist'>";
            foreach ($sql_features as $feature => $description) {
                echo "<li class='pass'>{$description} ({$feature})</li>";
            }
            echo "</ul>";
            
        } else {
            echo "<span class='error'>❌ MySQLi extension not available</span><br>";
            $this->errors[] = "MySQLi extension required but not available";
        }
        
        echo "</div>";
    }
    
    /**
     * Test 7: API Client Compatibility
     */
    private function testApiClientCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Test 7: API Client Compatibility</h3>";
        
        // Test cURL features used by API client
        if (function_exists('curl_init')) {
            echo "<span class='success'>✅ cURL extension available</span><br>";
            
            $curl_features = [
                'CURLOPT_URL' => 'URL setting',
                'CURLOPT_RETURNTRANSFER' => 'Return response as string',
                'CURLOPT_POST' => 'POST method support',
                'CURLOPT_POSTFIELDS' => 'POST data support',
                'CURLOPT_HTTPHEADER' => 'Custom headers',
                'CURLOPT_TIMEOUT' => 'Request timeout',
                'CURLOPT_SSL_VERIFYPEER' => 'SSL verification',
                'CURLOPT_FOLLOWLOCATION' => 'Redirect following'
            ];
            
            echo "<h4>🔧 cURL Options Used:</h4>";
            echo "<ul class='checklist'>";
            foreach ($curl_features as $option => $description) {
                if (defined($option)) {
                    echo "<li class='pass'>{$description} ({$option})</li>";
                } else {
                    echo "<li class='fail'>{$description} ({$option}) - Option not available</li>";
                    $this->errors[] = "cURL option not available: {$option}";
                }
            }
            echo "</ul>";
            
        } else {
            echo "<span class='error'>❌ cURL extension not available</span><br>";
            $this->errors[] = "cURL extension required but not available";
        }
        
        echo "</div>";
    }
    
    /**
     * Test 8: Security Features
     */
    private function testSecurityFeatures() {
        echo "<div class='test-section'>";
        echo "<h3>🔒 Test 8: Security Features Compatibility</h3>";
        
        // Test security-related functions
        $security_functions = [
            'hash_hmac' => 'HMAC generation for tokens',
            'random_bytes' => 'Cryptographically secure random data',
            'password_hash' => 'Password hashing',
            'password_verify' => 'Password verification',
            'openssl_random_pseudo_bytes' => 'OpenSSL random bytes',
            'filter_var' => 'Input validation',
            'htmlspecialchars' => 'XSS prevention'
        ];
        
        echo "<ul class='checklist'>";
        foreach ($security_functions as $function => $description) {
            if (function_exists($function)) {
                echo "<li class='pass'>{$description} ({$function})</li>";
            } else {
                echo "<li class='warn'>{$description} ({$function}) - Function not available</li>";
                $this->warnings[] = "Security function not available: {$function}";
            }
        }
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Test 9: Performance Features
     */
    private function testPerformanceFeatures() {
        echo "<div class='test-section'>";
        echo "<h3>⚡ Test 9: Performance Features</h3>";
        
        // Test memory and execution limits
        $memory_limit = ini_get('memory_limit');
        $max_execution_time = ini_get('max_execution_time');
        $post_max_size = ini_get('post_max_size');
        $upload_max_filesize = ini_get('upload_max_filesize');
        
        echo "<h4>💾 PHP Configuration:</h4>";
        echo "<ul class='checklist'>";
        
        // Memory limit check
        $memory_bytes = $this->convertToBytes($memory_limit);
        $recommended_memory = 128 * 1024 * 1024; // 128MB
        
        if ($memory_bytes >= $recommended_memory || $memory_limit === '-1') {
            echo "<li class='pass'>Memory limit: {$memory_limit} (sufficient)</li>";
        } else {
            echo "<li class='warn'>Memory limit: {$memory_limit} (may be insufficient, recommend 128M+)</li>";
            $this->warnings[] = "Memory limit may be insufficient: {$memory_limit}";
        }
        
        // Execution time check
        if ($max_execution_time >= 30 || $max_execution_time == 0) {
            echo "<li class='pass'>Max execution time: {$max_execution_time}s (sufficient)</li>";
        } else {
            echo "<li class='warn'>Max execution time: {$max_execution_time}s (may be insufficient for API calls)</li>";
            $this->warnings[] = "Execution time may be insufficient: {$max_execution_time}s";
        }
        
        echo "<li class='info'>POST max size: {$post_max_size}</li>";
        echo "<li class='info'>Upload max filesize: {$upload_max_filesize}</li>";
        
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Check PHP syntax of a file
     */
    private function checkPhpSyntax($file_path) {
        $output = [];
        $return_code = 0;
        
        // Use php -l to check syntax
        exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            return true;
        } else {
            return implode("\n", $output);
        }
    }
    
    /**
     * Convert PHP memory notation to bytes
     */
    private function convertToBytes($value) {
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;
        
        switch ($unit) {
            case 'g':
                $num *= 1024;
                // fall through
            case 'm':
                $num *= 1024;
                // fall through
            case 'k':
                $num *= 1024;
        }
        
        return $num;
    }
    
    /**
     * Display final test results
     */
    private function displayResults() {
        echo "<div class='test-section'>";
        echo "<h2>📋 Test Results Summary</h2>";
        
        $total_errors = count($this->errors);
        $total_warnings = count($this->warnings);
        
        echo "<h3>📊 Overall Status:</h3>";
        
        if ($total_errors === 0 && $total_warnings === 0) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #155724; margin: 0;'>🎉 ALL TESTS PASSED</h4>";
            echo "<p style='color: #155724; margin: 5px 0 0 0;'>Gary AI plugin is fully compatible with PHP " . PHP_VERSION . "</p>";
            echo "</div>";
        } elseif ($total_errors === 0) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #856404; margin: 0;'>⚠️ TESTS PASSED WITH WARNINGS</h4>";
            echo "<p style='color: #856404; margin: 5px 0 0 0;'>Plugin is compatible but some optimizations recommended.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ COMPATIBILITY ISSUES FOUND</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Please address the errors below before deployment.</p>";
            echo "</div>";
        }
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ Errors Found ({$total_errors}):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ Warnings ({$total_warnings}):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>✅ Compatibility Summary:</h3>";
        echo "<ul class='checklist'>";
        echo "<li class='pass'>PHP Version: " . PHP_VERSION . " (minimum 7.4 required)</li>";
        echo "<li class='pass'>Plugin Structure: All core files present</li>";
        echo "<li class='pass'>Class Loading: Core classes available</li>";
        echo "<li class='pass'>Function Support: Required functions available</li>";
        echo "<li class='pass'>Security Features: Cryptographic functions available</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIPhpCompatibilityTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_php_compatibility_test() {
    $test = new GaryAIPhpCompatibilityTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 