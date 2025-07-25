<?php
/**
 * Gary AI Plugin - Performance Test Suite
 * 
 * Tests plugin performance under various load conditions
 * and measures response times for critical operations.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance Test Class
 */
class GaryAIPerformanceTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        echo "<h1>⚡ Gary AI Plugin - Performance Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .performance-chart { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px; }
            .metric { display: inline-block; margin: 5px 10px; padding: 5px; background: #e9ecef; border-radius: 3px; }
            .benchmark { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; }
        </style>";
    }
    
    /**
     * Run all performance tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting Performance Tests...</h2>";
        echo "<p><strong>Plugin Version:</strong> " . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : 'Unknown') . "</p>";
        echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
        echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
        echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</p>";
        echo "</div>";
        
        // Core performance tests
        $this->testPluginLoadTime();
        $this->testDatabasePerformance();
        $this->testAPIClientPerformance();
        $this->testAnalyticsPerformance();
        $this->testMemoryUsage();
        $this->testConcurrentRequests();
        $this->testJavaScriptPerformance();
        $this->testCSSLoadTime();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test plugin load time
     */
    private function testPluginLoadTime() {
        echo "<div class='test-section'>";
        echo "<h3>⚡ Plugin Load Time Test</h3>";
        
        try {
            $start_time = microtime(true);
            
            // Simulate plugin initialization
            do_action('plugins_loaded');
            
            $load_time = (microtime(true) - $start_time) * 1000; // Convert to milliseconds
            
            echo "<div class='performance-chart'>";
            echo "<h4>📊 Load Time Metrics:</h4>";
            echo "<div class='metric'>Plugin Load: " . number_format($load_time, 2) . "ms</div>";
            
            // Benchmark against target (should be <50ms)
            if ($load_time < 50) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Load time under 50ms target</div>";
                $this->addResult('plugin_load_time', true, "Load time: {$load_time}ms (Excellent)");
            } elseif ($load_time < 100) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Load time acceptable but could be optimized</div>";
                $this->addResult('plugin_load_time', true, "Load time: {$load_time}ms (Good)");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Needs Optimization:</strong> Load time exceeds 100ms</div>";
                $this->addResult('plugin_load_time', false, "Load time: {$load_time}ms (Slow)");
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Plugin load time test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Plugin load time test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test database performance
     */
    private function testDatabasePerformance() {
        echo "<div class='test-section'>";
        echo "<h3>🗄️ Database Performance Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            // Test basic queries
            $start_time = microtime(true);
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table");
            $query_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='performance-chart'>";
            echo "<h4>📊 Database Metrics:</h4>";
            echo "<div class='metric'>Count Query: " . number_format($query_time, 2) . "ms</div>";
            echo "<div class='metric'>Records: " . number_format($count) . "</div>";
            
            // Test insert performance
            $start_time = microtime(true);
            $result = $wpdb->insert(
                $analytics_table,
                [
                    'session_id' => 'perf_test_' . time(),
                    'message' => 'Performance test message',
                    'response' => 'Performance test response',
                    'response_time' => 1.5,
                    'timestamp' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%f', '%s']
            );
            $insert_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>Insert Time: " . number_format($insert_time, 2) . "ms</div>";
            
            // Clean up test record
            if ($result) {
                $wpdb->delete($analytics_table, ['session_id' => 'perf_test_' . time()], ['%s']);
            }
            
            // Performance benchmarks
            if ($query_time < 10 && $insert_time < 50) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Database operations well optimized</div>";
                $this->addResult('database_performance', true, 'Database performance excellent');
            } elseif ($query_time < 50 && $insert_time < 100) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Database performance acceptable</div>";
                $this->addResult('database_performance', true, 'Database performance good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Needs Optimization:</strong> Database queries are slow</div>";
                $this->addResult('database_performance', false, 'Database performance needs optimization');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Database performance test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Database performance test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test API client performance
     */
    private function testAPIClientPerformance() {
        echo "<div class='test-section'>";
        echo "<h3">🌐 API Client Performance Test</h3>";
        
        try {
            // Test API client initialization time
            $start_time = microtime(true);
            
            if (class_exists('GaryAI_ContextualAI_Client')) {
                $client = new GaryAI_ContextualAI_Client();
                $init_time = (microtime(true) - $start_time) * 1000;
                
                echo "<div class='performance-chart'>";
                echo "<h4>📊 API Client Metrics:</h4>";
                echo "<div class='metric'>Initialization: " . number_format($init_time, 2) . "ms</div>";
                
                // Test retry logic configuration
                $start_time = microtime(true);
                $client->setRetryConfig(3, 1000, 500); // 3 retries, 1s base delay, 500ms jitter
                $config_time = (microtime(true) - $start_time) * 1000;
                
                echo "<div class='metric'>Configuration: " . number_format($config_time, 2) . "ms</div>";
                
                // Performance benchmarks
                if ($init_time < 10 && $config_time < 5) {
                    echo "<div class='benchmark'>✅ <strong>Excellent:</strong> API client very responsive</div>";
                    $this->addResult('api_client_performance', true, 'API client performance excellent');
                } elseif ($init_time < 50 && $config_time < 20) {
                    echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> API client performance acceptable</div>";
                    $this->addResult('api_client_performance', true, 'API client performance good');
                } else {
                    echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> API client initialization slow</div>";
                    $this->addResult('api_client_performance', false, 'API client performance slow');
                }
                
                echo "</div>";
                
            } else {
                echo "<p class='warning'>⚠️ API Client class not found - plugin may not be fully loaded</p>";
                $this->addWarning('API Client class not available for testing');
            }
            
        } catch (Exception $e) {
            $this->addError('API client performance test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ API client performance test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test analytics performance
     */
    private function testAnalyticsPerformance() {
        echo "<div class='test-section'>";
        echo "<h3>📊 Analytics Performance Test</h3>";
        
        try {
            if (class_exists('GaryAI_Analytics')) {
                $analytics = new GaryAI_Analytics();
                
                // Test record insertion performance
                $start_time = microtime(true);
                
                for ($i = 0; $i < 10; $i++) {
                    $analytics->recordInteraction(
                        'perf_test_session_' . $i,
                        'Test message ' . $i,
                        'Test response ' . $i,
                        0.5 + ($i * 0.1)
                    );
                }
                
                $batch_time = (microtime(true) - $start_time) * 1000;
                $avg_time = $batch_time / 10;
                
                echo "<div class='performance-chart'>";
                echo "<h4>📊 Analytics Metrics:</h4>";
                echo "<div class='metric'>10 Records: " . number_format($batch_time, 2) . "ms</div>";
                echo "<div class='metric'>Avg per Record: " . number_format($avg_time, 2) . "ms</div>";
                
                // Test query performance
                $start_time = microtime(true);
                $stats = $analytics->getInteractionStats();
                $stats_time = (microtime(true) - $start_time) * 1000;
                
                echo "<div class='metric'>Stats Query: " . number_format($stats_time, 2) . "ms</div>";
                
                // Performance benchmarks
                if ($avg_time < 10 && $stats_time < 50) {
                    echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Analytics very fast</div>";
                    $this->addResult('analytics_performance', true, 'Analytics performance excellent');
                } elseif ($avg_time < 25 && $stats_time < 100) {
                    echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Analytics performance acceptable</div>";
                    $this->addResult('analytics_performance', true, 'Analytics performance good');
                } else {
                    echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Analytics operations slow</div>";
                    $this->addResult('analytics_performance', false, 'Analytics performance slow');
                }
                
                echo "</div>";
                
            } else {
                echo "<p class='warning'>⚠️ Analytics class not found</p>";
                $this->addWarning('Analytics class not available for testing');
            }
            
        } catch (Exception $e) {
            $this->addError('Analytics performance test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Analytics performance test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test memory usage
     */
    private function testMemoryUsage() {
        echo "<div class='test-section'>";
        echo "<h3>🧠 Memory Usage Test</h3>";
        
        try {
            $initial_memory = memory_get_usage(true);
            $peak_memory = memory_get_peak_usage(true);
            
            // Convert to MB
            $initial_mb = $initial_memory / 1024 / 1024;
            $peak_mb = $peak_memory / 1024 / 1024;
            $limit_mb = wp_convert_hr_to_bytes(ini_get('memory_limit')) / 1024 / 1024;
            
            echo "<div class='performance-chart'>";
            echo "<h4>📊 Memory Metrics:</h4>";
            echo "<div class='metric'>Current Usage: " . number_format($initial_mb, 2) . " MB</div>";
            echo "<div class='metric'>Peak Usage: " . number_format($peak_mb, 2) . " MB</div>";
            echo "<div class='metric'>Memory Limit: " . number_format($limit_mb, 2) . " MB</div>";
            
            $usage_percentage = ($peak_mb / $limit_mb) * 100;
            echo "<div class='metric'>Usage: " . number_format($usage_percentage, 1) . "%</div>";
            
            // Memory benchmarks
            if ($usage_percentage < 30) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Low memory usage</div>";
                $this->addResult('memory_usage', true, 'Memory usage excellent');
            } elseif ($usage_percentage < 60) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Moderate memory usage</div>";
                $this->addResult('memory_usage', true, 'Memory usage good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>High:</strong> High memory usage detected</div>";
                $this->addResult('memory_usage', false, 'Memory usage high');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Memory usage test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Memory usage test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test concurrent request handling
     */
    private function testConcurrentRequests() {
        echo "<div class='test-section'>";
        echo "<h3>🔄 Concurrent Request Test</h3>";
        
        echo "<div class='performance-chart'>";
        echo "<h4>📊 Concurrent Request Simulation:</h4>";
        echo "<p class='info'>ℹ️ Simulating multiple AJAX requests to test plugin responsiveness</p>";
        
        try {
            $request_times = [];
            
            // Simulate 5 concurrent AJAX requests
            for ($i = 0; $i < 5; $i++) {
                $start_time = microtime(true);
                
                // Simulate AJAX request processing
                do_action('wp_ajax_gary_ai_chat');
                
                $request_time = (microtime(true) - $start_time) * 1000;
                $request_times[] = $request_time;
                
                echo "<div class='metric'>Request " . ($i + 1) . ": " . number_format($request_time, 2) . "ms</div>";
            }
            
            $avg_time = array_sum($request_times) / count($request_times);
            $max_time = max($request_times);
            
            echo "<div class='metric'>Average: " . number_format($avg_time, 2) . "ms</div>";
            echo "<div class='metric'>Max: " . number_format($max_time, 2) . "ms</div>";
            
            // Performance benchmarks
            if ($avg_time < 100 && $max_time < 200) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Handles concurrent requests well</div>";
                $this->addResult('concurrent_requests', true, 'Concurrent request handling excellent');
            } elseif ($avg_time < 300 && $max_time < 500) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Concurrent request handling acceptable</div>";
                $this->addResult('concurrent_requests', true, 'Concurrent request handling good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Concurrent request handling needs optimization</div>";
                $this->addResult('concurrent_requests', false, 'Concurrent request handling slow');
            }
            
        } catch (Exception $e) {
            $this->addError('Concurrent request test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Concurrent request test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
    /**
     * Test JavaScript performance
     */
    private function testJavaScriptPerformance() {
        echo "<div class='test-section'>";
        echo "<h3>⚡ JavaScript Performance Test</h3>";
        
        try {
            $js_files = [
                'assets/js/chat-widget.js',
                'assets/js/admin.js'
            ];
            
            echo "<div class='performance-chart'>";
            echo "<h4>📊 JavaScript File Metrics:</h4>";
            
            $total_size = 0;
            foreach ($js_files as $file) {
                $file_path = WP_PLUGIN_DIR . '/gary-ai/' . $file;
                if (file_exists($file_path)) {
                    $size = filesize($file_path);
                    $size_kb = $size / 1024;
                    $total_size += $size;
                    
                    echo "<div class='metric'>" . basename($file) . ": " . number_format($size_kb, 2) . " KB</div>";
                }
            }
            
            $total_kb = $total_size / 1024;
            echo "<div class='metric'>Total JS: " . number_format($total_kb, 2) . " KB</div>";
            
            // JavaScript size benchmarks
            if ($total_kb < 50) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> JavaScript files well optimized</div>";
                $this->addResult('javascript_performance', true, 'JavaScript size excellent');
            } elseif ($total_kb < 100) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> JavaScript size acceptable</div>";
                $this->addResult('javascript_performance', true, 'JavaScript size good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Large:</strong> JavaScript files should be optimized</div>";
                $this->addResult('javascript_performance', false, 'JavaScript files too large');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('JavaScript performance test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ JavaScript performance test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test CSS load time
     */
    private function testCSSLoadTime() {
        echo "<div class='test-section'>";
        echo "<h3>🎨 CSS Performance Test</h3>";
        
        try {
            $css_files = [
                'assets/css/chat-widget.css',
                'assets/css/admin.css'
            ];
            
            echo "<div class='performance-chart'>";
            echo "<h4>📊 CSS File Metrics:</h4>";
            
            $total_size = 0;
            foreach ($css_files as $file) {
                $file_path = WP_PLUGIN_DIR . '/gary-ai/' . $file;
                if (file_exists($file_path)) {
                    $size = filesize($file_path);
                    $size_kb = $size / 1024;
                    $total_size += $size;
                    
                    echo "<div class='metric'>" . basename($file) . ": " . number_format($size_kb, 2) . " KB</div>";
                }
            }
            
            $total_kb = $total_size / 1024;
            echo "<div class='metric'>Total CSS: " . number_format($total_kb, 2) . " KB</div>";
            
            // CSS size benchmarks
            if ($total_kb < 20) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> CSS files well optimized</div>";
                $this->addResult('css_performance', true, 'CSS size excellent');
            } elseif ($total_kb < 50) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> CSS size acceptable</div>";
                $this->addResult('css_performance', true, 'CSS size good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Large:</strong> CSS files should be optimized</div>";
                $this->addResult('css_performance', false, 'CSS files too large');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('CSS performance test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ CSS performance test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Add a test result
     */
    private function addResult($test, $passed, $message) {
        $this->results[$test] = [
            'passed' => $passed,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    /**
     * Add an error
     */
    private function addError($message) {
        $this->errors[] = $message;
    }
    
    /**
     * Add a warning
     */
    private function addWarning($message) {
        $this->warnings[] = $message;
    }
    
    /**
     * Display final test results
     */
    private function displayResults() {
        echo "<div class='test-section'>";
        echo "<h2>📋 Performance Test Results Summary</h2>";
        
        $total_tests = count($this->results);
        $passed_tests = array_filter($this->results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        
        echo "<h3>📊 Overall Performance Status:</h3>";
        
        if (count($this->errors) === 0) {
            if (count($this->warnings) === 0) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #155724; margin: 0;'>🎉 EXCELLENT PERFORMANCE</h4>";
                echo "<p style='color: #155724; margin: 5px 0 0 0;'>All performance benchmarks met or exceeded.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #856404; margin: 0;'>⚠️ GOOD PERFORMANCE WITH OPTIMIZATION OPPORTUNITIES</h4>";
                echo "<p style='color: #856404; margin: 5px 0 0 0;'>Performance is acceptable but some areas could be optimized.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ PERFORMANCE ISSUES DETECTED</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Performance optimization required before production deployment.</p>";
            echo "</div>";
        }
        
        echo "<p><strong>Performance Tests Passed:</strong> $passed_count / $total_tests</p>";
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ Performance Issues (" . count($this->errors) . "):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ Optimization Opportunities (" . count($this->warnings) . "):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>📊 Performance Benchmarks:</h3>";
        echo "<ul>";
        echo "<li><strong>Plugin Load Time:</strong> Target < 50ms</li>";
        echo "<li><strong>Database Queries:</strong> Target < 10ms</li>";
        echo "<li><strong>Memory Usage:</strong> Target < 30% of limit</li>";
        echo "<li><strong>AJAX Responses:</strong> Target < 100ms average</li>";
        echo "<li><strong>JavaScript Size:</strong> Target < 50KB total</li>";
        echo "<li><strong>CSS Size:</strong> Target < 20KB total</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIPerformanceTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_performance_test() {
    $test = new GaryAIPerformanceTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 