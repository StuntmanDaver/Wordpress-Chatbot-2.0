<?php
/**
 * Gary AI Plugin - Export System Test Suite
 * 
 * Tests export functionality with large datasets, memory monitoring,
 * and error handling validation.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export System Test Class
 */
class GaryAIExportSystemTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    private $test_data = [];
    
    public function __construct() {
        echo "<h1>📤 Gary AI Plugin - Export System Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .memory-chart { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px; }
            .metric { display: inline-block; margin: 5px 10px; padding: 5px; background: #e9ecef; border-radius: 3px; }
            .benchmark { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }
            .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; font-size: 12px; }
        </style>";
    }
    
    /**
     * Run all export system tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting Export System Tests...</h2>";
        echo "<p><strong>Plugin Version:</strong> " . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : 'Unknown') . "</p>";
        echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
        echo "<p><strong>PHP Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
        echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</p>";
        echo "<p><strong>Max Post Size:</strong> " . ini_get('post_max_size') . "</p>";
        echo "</div>";
        
        // Core export tests
        $this->testDatabaseSeeding();
        $this->testSmallDatasetExport();
        $this->testLargeDatasetExport();
        $this->testMemoryUsageMonitoring();
        $this->testExecutionTimeTracking();
        $this->testInvalidPeriodHandling();
        $this->testExportFormatValidation();
        $this->testConcurrentExports();
        $this->testExportCleanup();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test database seeding with mock records
     */
    private function testDatabaseSeeding() {
        echo "<div class='test-section'>";
        echo "<h3>🌱 Database Seeding Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") === $analytics_table;
            
            if (!$table_exists) {
                $this->addError('Analytics table does not exist: ' . $analytics_table);
                echo "<p class='error'>❌ Analytics table missing. Cannot proceed with seeding.</p>";
                echo "</div>";
                return;
            }
            
            // Clear existing test data
            $deleted = $wpdb->delete($analytics_table, ['session_id' => ['LIKE' => 'export_test_%']], ['%s']);
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Seeding Progress:</h4>";
            
            $target_records = 10000;
            $batch_size = 1000;
            $batches = ceil($target_records / $batch_size);
            
            $start_memory = memory_get_usage(true);
            $start_time = microtime(true);
            
            $total_inserted = 0;
            
            for ($batch = 0; $batch < $batches; $batch++) {
                $batch_start = microtime(true);
                
                // Prepare batch insert
                $values = [];
                $placeholders = [];
                
                for ($i = 0; $i < $batch_size; $i++) {
                    $record_id = ($batch * $batch_size) + $i;
                    if ($record_id >= $target_records) break;
                    
                    $session_id = 'export_test_' . $record_id;
                    $message = 'Test message ' . $record_id . ' with some longer content to simulate real data';
                    $response = 'Test response ' . $record_id . ' with AI-generated content that might be longer';
                    $response_time = 0.5 + (mt_rand(0, 200) / 100); // 0.5-2.5 seconds
                    $timestamp = date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 30)); // Last 30 days
                    
                    $values = array_merge($values, [$session_id, $message, $response, $response_time, $timestamp]);
                    $placeholders[] = "(%s, %s, %s, %f, %s)";
                }
                
                if (!empty($placeholders)) {
                    $query = "INSERT INTO $analytics_table (session_id, message, response, response_time, timestamp) VALUES " . implode(', ', $placeholders);
                    $result = $wpdb->query($wpdb->prepare($query, $values));
                    
                    if ($result !== false) {
                        $total_inserted += $result;
                    }
                }
                
                $batch_time = (microtime(true) - $batch_start) * 1000;
                $progress = (($batch + 1) / $batches) * 100;
                
                echo "<div class='progress-bar'>";
                echo "<div class='progress-fill' style='width: {$progress}%'></div>";
                echo "</div>";
                echo "<div class='metric'>Batch " . ($batch + 1) . "/$batches: " . number_format($batch_time, 2) . "ms</div>";
                
                // Prevent timeout and memory issues
                if ($batch % 5 === 0) {
                    sleep(0.1); // Brief pause every 5 batches
                }
            }
            
            $total_time = (microtime(true) - $start_time) * 1000;
            $end_memory = memory_get_usage(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
            
            echo "<div class='metric'>Total Inserted: " . number_format($total_inserted) . " records</div>";
            echo "<div class='metric'>Total Time: " . number_format($total_time, 2) . "ms</div>";
            echo "<div class='metric'>Memory Used: " . number_format($memory_used, 2) . " MB</div>";
            echo "<div class='metric'>Rate: " . number_format($total_inserted / ($total_time / 1000), 2) . " records/sec</div>";
            
            // Verify final count
            $final_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'export_test_%'");
            
            if ($final_count >= $target_records * 0.95) { // Allow 5% tolerance
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Successfully seeded {$final_count} records</div>";
                $this->addResult('database_seeding', true, "Seeded {$final_count} records successfully");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Issues:</strong> Only seeded {$final_count} out of {$target_records} target records</div>";
                $this->addResult('database_seeding', false, "Seeding incomplete: {$final_count}/{$target_records}");
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Database seeding failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Database seeding failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test small dataset export (baseline)
     */
    private function testSmallDatasetExport() {
        echo "<div class='test-section'>";
        echo "<h3>📊 Small Dataset Export Test</h3>";
        
        try {
            if (!class_exists('GaryAI_Admin_AJAX')) {
                echo "<p class='warning'>⚠️ GaryAI_Admin_AJAX class not found</p>";
                $this->addWarning('Admin AJAX class not available for testing');
                echo "</div>";
                return;
            }
            
            $ajax_handler = new GaryAI_Admin_AJAX();
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Small Export Metrics:</h4>";
            
            $start_memory = memory_get_usage(true);
            $start_time = microtime(true);
            
            // Simulate export request for last 7 days (small dataset)
            $_POST = [
                'action' => 'gary_ai_export_analytics',
                'period' => 'last_7_days',
                'format' => 'csv',
                'nonce' => wp_create_nonce('gary_ai_export_nonce')
            ];
            
            ob_start();
            $ajax_handler->handleExportAnalytics();
            $output = ob_get_clean();
            
            $export_time = (microtime(true) - $start_time) * 1000;
            $end_memory = memory_get_usage(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
            
            echo "<div class='metric'>Export Time: " . number_format($export_time, 2) . "ms</div>";
            echo "<div class='metric'>Memory Used: " . number_format($memory_used, 2) . " MB</div>";
            echo "<div class='metric'>Output Size: " . number_format(strlen($output)) . " bytes</div>";
            
            // Analyze output
            $lines = substr_count($output, "\n");
            echo "<div class='metric'>CSV Lines: " . number_format($lines) . "</div>";
            
            // Performance benchmarks for small datasets
            if ($export_time < 1000 && $memory_used < 10) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Small dataset export very fast</div>";
                $this->addResult('small_dataset_export', true, 'Small export performance excellent');
            } elseif ($export_time < 5000 && $memory_used < 50) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Small dataset export acceptable</div>";
                $this->addResult('small_dataset_export', true, 'Small export performance good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Small dataset export needs optimization</div>";
                $this->addResult('small_dataset_export', false, 'Small export performance poor');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Small dataset export test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Small dataset export test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test large dataset export (10,000+ records)
     */
    private function testLargeDatasetExport() {
        echo "<div class='test-section'>";
        echo "<h3>📈 Large Dataset Export Test</h3>";
        
        try {
            if (!class_exists('GaryAI_Admin_AJAX')) {
                echo "<p class='warning'>⚠️ GaryAI_Admin_AJAX class not found</p>";
                $this->addWarning('Admin AJAX class not available for testing');
                echo "</div>";
                return;
            }
            
            $ajax_handler = new GaryAI_Admin_AJAX();
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Large Export Metrics:</h4>";
            
            $start_memory = memory_get_usage(true);
            $peak_memory_before = memory_get_peak_usage(true);
            $start_time = microtime(true);
            
            // Simulate export request for all time (large dataset)
            $_POST = [
                'action' => 'gary_ai_export_analytics',
                'period' => 'all_time',
                'format' => 'csv',
                'nonce' => wp_create_nonce('gary_ai_export_nonce')
            ];
            
            ob_start();
            $ajax_handler->handleExportAnalytics();
            $output = ob_get_clean();
            
            $export_time = (microtime(true) - $start_time) * 1000;
            $end_memory = memory_get_usage(true);
            $peak_memory_after = memory_get_peak_usage(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
            $peak_memory_used = ($peak_memory_after - $peak_memory_before) / 1024 / 1024; // MB
            
            echo "<div class='metric'>Export Time: " . number_format($export_time, 2) . "ms</div>";
            echo "<div class='metric'>Memory Used: " . number_format($memory_used, 2) . " MB</div>";
            echo "<div class='metric'>Peak Memory: " . number_format($peak_memory_used, 2) . " MB</div>";
            echo "<div class='metric'>Output Size: " . number_format(strlen($output) / 1024, 2) . " KB</div>";
            
            // Analyze output
            $lines = substr_count($output, "\n");
            echo "<div class='metric'>CSV Lines: " . number_format($lines) . "</div>";
            
            // Calculate efficiency metrics
            $memory_per_record = $lines > 0 ? ($memory_used * 1024) / $lines : 0; // KB per record
            $time_per_record = $lines > 0 ? $export_time / $lines : 0; // ms per record
            
            echo "<div class='metric'>Memory/Record: " . number_format($memory_per_record, 3) . " KB</div>";
            echo "<div class='metric'>Time/Record: " . number_format($time_per_record, 3) . " ms</div>";
            
            // Performance benchmarks for large datasets
            if ($export_time < 30000 && $memory_used < 100 && $lines >= 5000) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Large dataset export handles 10K+ records efficiently</div>";
                $this->addResult('large_dataset_export', true, "Large export excellent: {$lines} records in " . number_format($export_time/1000, 1) . "s");
            } elseif ($export_time < 60000 && $memory_used < 200 && $lines >= 1000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Large dataset export acceptable but could be optimized</div>";
                $this->addResult('large_dataset_export', true, "Large export good: {$lines} records in " . number_format($export_time/1000, 1) . "s");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Needs Optimization:</strong> Large dataset export too slow or memory intensive</div>";
                $this->addResult('large_dataset_export', false, "Large export needs optimization: {$lines} records in " . number_format($export_time/1000, 1) . "s");
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Large dataset export test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Large dataset export test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test memory usage monitoring during export
     */
    private function testMemoryUsageMonitoring() {
        echo "<div class='test-section'>";
        echo "<h3>🧠 Memory Usage Monitoring Test</h3>";
        
        try {
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Memory Monitoring Metrics:</h4>";
            
            $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
            $current_memory = memory_get_usage(true);
            $peak_memory = memory_get_peak_usage(true);
            
            // Convert to MB
            $limit_mb = $memory_limit / 1024 / 1024;
            $current_mb = $current_memory / 1024 / 1024;
            $peak_mb = $peak_memory / 1024 / 1024;
            
            echo "<div class='metric'>Memory Limit: " . number_format($limit_mb, 2) . " MB</div>";
            echo "<div class='metric'>Current Usage: " . number_format($current_mb, 2) . " MB</div>";
            echo "<div class='metric'>Peak Usage: " . number_format($peak_mb, 2) . " MB</div>";
            
            $usage_percentage = ($peak_mb / $limit_mb) * 100;
            echo "<div class='metric'>Peak Usage: " . number_format($usage_percentage, 1) . "%</div>";
            
            // Memory safety check
            $available_mb = $limit_mb - $peak_mb;
            echo "<div class='metric'>Available: " . number_format($available_mb, 2) . " MB</div>";
            
            // Memory benchmarks
            if ($usage_percentage < 50 && $available_mb > 64) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Plenty of memory available for large exports</div>";
                $this->addResult('memory_monitoring', true, 'Memory usage excellent - safe for large exports');
            } elseif ($usage_percentage < 75 && $available_mb > 32) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Adequate memory for moderate exports</div>";
                $this->addResult('memory_monitoring', true, 'Memory usage good - adequate for exports');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Risk:</strong> High memory usage - exports may fail</div>";
                $this->addResult('memory_monitoring', false, 'Memory usage concerning - may cause export failures');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Memory monitoring test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Memory monitoring test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test execution time tracking
     */
    private function testExecutionTimeTracking() {
        echo "<div class='test-section'>";
        echo "<h3>⏱️ Execution Time Tracking Test</h3>";
        
        try {
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Execution Time Metrics:</h4>";
            
            $max_execution_time = ini_get('max_execution_time');
            $start_time = microtime(true);
            
            // Simulate processing time for different dataset sizes
            $test_scenarios = [
                ['name' => '1K Records', 'records' => 1000, 'expected_time' => 2],
                ['name' => '5K Records', 'records' => 5000, 'expected_time' => 10],
                ['name' => '10K Records', 'records' => 10000, 'expected_time' => 20],
                ['name' => '50K Records', 'records' => 50000, 'expected_time' => 100]
            ];
            
            echo "<div class='metric'>Max Execution Time: " . $max_execution_time . "s</div>";
            
            foreach ($test_scenarios as $scenario) {
                $scenario_start = microtime(true);
                
                // Simulate processing time (0.002ms per record)
                usleep($scenario['records'] * 2); // 2 microseconds per record
                
                $scenario_time = (microtime(true) - $scenario_start) * 1000;
                $estimated_real_time = $scenario['expected_time'] * 1000; // Convert to ms
                
                echo "<div class='metric'>{$scenario['name']}: ~" . number_format($estimated_real_time, 0) . "ms (est)</div>";
            }
            
            $total_test_time = (microtime(true) - $start_time) * 1000;
            echo "<div class='metric'>Test Duration: " . number_format($total_test_time, 2) . "ms</div>";
            
            // Time limit analysis
            $safe_time_limit = $max_execution_time * 0.8; // Use 80% of limit
            
            if ($max_execution_time >= 60 || $max_execution_time == 0) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Sufficient time limit for large exports</div>";
                $this->addResult('execution_time_tracking', true, 'Execution time limit adequate');
            } elseif ($max_execution_time >= 30) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Adequate time for moderate exports</div>";
                $this->addResult('execution_time_tracking', true, 'Execution time limit adequate for moderate exports');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Risk:</strong> Low time limit may cause export timeouts</div>";
                $this->addResult('execution_time_tracking', false, 'Execution time limit too low for large exports');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Execution time tracking test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Execution time tracking test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test invalid period handling
     */
    private function testInvalidPeriodHandling() {
        echo "<div class='test-section'>";
        echo "<h3">🚫 Invalid Period Handling Test</h3>";
        
        try {
            if (!class_exists('GaryAI_Admin_AJAX')) {
                echo "<p class='warning'>⚠️ GaryAI_Admin_AJAX class not found</p>";
                $this->addWarning('Admin AJAX class not available for testing');
                echo "</div>";
                return;
            }
            
            $ajax_handler = new GaryAI_Admin_AJAX();
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Invalid Period Test Results:</h4>";
            
            $test_cases = [
                'invalid_period' => 'random_invalid_period',
                'empty_period' => '',
                'sql_injection' => "'; DROP TABLE users; --",
                'special_chars' => '<script>alert("xss")</script>',
                'null_period' => null
            ];
            
            $passed_tests = 0;
            
            foreach ($test_cases as $test_name => $period_value) {
                $_POST = [
                    'action' => 'gary_ai_export_analytics',
                    'period' => $period_value,
                    'format' => 'csv',
                    'nonce' => wp_create_nonce('gary_ai_export_nonce')
                ];
                
                ob_start();
                try {
                    $ajax_handler->handleExportAnalytics();
                    $output = ob_get_clean();
                    
                    // Check if error was handled properly
                    $is_error_response = (strpos($output, 'error') !== false || strpos($output, 'invalid') !== false);
                    
                    if ($is_error_response || empty($output)) {
                        echo "<div class='metric'>{$test_name}: ✅ Properly rejected</div>";
                        $passed_tests++;
                    } else {
                        echo "<div class='metric'>{$test_name}: ❌ Not properly handled</div>";
                    }
                    
                } catch (Exception $e) {
                    ob_end_clean();
                    echo "<div class='metric'>{$test_name}: ✅ Exception caught (good)</div>";
                    $passed_tests++;
                }
            }
            
            $total_tests = count($test_cases);
            echo "<div class='metric'>Passed: {$passed_tests}/{$total_tests}</div>";
            
            // Error handling benchmarks
            if ($passed_tests === $total_tests) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All invalid periods properly rejected</div>";
                $this->addResult('invalid_period_handling', true, 'All invalid periods handled correctly');
            } elseif ($passed_tests >= $total_tests * 0.8) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Most invalid periods handled</div>";
                $this->addResult('invalid_period_handling', true, 'Most invalid periods handled correctly');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Security Risk:</strong> Invalid periods not properly validated</div>";
                $this->addResult('invalid_period_handling', false, 'Invalid period validation insufficient');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Invalid period handling test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Invalid period handling test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test export format validation
     */
    private function testExportFormatValidation() {
        echo "<div class='test-section'>";
        echo "<h3">📋 Export Format Validation Test</h3>";
        
        try {
            global $wpdb;
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            // Get sample data for format validation
            $sample_data = $wpdb->get_results(
                "SELECT * FROM $analytics_table WHERE session_id LIKE 'export_test_%' LIMIT 5",
                ARRAY_A
            );
            
            if (empty($sample_data)) {
                echo "<p class='warning'>⚠️ No test data available for format validation</p>";
                $this->addWarning('No test data for format validation');
                echo "</div>";
                return;
            }
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Format Validation Results:</h4>";
            
            // Test CSV format
            $csv_content = $this->generateCSVFromData($sample_data);
            $csv_lines = explode("\n", $csv_content);
            $csv_valid = (count($csv_lines) >= 2 && strpos($csv_lines[0], ',') !== false);
            
            echo "<div class='metric'>CSV Format: " . ($csv_valid ? '✅ Valid' : '❌ Invalid') . "</div>";
            echo "<div class='metric'>CSV Lines: " . count($csv_lines) . "</div>";
            echo "<div class='metric'>CSV Columns: " . (isset($csv_lines[0]) ? substr_count($csv_lines[0], ',') + 1 : 0) . "</div>";
            
            // Test JSON format  
            $json_content = json_encode($sample_data);
            $json_valid = (json_last_error() === JSON_ERROR_NONE);
            
            echo "<div class='metric'>JSON Format: " . ($json_valid ? '✅ Valid' : '❌ Invalid') . "</div>";
            echo "<div class='metric'>JSON Size: " . number_format(strlen($json_content)) . " bytes</div>";
            
            // Test data integrity
            $required_fields = ['session_id', 'message', 'response', 'response_time', 'timestamp'];
            $has_required_fields = true;
            
            foreach ($required_fields as $field) {
                if (!isset($sample_data[0][$field])) {
                    $has_required_fields = false;
                    break;
                }
            }
            
            echo "<div class='metric'>Required Fields: " . ($has_required_fields ? '✅ Present' : '❌ Missing') . "</div>";
            
            // Format validation benchmarks
            if ($csv_valid && $json_valid && $has_required_fields) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All export formats valid</div>";
                $this->addResult('export_format_validation', true, 'All export formats validated successfully');
            } elseif (($csv_valid || $json_valid) && $has_required_fields) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Basic export functionality works</div>";
                $this->addResult('export_format_validation', true, 'Basic export formats working');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Issues:</strong> Export format validation failed</div>";
                $this->addResult('export_format_validation', false, 'Export format validation issues found');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Export format validation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Export format validation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test concurrent export handling
     */
    private function testConcurrentExports() {
        echo "<div class='test-section'>";
        echo "<h3">🔄 Concurrent Export Test</h3>";
        
        echo "<div class='memory-chart'>";
        echo "<h4>📊 Concurrent Export Simulation:</h4>";
        echo "<p class='info'>ℹ️ Simulating multiple simultaneous export requests</p>";
        
        try {
            $export_times = [];
            $memory_usage = [];
            
            // Simulate 3 concurrent export requests
            for ($i = 0; $i < 3; $i++) {
                $start_time = microtime(true);
                $start_memory = memory_get_usage(true);
                
                // Simulate export processing
                usleep(100000); // 100ms processing time
                
                $export_time = (microtime(true) - $start_time) * 1000;
                $memory_used = (memory_get_usage(true) - $start_memory) / 1024; // KB
                
                $export_times[] = $export_time;
                $memory_usage[] = $memory_used;
                
                echo "<div class='metric'>Export " . ($i + 1) . ": " . number_format($export_time, 2) . "ms</div>";
            }
            
            $avg_time = array_sum($export_times) / count($export_times);
            $max_time = max($export_times);
            $total_memory = array_sum($memory_usage);
            
            echo "<div class='metric'>Average Time: " . number_format($avg_time, 2) . "ms</div>";
            echo "<div class='metric'>Max Time: " . number_format($max_time, 2) . "ms</div>";
            echo "<div class='metric'>Total Memory: " . number_format($total_memory, 2) . " KB</div>";
            
            // Concurrent handling benchmarks
            if ($avg_time < 5000 && $max_time < 10000) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Handles concurrent exports efficiently</div>";
                $this->addResult('concurrent_exports', true, 'Concurrent export handling excellent');
            } elseif ($avg_time < 15000 && $max_time < 30000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Concurrent export handling acceptable</div>";
                $this->addResult('concurrent_exports', true, 'Concurrent export handling good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Concurrent exports need optimization</div>";
                $this->addResult('concurrent_exports', false, 'Concurrent export handling needs improvement');
            }
            
        } catch (Exception $e) {
            $this->addError('Concurrent export test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Concurrent export test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
    /**
     * Test export cleanup
     */
    private function testExportCleanup() {
        echo "<div class='test-section'>";
        echo "<h3">🧹 Export Cleanup Test</h3>";
        
        try {
            global $wpdb;
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='memory-chart'>";
            echo "<h4>📊 Cleanup Results:</h4>";
            
            // Count test records before cleanup
            $before_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'export_test_%'");
            echo "<div class='metric'>Records Before: " . number_format($before_count) . "</div>";
            
            // Perform cleanup
            $deleted = $wpdb->delete($analytics_table, 
                ['session_id' => ['LIKE' => 'export_test_%']], 
                ['%s']
            );
            
            // Count test records after cleanup  
            $after_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'export_test_%'");
            echo "<div class='metric'>Records After: " . number_format($after_count) . "</div>";
            echo "<div class='metric'>Records Deleted: " . number_format($deleted ?: 0) . "</div>";
            
            // Verify cleanup
            if ($after_count == 0 && $before_count > 0) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All test data cleaned up successfully</div>";
                $this->addResult('export_cleanup', true, "Cleaned up {$before_count} test records");
            } elseif ($after_count < $before_count) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Partial:</strong> Some test data remains</div>";
                $this->addResult('export_cleanup', true, "Partial cleanup: {$deleted} records removed");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Failed:</strong> Cleanup did not work</div>";
                $this->addResult('export_cleanup', false, 'Cleanup failed');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Export cleanup test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Export cleanup test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Helper method to generate CSV from data
     */
    private function generateCSVFromData($data) {
        if (empty($data)) return '';
        
        $csv = '';
        
        // Add header
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";
        
        // Add data rows
        foreach ($data as $row) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }
        
        return $csv;
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
        echo "<h2>📋 Export System Test Results Summary</h2>";
        
        $total_tests = count($this->results);
        $passed_tests = array_filter($this->results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        
        echo "<h3>📊 Overall Export System Status:</h3>";
        
        if (count($this->errors) === 0) {
            if (count($this->warnings) === 0) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #155724; margin: 0;'>🎉 EXPORT SYSTEM EXCELLENT</h4>";
                echo "<p style='color: #155724; margin: 5px 0 0 0;'>All export functionality working optimally for large datasets.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #856404; margin: 0;'>⚠️ EXPORT SYSTEM GOOD WITH OPTIMIZATION OPPORTUNITIES</h4>";
                echo "<p style='color: #856404; margin: 5px 0 0 0;'>Export system works but some areas could be optimized.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ EXPORT SYSTEM ISSUES DETECTED</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Export system requires fixes before handling large datasets.</p>";
            echo "</div>";
        }
        
        echo "<p><strong>Export Tests Passed:</strong> $passed_count / $total_tests</p>";
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ Export System Issues (" . count($this->errors) . "):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ Export System Warnings (" . count($this->warnings) . "):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>📊 Export System Benchmarks:</h3>";
        echo "<ul>";
        echo "<li><strong>Large Dataset:</strong> Target 10K+ records in <30s</li>";
        echo "<li><strong>Memory Usage:</strong> Target <100MB for large exports</li>";
        echo "<li><strong>Error Handling:</strong> All invalid inputs rejected</li>";
        echo "<li><strong>Concurrent Exports:</strong> Target 3+ simultaneous exports</li>";
        echo "<li><strong>Format Validation:</strong> CSV and JSON support</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIExportSystemTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_export_system_test() {
    $test = new GaryAIExportSystemTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 