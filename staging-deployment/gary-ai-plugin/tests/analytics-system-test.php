<?php
/**
 * Gary AI Plugin - Analytics System Test Suite
 * 
 * Tests analytics functionality with large datasets, query optimization,
 * and data handling performance validation.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics System Test Class
 */
class GaryAIAnalyticsSystemTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        echo "<h1>📊 Gary AI Plugin - Analytics System Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .analytics-chart { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px; }
            .metric { display: inline-block; margin: 5px 10px; padding: 5px; background: #e9ecef; border-radius: 3px; }
            .benchmark { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .query-result { background: #f1f3f4; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; font-size: 12px; }
        </style>";
    }
    
    /**
     * Run all analytics system tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting Analytics System Tests...</h2>";
        echo "<p><strong>Plugin Version:</strong> " . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : 'Unknown') . "</p>";
        echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
        echo "<p><strong>Database Version:</strong> " . $this->getDatabaseVersion() . "</p>";
        echo "<p><strong>PHP Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
        echo "</div>";
        
        // Core analytics tests
        $this->testEmptyDatasetHandling();
        $this->testLargeDatasetCreation();
        $this->testQueryOptimization();
        $this->testAnalyticsClassFunctionality();
        $this->testStatsCalculation();
        $this->testDataAggregation();
        $this->testIndexEffectiveness();
        $this->testConcurrentAnalytics();
        $this->testAnalyticsCleanup();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test empty dataset handling
     */
    private function testEmptyDatasetHandling() {
        echo "<div class='test-section'>";
        echo "<h3>🗳️ Empty Dataset Handling Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            // Temporarily clear all data for empty test
            $backup_data = $wpdb->get_results("SELECT * FROM $analytics_table LIMIT 100", ARRAY_A);
            $wpdb->query("DELETE FROM $analytics_table");
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Empty Dataset Test Results:</h4>";
            
            // Test count query with empty dataset
            $start_time = microtime(true);
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table");
            $count_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>Count Query: " . number_format($count_time, 2) . "ms</div>";
            echo "<div class='metric'>Result: {$count} records</div>";
            
            // Test stats query with empty dataset
            $start_time = microtime(true);
            $avg_response_time = $wpdb->get_var("SELECT AVG(response_time) FROM $analytics_table");
            $avg_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>AVG Query: " . number_format($avg_time, 2) . "ms</div>";
            echo "<div class='metric'>AVG Result: " . ($avg_response_time ?: 'NULL') . "</div>";
            
            // Test GROUP BY query with empty dataset
            $start_time = microtime(true);
            $daily_stats = $wpdb->get_results("SELECT DATE(timestamp) as date, COUNT(*) as count FROM $analytics_table GROUP BY DATE(timestamp)");
            $group_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>GROUP BY Query: " . number_format($group_time, 2) . "ms</div>";
            echo "<div class='metric'>Grouped Results: " . count($daily_stats) . " days</div>";
            
            // Restore backup data
            if (!empty($backup_data)) {
                foreach ($backup_data as $row) {
                    $wpdb->insert($analytics_table, $row);
                }
            }
            
            // Empty dataset benchmarks
            if ($count_time < 10 && $avg_time < 10 && $group_time < 10) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Empty dataset queries very fast</div>";
                $this->addResult('empty_dataset_handling', true, 'Empty dataset handling excellent');
            } elseif ($count_time < 50 && $avg_time < 50 && $group_time < 50) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Empty dataset queries acceptable</div>";
                $this->addResult('empty_dataset_handling', true, 'Empty dataset handling good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Empty dataset queries too slow</div>";
                $this->addResult('empty_dataset_handling', false, 'Empty dataset handling slow');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Empty dataset handling test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Empty dataset handling test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test large dataset creation (1 million rows simulation)
     */
    private function testLargeDatasetCreation() {
        echo "<div class='test-section'>";
        echo "<h3">📈 Large Dataset Creation Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Large Dataset Simulation:</h4>";
            echo "<p class='info'>ℹ️ Creating test dataset to simulate 1M row scenarios</p>";
            
            $target_records = 50000; // Scaled down for testing
            $batch_size = 5000;
            $batches = ceil($target_records / $batch_size);
            
            $start_memory = memory_get_usage(true);
            $start_time = microtime(true);
            
            $total_inserted = 0;
            
            // Clear existing analytics test data
            $wpdb->delete($analytics_table, ['session_id' => ['LIKE' => 'analytics_test_%']], ['%s']);
            
            for ($batch = 0; $batch < $batches; $batch++) {
                $batch_start = microtime(true);
                
                // Prepare batch insert
                $values = [];
                $placeholders = [];
                
                for ($i = 0; $i < $batch_size; $i++) {
                    $record_id = ($batch * $batch_size) + $i;
                    if ($record_id >= $target_records) break;
                    
                    $session_id = 'analytics_test_' . $record_id;
                    $message = 'Analytics test message ' . $record_id;
                    $response = 'Analytics test response ' . $record_id;
                    $response_time = 0.5 + (mt_rand(0, 400) / 100); // 0.5-4.5 seconds
                    $timestamp = date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 90)); // Last 90 days
                    
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
                echo "<div class='metric'>Batch " . ($batch + 1) . ": " . number_format($batch_time, 2) . "ms</div>";
                
                // Brief pause to prevent timeout
                if ($batch % 3 === 0) {
                    usleep(50000); // 50ms pause
                }
            }
            
            $total_time = (microtime(true) - $start_time) * 1000;
            $end_memory = memory_get_usage(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
            
            echo "<div class='metric'>Total Inserted: " . number_format($total_inserted) . " records</div>";
            echo "<div class='metric'>Total Time: " . number_format($total_time, 2) . "ms</div>";
            echo "<div class='metric'>Memory Used: " . number_format($memory_used, 2) . " MB</div>";
            echo "<div class='metric'>Rate: " . number_format($total_inserted / ($total_time / 1000), 2) . " records/sec</div>";
            
            // Test query performance on large dataset
            $query_start = microtime(true);
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'");
            $query_time = (microtime(true) - $query_start) * 1000;
            
            echo "<div class='metric'>Count Query on {$count} records: " . number_format($query_time, 2) . "ms</div>";
            
            // Large dataset benchmarks
            if ($total_inserted >= $target_records * 0.95 && $query_time < 100) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Large dataset creation and queries efficient</div>";
                $this->addResult('large_dataset_creation', true, "Created {$total_inserted} records efficiently");
            } elseif ($total_inserted >= $target_records * 0.8 && $query_time < 500) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Large dataset handling acceptable</div>";
                $this->addResult('large_dataset_creation', true, "Created {$total_inserted} records with good performance");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Issues:</strong> Large dataset performance needs optimization</div>";
                $this->addResult('large_dataset_creation', false, "Large dataset performance issues");
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Large dataset creation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Large dataset creation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test query optimization
     */
    private function testQueryOptimization() {
        echo "<div class='test-section'>";
        echo "<h3">⚡ Query Optimization Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Query Performance Analysis:</h4>";
            
            // Test various query patterns
            $test_queries = [
                'Simple Count' => "SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'AVG Response Time' => "SELECT AVG(response_time) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Daily Aggregation' => "SELECT DATE(timestamp) as date, COUNT(*) as count, AVG(response_time) as avg_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY DATE(timestamp)",
                'Recent Records' => "SELECT * FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 100",
                'Complex Stats' => "SELECT session_id, COUNT(*) as messages, AVG(response_time) as avg_time, MAX(response_time) as max_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY session_id HAVING COUNT(*) > 0 LIMIT 10"
            ];
            
            $query_results = [];
            
            foreach ($test_queries as $query_name => $query) {
                $start_time = microtime(true);
                
                try {
                    $result = $wpdb->get_results($query);
                    $query_time = (microtime(true) - $start_time) * 1000;
                    $result_count = is_array($result) ? count($result) : 1;
                    
                    $query_results[$query_name] = [
                        'time' => $query_time,
                        'results' => $result_count,
                        'success' => true
                    ];
                    
                    echo "<div class='query-result'>";
                    echo "<strong>{$query_name}:</strong> " . number_format($query_time, 2) . "ms ({$result_count} results)";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    $query_results[$query_name] = [
                        'time' => 0,
                        'results' => 0,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    
                    echo "<div class='query-result' style='background: #f8d7da;'>";
                    echo "<strong>{$query_name}:</strong> ERROR - " . $e->getMessage();
                    echo "</div>";
                }
            }
            
            // Analyze query performance
            $successful_queries = array_filter($query_results, function($result) {
                return $result['success'];
            });
            
            $avg_query_time = count($successful_queries) > 0 ? 
                array_sum(array_column($successful_queries, 'time')) / count($successful_queries) : 0;
            
            $slowest_query = max(array_column($successful_queries, 'time'));
            
            echo "<div class='metric'>Successful Queries: " . count($successful_queries) . "/" . count($test_queries) . "</div>";
            echo "<div class='metric'>Average Query Time: " . number_format($avg_query_time, 2) . "ms</div>";
            echo "<div class='metric'>Slowest Query: " . number_format($slowest_query, 2) . "ms</div>";
            
            // Query optimization benchmarks
            if (count($successful_queries) === count($test_queries) && $avg_query_time < 100 && $slowest_query < 1000) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All queries optimized and fast</div>";
                $this->addResult('query_optimization', true, 'Query optimization excellent');
            } elseif (count($successful_queries) >= count($test_queries) * 0.8 && $avg_query_time < 500 && $slowest_query < 5000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Most queries optimized</div>";
                $this->addResult('query_optimization', true, 'Query optimization good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Needs Work:</strong> Query optimization required</div>";
                $this->addResult('query_optimization', false, 'Query optimization needs improvement');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Query optimization test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Query optimization test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test analytics class functionality
     */
    private function testAnalyticsClassFunctionality() {
        echo "<div class='test-section'>";
        echo "<h3>🧪 Analytics Class Functionality Test</h3>";
        
        try {
            if (!class_exists('GaryAI_Analytics')) {
                echo "<p class='warning'>⚠️ GaryAI_Analytics class not found</p>";
                $this->addWarning('Analytics class not available for testing');
                echo "</div>";
                return;
            }
            
            $analytics = new GaryAI_Analytics();
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Class Method Testing:</h4>";
            
            // Test record interaction
            $start_time = microtime(true);
            $result = $analytics->recordInteraction(
                'class_test_session',
                'Test message for class testing',
                'Test response for class testing',
                1.25
            );
            $record_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>Record Interaction: " . number_format($record_time, 2) . "ms</div>";
            echo "<div class='metric'>Record Result: " . ($result ? 'Success' : 'Failed') . "</div>";
            
            // Test get stats
            $start_time = microtime(true);
            $stats = $analytics->getInteractionStats();
            $stats_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>Get Stats: " . number_format($stats_time, 2) . "ms</div>";
            echo "<div class='metric'>Stats Retrieved: " . (is_array($stats) ? 'Yes' : 'No') . "</div>";
            
            if (is_array($stats)) {
                foreach ($stats as $key => $value) {
                    echo "<div class='metric'>{$key}: {$value}</div>";
                }
            }
            
            // Test batch operations
            $start_time = microtime(true);
            for ($i = 0; $i < 10; $i++) {
                $analytics->recordInteraction(
                    'class_test_batch_' . $i,
                    'Batch test message ' . $i,
                    'Batch test response ' . $i,
                    0.5 + ($i * 0.1)
                );
            }
            $batch_time = (microtime(true) - $start_time) * 1000;
            
            echo "<div class='metric'>Batch 10 Records: " . number_format($batch_time, 2) . "ms</div>";
            echo "<div class='metric'>Avg per Record: " . number_format($batch_time / 10, 2) . "ms</div>";
            
            // Analytics class benchmarks
            if ($record_time < 50 && $stats_time < 100 && $batch_time < 500) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Analytics class very efficient</div>";
                $this->addResult('analytics_class_functionality', true, 'Analytics class performance excellent');
            } elseif ($record_time < 200 && $stats_time < 500 && $batch_time < 2000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Analytics class acceptable</div>";
                $this->addResult('analytics_class_functionality', true, 'Analytics class performance good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Analytics class needs optimization</div>";
                $this->addResult('analytics_class_functionality', false, 'Analytics class performance slow');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Analytics class functionality test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Analytics class functionality test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test statistics calculation
     */
    private function testStatsCalculation() {
        echo "<div class='test-section'>";
        echo "<h3">📈 Statistics Calculation Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Statistical Analysis:</h4>";
            
            // Test comprehensive stats
            $stats_queries = [
                'Total Messages' => "SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Unique Sessions' => "SELECT COUNT(DISTINCT session_id) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Avg Response Time' => "SELECT AVG(response_time) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Min Response Time' => "SELECT MIN(response_time) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Max Response Time' => "SELECT MAX(response_time) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%'",
                'Daily Average' => "SELECT DATE(timestamp) as date, COUNT(*) as messages, AVG(response_time) as avg_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY DATE(timestamp) ORDER BY date DESC LIMIT 7"
            ];
            
            $stats_results = [];
            $total_stats_time = 0;
            
            foreach ($stats_queries as $stat_name => $query) {
                $start_time = microtime(true);
                
                try {
                    if ($stat_name === 'Daily Average') {
                        $result = $wpdb->get_results($query);
                        $value = count($result) . ' days';
                    } else {
                        $result = $wpdb->get_var($query);
                        $value = is_numeric($result) ? number_format($result, 2) : $result;
                    }
                    
                    $query_time = (microtime(true) - $start_time) * 1000;
                    $total_stats_time += $query_time;
                    
                    $stats_results[$stat_name] = [
                        'value' => $value,
                        'time' => $query_time,
                        'success' => true
                    ];
                    
                    echo "<div class='metric'>{$stat_name}: {$value} (" . number_format($query_time, 2) . "ms)</div>";
                    
                } catch (Exception $e) {
                    $stats_results[$stat_name] = [
                        'value' => 'ERROR',
                        'time' => 0,
                        'success' => false
                    ];
                    
                    echo "<div class='metric' style='color: #dc3545;'>{$stat_name}: ERROR</div>";
                }
            }
            
            echo "<div class='metric'>Total Stats Time: " . number_format($total_stats_time, 2) . "ms</div>";
            
            $successful_stats = array_filter($stats_results, function($result) {
                return $result['success'];
            });
            
            // Statistics calculation benchmarks
            if (count($successful_stats) === count($stats_queries) && $total_stats_time < 500) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All statistics calculated quickly</div>";
                $this->addResult('stats_calculation', true, 'Statistics calculation excellent');
            } elseif (count($successful_stats) >= count($stats_queries) * 0.8 && $total_stats_time < 2000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Most statistics calculated efficiently</div>";
                $this->addResult('stats_calculation', true, 'Statistics calculation good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Statistics calculation needs optimization</div>";
                $this->addResult('stats_calculation', false, 'Statistics calculation too slow');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Statistics calculation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Statistics calculation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test data aggregation
     */
    private function testDataAggregation() {
        echo "<div class='test-section'>";
        echo "<h3">🔄 Data Aggregation Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Aggregation Performance:</h4>";
            
            // Test time-based aggregations
            $aggregation_queries = [
                'Hourly Stats' => "SELECT HOUR(timestamp) as hour, COUNT(*) as count, AVG(response_time) as avg_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(timestamp)",
                'Daily Stats' => "SELECT DATE(timestamp) as date, COUNT(*) as count, AVG(response_time) as avg_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY DATE(timestamp)",
                'Weekly Stats' => "SELECT YEARWEEK(timestamp) as week, COUNT(*) as count, AVG(response_time) as avg_time FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY YEARWEEK(timestamp)",
                'Response Time Buckets' => "SELECT CASE WHEN response_time < 1.0 THEN 'Fast' WHEN response_time < 2.0 THEN 'Medium' ELSE 'Slow' END as bucket, COUNT(*) as count FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' GROUP BY bucket"
            ];
            
            $aggregation_results = [];
            $total_aggregation_time = 0;
            
            foreach ($aggregation_queries as $agg_name => $query) {
                $start_time = microtime(true);
                
                try {
                    $result = $wpdb->get_results($query);
                    $query_time = (microtime(true) - $start_time) * 1000;
                    $total_aggregation_time += $query_time;
                    $result_count = count($result);
                    
                    $aggregation_results[$agg_name] = [
                        'count' => $result_count,
                        'time' => $query_time,
                        'success' => true
                    ];
                    
                    echo "<div class='metric'>{$agg_name}: {$result_count} groups (" . number_format($query_time, 2) . "ms)</div>";
                    
                } catch (Exception $e) {
                    $aggregation_results[$agg_name] = [
                        'count' => 0,
                        'time' => 0,
                        'success' => false
                    ];
                    
                    echo "<div class='metric' style='color: #dc3545;'>{$agg_name}: ERROR</div>";
                }
            }
            
            echo "<div class='metric'>Total Aggregation Time: " . number_format($total_aggregation_time, 2) . "ms</div>";
            
            $successful_aggregations = array_filter($aggregation_results, function($result) {
                return $result['success'];
            });
            
            // Data aggregation benchmarks
            if (count($successful_aggregations) === count($aggregation_queries) && $total_aggregation_time < 1000) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All aggregations fast and efficient</div>";
                $this->addResult('data_aggregation', true, 'Data aggregation excellent');
            } elseif (count($successful_aggregations) >= count($aggregation_queries) * 0.8 && $total_aggregation_time < 5000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Most aggregations working well</div>";
                $this->addResult('data_aggregation', true, 'Data aggregation good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Data aggregation needs optimization</div>";
                $this->addResult('data_aggregation', false, 'Data aggregation too slow');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Data aggregation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Data aggregation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test index effectiveness
     */
    private function testIndexEffectiveness() {
        echo "<div class='test-section'>";
        echo "<h3">🔍 Index Effectiveness Test</h3>";
        
        global $wpdb;
        
        try {
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Index Analysis:</h4>";
            
            // Check existing indexes
            $indexes = $wpdb->get_results("SHOW INDEX FROM $analytics_table");
            
            echo "<h5>Existing Indexes:</h5>";
            foreach ($indexes as $index) {
                echo "<div class='metric'>{$index->Key_name}: {$index->Column_name}</div>";
            }
            
            // Test queries with EXPLAIN to check index usage
            $index_test_queries = [
                'Session ID Lookup' => "SELECT * FROM $analytics_table WHERE session_id = 'analytics_test_1'",
                'Timestamp Range' => "SELECT * FROM $analytics_table WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                'Response Time Filter' => "SELECT * FROM $analytics_table WHERE response_time > 2.0",
                'Combined Filter' => "SELECT * FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ];
            
            foreach ($index_test_queries as $test_name => $query) {
                echo "<h6>{$test_name}:</h6>";
                
                // Run EXPLAIN
                $explain = $wpdb->get_results("EXPLAIN $query");
                
                if (!empty($explain)) {
                    $explain_row = $explain[0];
                    $using_index = (!empty($explain_row->key) && $explain_row->key !== 'NULL');
                    $rows_examined = $explain_row->rows;
                    
                    echo "<div class='query-result'>";
                    echo "Type: {$explain_row->type}, ";
                    echo "Key: " . ($explain_row->key ?: 'None') . ", ";
                    echo "Rows: {$rows_examined}, ";
                    echo "Extra: " . ($explain_row->Extra ?: 'None');
                    echo "</div>";
                    
                    // Time the actual query
                    $start_time = microtime(true);
                    $wpdb->get_results($query . " LIMIT 100");
                    $query_time = (microtime(true) - $start_time) * 1000;
                    
                    echo "<div class='metric'>Execution Time: " . number_format($query_time, 2) . "ms</div>";
                }
            }
            
            // Index effectiveness assessment
            $has_timestamp_index = false;
            $has_session_index = false;
            
            foreach ($indexes as $index) {
                if ($index->Column_name === 'timestamp') $has_timestamp_index = true;
                if ($index->Column_name === 'session_id') $has_session_index = true;
            }
            
            if ($has_timestamp_index && $has_session_index) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Key indexes present for optimal performance</div>";
                $this->addResult('index_effectiveness', true, 'Index effectiveness excellent');
            } elseif ($has_timestamp_index || $has_session_index) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Some indexes present but could be improved</div>";
                $this->addResult('index_effectiveness', true, 'Index effectiveness good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Missing:</strong> Key indexes missing for performance</div>";
                $this->addResult('index_effectiveness', false, 'Missing key indexes');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Index effectiveness test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Index effectiveness test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test concurrent analytics operations
     */
    private function testConcurrentAnalytics() {
        echo "<div class='test-section'>";
        echo "<h3">🔄 Concurrent Analytics Test</h3>";
        
        echo "<div class='analytics-chart'>";
        echo "<h4>📊 Concurrent Operations Simulation:</h4>";
        echo "<p class='info'>ℹ️ Simulating multiple analytics operations simultaneously</p>";
        
        try {
            if (!class_exists('GaryAI_Analytics')) {
                echo "<p class='warning'>⚠️ GaryAI_Analytics class not found</p>";
                $this->addWarning('Analytics class not available for concurrent testing');
                echo "</div></div>";
                return;
            }
            
            $analytics = new GaryAI_Analytics();
            $operation_times = [];
            
            // Simulate 5 concurrent analytics operations
            for ($i = 0; $i < 5; $i++) {
                $start_time = microtime(true);
                
                // Record interaction
                $analytics->recordInteraction(
                    'concurrent_test_' . $i,
                    'Concurrent test message ' . $i,
                    'Concurrent test response ' . $i,
                    1.0 + ($i * 0.2)
                );
                
                // Get stats
                $stats = $analytics->getInteractionStats();
                
                $operation_time = (microtime(true) - $start_time) * 1000;
                $operation_times[] = $operation_time;
                
                echo "<div class='metric'>Operation " . ($i + 1) . ": " . number_format($operation_time, 2) . "ms</div>";
            }
            
            $avg_time = array_sum($operation_times) / count($operation_times);
            $max_time = max($operation_times);
            
            echo "<div class='metric'>Average Time: " . number_format($avg_time, 2) . "ms</div>";
            echo "<div class='metric'>Max Time: " . number_format($max_time, 2) . "ms</div>";
            
            // Concurrent analytics benchmarks
            if ($avg_time < 200 && $max_time < 500) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Handles concurrent analytics efficiently</div>";
                $this->addResult('concurrent_analytics', true, 'Concurrent analytics handling excellent');
            } elseif ($avg_time < 1000 && $max_time < 2000) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Concurrent analytics acceptable</div>";
                $this->addResult('concurrent_analytics', true, 'Concurrent analytics handling good');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Slow:</strong> Concurrent analytics need optimization</div>";
                $this->addResult('concurrent_analytics', false, 'Concurrent analytics handling slow');
            }
            
        } catch (Exception $e) {
            $this->addError('Concurrent analytics test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Concurrent analytics test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
    /**
     * Test analytics cleanup
     */
    private function testAnalyticsCleanup() {
        echo "<div class='test-section'>";
        echo "<h3">🧹 Analytics Cleanup Test</h3>";
        
        try {
            global $wpdb;
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            echo "<div class='analytics-chart'>";
            echo "<h4>📊 Cleanup Results:</h4>";
            
            // Count analytics test records before cleanup
            $before_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' OR session_id LIKE 'class_test_%' OR session_id LIKE 'concurrent_test_%'");
            echo "<div class='metric'>Records Before: " . number_format($before_count) . "</div>";
            
            // Perform cleanup
            $deleted = $wpdb->query("DELETE FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' OR session_id LIKE 'class_test_%' OR session_id LIKE 'concurrent_test_%'");
            
            // Count analytics test records after cleanup  
            $after_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE session_id LIKE 'analytics_test_%' OR session_id LIKE 'class_test_%' OR session_id LIKE 'concurrent_test_%'");
            echo "<div class='metric'>Records After: " . number_format($after_count) . "</div>";
            echo "<div class='metric'>Records Deleted: " . number_format($deleted ?: 0) . "</div>";
            
            // Verify cleanup
            if ($after_count == 0 && $before_count > 0) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All analytics test data cleaned up successfully</div>";
                $this->addResult('analytics_cleanup', true, "Cleaned up {$before_count} analytics test records");
            } elseif ($after_count < $before_count) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Partial:</strong> Some analytics test data remains</div>";
                $this->addResult('analytics_cleanup', true, "Partial cleanup: {$deleted} records removed");
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Failed:</strong> Analytics cleanup did not work</div>";
                $this->addResult('analytics_cleanup', false, 'Analytics cleanup failed');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Analytics cleanup test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Analytics cleanup test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Get database version
     */
    private function getDatabaseVersion() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
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
        echo "<h2>📋 Analytics System Test Results Summary</h2>";
        
        $total_tests = count($this->results);
        $passed_tests = array_filter($this->results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        
        echo "<h3>📊 Overall Analytics System Status:</h3>";
        
        if (count($this->errors) === 0) {
            if (count($this->warnings) === 0) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #155724; margin: 0;'>🎉 ANALYTICS SYSTEM EXCELLENT</h4>";
                echo "<p style='color: #155724; margin: 5px 0 0 0;'>All analytics functionality optimized for large-scale data handling.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #856404; margin: 0;'>⚠️ ANALYTICS SYSTEM GOOD WITH OPPORTUNITIES</h4>";
                echo "<p style='color: #856404; margin: 5px 0 0 0;'>Analytics system works well but some areas could be optimized.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ ANALYTICS SYSTEM ISSUES DETECTED</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Analytics system requires optimization before production use.</p>";
            echo "</div>";
        }
        
        echo "<p><strong>Analytics Tests Passed:</strong> $passed_count / $total_tests</p>";
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ Analytics System Issues (" . count($this->errors) . "):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ Analytics System Warnings (" . count($this->warnings) . "):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>📊 Analytics System Benchmarks:</h3>";
        echo "<ul>";
        echo "<li><strong>Query Performance:</strong> Target <100ms average for complex queries</li>";
        echo "<li><strong>Large Dataset:</strong> Target 50K+ records with sub-second aggregations</li>";
        echo "<li><strong>Statistics:</strong> All calculations complete in <500ms</li>";
        echo "<li><strong>Concurrent Operations:</strong> Multiple analytics operations <200ms avg</li>";
        echo "<li><strong>Index Usage:</strong> Key indexes on timestamp and session_id</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIAnalyticsSystemTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_analytics_system_test() {
    $test = new GaryAIAnalyticsSystemTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 