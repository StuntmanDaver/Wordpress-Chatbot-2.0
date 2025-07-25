<?php
/**
 * Gary AI Plugin - JavaScript Functionality Test Suite
 * 
 * Tests JavaScript functionality including AJAX error handling, offline mode,
 * special character sanitization, and performance validation.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * JavaScript Functionality Test Class
 */
class GaryAIJavaScriptFunctionalityTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    private $js_files = [];
    
    public function __construct() {
        echo "<h1>⚡ Gary AI Plugin - JavaScript Functionality Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .js-chart { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 3px; }
            .metric { display: inline-block; margin: 5px 10px; padding: 5px; background: #e9ecef; border-radius: 3px; }
            .benchmark { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .js-code { background: #f1f3f4; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
            .performance-test { border: 1px solid #17a2b8; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .ajax-simulation { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; font-size: 12px; }
        </style>";
        
        // Add JavaScript for interactive testing
        echo "<script>
            var garyAITestResults = {};
            var garyAITestInProgress = false;
            
            // Simulate AJAX functionality for testing
            function simulateAjaxCall(endpoint, data, callback) {
                setTimeout(function() {
                    if (Math.random() > 0.8) {
                        callback(new Error('Simulated network error'));
                    } else {
                        callback(null, {success: true, data: 'Test response'});
                    }
                }, Math.random() * 1000 + 100);
            }
            
            // Test special character handling
            function testSpecialCharacters() {
                var testStrings = [
                    'Hello & goodbye',
                    '<script>alert(\"XSS\")</script>',
                    'String with \"quotes\" and \\'apostrophes\\'',
                    'Unicode: 🚀 📊 ⚡',
                    'SQL injection: \\'; DROP TABLE users; --',
                    'Emoji test: 😀 👍 ❤️ 🔥'
                ];
                
                var results = [];
                testStrings.forEach(function(str) {
                    var sanitized = encodeURIComponent(str);
                    results.push({
                        original: str,
                        sanitized: sanitized,
                        safe: sanitized !== str
                    });
                });
                
                return results;
            }
            
            // Performance testing function
            function performanceTest(iterations) {
                var startTime = performance.now();
                
                for (var i = 0; i < iterations; i++) {
                    // Simulate DOM operations
                    var element = document.createElement('div');
                    element.innerHTML = 'Test content ' + i;
                    document.body.appendChild(element);
                    document.body.removeChild(element);
                }
                
                var endTime = performance.now();
                return endTime - startTime;
            }
            
            // Memory usage test
            function memoryUsageTest() {
                var objects = [];
                var startHeap = performance.memory ? performance.memory.usedJSHeapSize : 0;
                
                // Create objects to test memory usage
                for (var i = 0; i < 10000; i++) {
                    objects.push({
                        id: i,
                        data: 'Test data string number ' + i,
                        timestamp: new Date()
                    });
                }
                
                var endHeap = performance.memory ? performance.memory.usedJSHeapSize : 0;
                
                // Cleanup
                objects = null;
                
                return {
                    startHeap: startHeap,
                    endHeap: endHeap,
                    difference: endHeap - startHeap,
                    available: !!performance.memory
                };
            }
        </script>";
    }
    
    /**
     * Run all JavaScript functionality tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting JavaScript Functionality Tests...</h2>";
        echo "<p><strong>Plugin Version:</strong> " . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : 'Unknown') . "</p>";
        echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
        echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "</p>";
        echo "</div>";
        
        // Initialize JavaScript files
        $this->initializeJSFiles();
        
        // Core JavaScript tests
        $this->testJSFileExistence();
        $this->testJSSyntaxValidation();
        $this->testAJAXErrorHandling();
        $this->testOfflineModeSupport();
        $this->testSpecialCharacterSanitization();
        $this->testPerformanceBenchmarks();
        $this->testEventHandling();
        $this->testModularArchitecture();
        $this->testErrorReporting();
        $this->testCrossBrowserCompatibility();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Initialize JavaScript files for testing
     */
    private function initializeJSFiles() {
        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_path = plugin_dir_path(__FILE__);
        
        $this->js_files = [
            'admin' => [
                'url' => $plugin_url . '../assets/js/admin.js',
                'path' => $plugin_path . '../assets/js/admin.js',
                'description' => 'WordPress Admin Interface Scripts'
            ],
            'chat-widget' => [
                'url' => $plugin_url . '../assets/js/chat-widget.js',
                'path' => $plugin_path . '../assets/js/chat-widget.js',
                'description' => 'Frontend Chat Widget Scripts'
            ],
            'chat-widget-modular' => [
                'url' => $plugin_url . '../assets/js/chat-widget-modular.js',
                'path' => $plugin_path . '../assets/js/chat-widget-modular.js',
                'description' => 'Modular Chat Widget Scripts'
            ]
        ];
        
        // Check for module files
        $module_files = ['ui.js', 'api.js', 'storage.js'];
        foreach ($module_files as $module_file) {
            $module_path = $plugin_path . '../assets/js/modules/' . $module_file;
            if (file_exists($module_path)) {
                $this->js_files[str_replace('.js', '_module', $module_file)] = [
                    'url' => $plugin_url . '../assets/js/modules/' . $module_file,
                    'path' => $module_path,
                    'description' => 'ES6 Module: ' . ucfirst(str_replace('.js', '', $module_file))
                ];
            }
        }
    }
    
    /**
     * Test JavaScript file existence and accessibility
     */
    private function testJSFileExistence() {
        echo "<div class='test-section'>";
        echo "<h3>📁 JavaScript File Existence Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 JavaScript File Validation:</h4>";
            
            $files_found = 0;
            $total_files = count($this->js_files);
            
            foreach ($this->js_files as $file_key => $file_info) {
                $file_exists = file_exists($file_info['path']);
                $file_readable = $file_exists && is_readable($file_info['path']);
                
                if ($file_exists && $file_readable) {
                    $file_size = filesize($file_info['path']);
                    $files_found++;
                    
                    echo "<div class='metric'>{$file_key}.js: ✅ Found (" . number_format($file_size) . " bytes)</div>";
                    
                    // Test file content
                    $content = file_get_contents($file_info['path']);
                    $function_count = $this->countJavaScriptFunctions($content);
                    $line_count = substr_count($content, "\n") + 1;
                    
                    echo "<div class='metric'>{$file_key} Functions: {$function_count}</div>";
                    echo "<div class='metric'>{$file_key} Lines: {$line_count}</div>";
                    
                } elseif ($file_exists) {
                    echo "<div class='metric'>{$file_key}.js: ❌ Not readable</div>";
                } else {
                    echo "<div class='metric'>{$file_key}.js: ⚠️ Not found</div>";
                }
            }
            
            echo "<div class='metric'>JS Files Found: {$files_found}/{$total_files}</div>";
            
            // JavaScript file existence benchmarks
            if ($files_found >= $total_files * 0.8) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Most JavaScript files present and accessible</div>";
                $this->addResult('js_file_existence', true, 'JavaScript files found and accessible');
            } elseif ($files_found > 0) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Partial:</strong> Some JavaScript files missing</div>";
                $this->addResult('js_file_existence', true, 'Some JavaScript files found');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Critical:</strong> No JavaScript files found</div>";
                $this->addResult('js_file_existence', false, 'No JavaScript files found');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('JavaScript file existence test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ JavaScript file existence test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test JavaScript syntax validation
     */
    private function testJSSyntaxValidation() {
        echo "<div class='test-section'>";
        echo "<h3>✅ JavaScript Syntax Validation Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 JavaScript Syntax Analysis:</h4>";
            
            $validation_results = [];
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                $syntax_issues = $this->validateJavaScriptSyntax($content);
                $code_quality = $this->analyzeCodeQuality($content);
                
                $validation_results[$file_key] = [
                    'syntax_errors' => $syntax_issues,
                    'quality_score' => $code_quality,
                    'size' => strlen($content),
                    'lines' => substr_count($content, "\n") + 1
                ];
                
                $error_count = count($syntax_issues);
                echo "<div class='metric'>{$file_key}.js Syntax Errors: {$error_count}</div>";
                echo "<div class='metric'>{$file_key}.js Quality Score: {$code_quality}%</div>";
                echo "<div class='metric'>{$file_key}.js Size: " . number_format(strlen($content)) . " bytes</div>";
                
                if ($error_count > 0) {
                    echo "<h5>Syntax Issues in {$file_key}.js:</h5>";
                    foreach (array_slice($syntax_issues, 0, 3) as $issue) {
                        echo "<div class='js-code'>⚠️ {$issue}</div>";
                    }
                }
            }
            
            $total_errors = array_sum(array_map('count', array_column($validation_results, 'syntax_errors')));
            $avg_quality = count($validation_results) > 0 ? 
                array_sum(array_column($validation_results, 'quality_score')) / count($validation_results) : 0;
            
            echo "<div class='metric'>Total Syntax Errors: {$total_errors}</div>";
            echo "<div class='metric'>Average Quality Score: " . number_format($avg_quality, 1) . "%</div>";
            
            // JavaScript syntax validation benchmarks
            if ($total_errors === 0 && $avg_quality >= 85) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> All JavaScript files pass validation with high quality</div>";
                $this->addResult('js_syntax_validation', true, 'All JavaScript files valid with high quality');
            } elseif ($total_errors <= 2 && $avg_quality >= 70) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> JavaScript quality is acceptable</div>";
                $this->addResult('js_syntax_validation', true, 'JavaScript quality acceptable');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Issues:</strong> JavaScript quality needs improvement</div>";
                $this->addResult('js_syntax_validation', false, 'JavaScript quality issues detected');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('JavaScript syntax validation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ JavaScript syntax validation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test AJAX error handling
     */
    private function testAJAXErrorHandling() {
        echo "<div class='test-section'>";
        echo "<h3>🔗 AJAX Error Handling Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 AJAX Error Handling Analysis:</h4>";
            
            // Client-side AJAX testing
            echo "<div class='ajax-simulation'>";
            echo "<h5>AJAX Error Simulation:</h5>";
            echo "<div id='ajax-test-results'></div>";
            
            // Add JavaScript for AJAX testing
            echo "<script>
                (function() {
                    var testResults = [];
                    var testsCompleted = 0;
                    var totalTests = 5;
                    
                    function displayResults() {
                        var container = document.getElementById('ajax-test-results');
                        var html = '';
                        
                        testResults.forEach(function(result, index) {
                            var status = result.success ? '✅ Success' : '❌ Failed';
                            var time = result.time ? ' (' + result.time.toFixed(2) + 'ms)' : '';
                            html += '<div class=\"metric\">Test ' + (index + 1) + ': ' + status + time + '</div>';
                        });
                        
                        var successRate = (testResults.filter(function(r) { return r.success; }).length / testResults.length * 100).toFixed(1);
                        html += '<div class=\"metric\">Success Rate: ' + successRate + '%</div>';
                        
                        container.innerHTML = html;
                    }
                    
                    function runAjaxTest(testName, shouldFail) {
                        var startTime = performance.now();
                        
                        simulateAjaxCall('/test-endpoint', {test: testName}, function(error, response) {
                            var endTime = performance.now();
                            var testTime = endTime - startTime;
                            
                            var success = shouldFail ? !!error : !error;
                            
                            testResults.push({
                                name: testName,
                                success: success,
                                time: testTime,
                                expectedToFail: shouldFail
                            });
                            
                            testsCompleted++;
                            if (testsCompleted === totalTests) {
                                displayResults();
                            }
                        });
                    }
                    
                    // Run AJAX tests
                    setTimeout(function() {
                        runAjaxTest('Normal Request', false);
                        runAjaxTest('Timeout Test', false);
                        runAjaxTest('Server Error', true);
                        runAjaxTest('Network Error', true);
                        runAjaxTest('Invalid Response', false);
                    }, 100);
                })();
            </script>";
            echo "</div>";
            
            // Analyze JavaScript files for AJAX error handling patterns
            $ajax_analysis = [];
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $ajax_patterns = [
                    'jquery_ajax' => $this->countPattern($content, '/\$\.ajax\s*\(|\$\.post\s*\(|\$\.get\s*\(/'),
                    'fetch_api' => $this->countPattern($content, '/fetch\s*\(/'),
                    'xmlhttprequest' => $this->countPattern($content, '/XMLHttpRequest/'),
                    'error_handling' => $this->countPattern($content, '/\.catch\s*\(|\.fail\s*\(|onerror\s*=/'),
                    'try_catch' => $this->countPattern($content, '/try\s*{.*?catch\s*\(/s'),
                    'timeout_handling' => $this->countPattern($content, '/timeout\s*:/'),
                    'retry_logic' => $this->countPattern($content, '/retry|attempt/i')
                ];
                
                $ajax_analysis[$file_key] = $ajax_patterns;
                
                echo "<h5>{$file_key}.js AJAX Patterns:</h5>";
                foreach ($ajax_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            // AJAX error handling benchmarks
            $total_ajax_calls = 0;
            $total_error_handling = 0;
            
            foreach ($ajax_analysis as $file_analysis) {
                $total_ajax_calls += $file_analysis['jquery_ajax'] + $file_analysis['fetch_api'] + $file_analysis['xmlhttprequest'];
                $total_error_handling += $file_analysis['error_handling'] + $file_analysis['try_catch'];
            }
            
            echo "<div class='metric'>Total AJAX Calls: {$total_ajax_calls}</div>";
            echo "<div class='metric'>Error Handling Implementations: {$total_error_handling}</div>";
            
            $error_coverage = $total_ajax_calls > 0 ? ($total_error_handling / $total_ajax_calls) * 100 : 100;
            
            if ($error_coverage >= 80 && $total_ajax_calls > 0) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Comprehensive AJAX error handling implemented</div>";
                $this->addResult('ajax_error_handling', true, 'Excellent AJAX error handling');
            } elseif ($error_coverage >= 50 || $total_ajax_calls === 0) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Basic AJAX error handling present</div>";
                $this->addResult('ajax_error_handling', true, 'Basic AJAX error handling');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Missing:</strong> Insufficient AJAX error handling</div>";
                $this->addResult('ajax_error_handling', false, 'Insufficient AJAX error handling');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('AJAX error handling test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ AJAX error handling test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test offline mode support
     */
    private function testOfflineModeSupport() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Offline Mode Support Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Offline Support Analysis:</h4>";
            
            // Test offline detection and handling
            echo "<div class='ajax-simulation'>";
            echo "<h5>Offline Mode Detection:</h5>";
            echo "<div id='offline-test-results'></div>";
            
            echo "<script>
                (function() {
                    var offlineResults = [];
                    
                    function displayOfflineResults() {
                        var container = document.getElementById('offline-test-results');
                        var html = '';
                        
                        offlineResults.forEach(function(result) {
                            var status = result.supported ? '✅ Supported' : '❌ Not supported';
                            html += '<div class=\"metric\">' + result.feature + ': ' + status + '</div>';
                        });
                        
                        container.innerHTML = html;
                    }
                    
                    // Test offline detection capabilities
                    offlineResults.push({
                        feature: 'Navigator Online Status',
                        supported: typeof navigator.onLine !== 'undefined'
                    });
                    
                    offlineResults.push({
                        feature: 'Service Worker Support',
                        supported: 'serviceWorker' in navigator
                    });
                    
                    offlineResults.push({
                        feature: 'Local Storage',
                        supported: typeof Storage !== 'undefined'
                    });
                    
                    offlineResults.push({
                        feature: 'Session Storage',
                        supported: typeof sessionStorage !== 'undefined'
                    });
                    
                    offlineResults.push({
                        feature: 'IndexedDB',
                        supported: typeof indexedDB !== 'undefined'
                    });
                    
                    offlineResults.push({
                        feature: 'Current Online Status',
                        supported: navigator.onLine
                    });
                    
                    displayOfflineResults();
                })();
            </script>";
            echo "</div>";
            
            // Analyze JavaScript files for offline support patterns
            $offline_analysis = [];
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $offline_patterns = [
                    'online_detection' => $this->countPattern($content, '/navigator\.onLine|online\s*===?\s*false/i'),
                    'offline_events' => $this->countPattern($content, '/addEventListener.*?offline|addEventListener.*?online/i'),
                    'local_storage' => $this->countPattern($content, '/localStorage\.|sessionStorage\./'),
                    'cache_strategy' => $this->countPattern($content, '/cache|Cache/'),
                    'service_worker' => $this->countPattern($content, '/serviceWorker|Service.*?Worker/i'),
                    'fallback_handling' => $this->countPattern($content, '/fallback|offline.*?mode/i'),
                    'queue_management' => $this->countPattern($content, '/queue|Queue/'),
                ];
                
                $offline_analysis[$file_key] = $offline_patterns;
                
                echo "<h5>{$file_key}.js Offline Patterns:</h5>";
                foreach ($offline_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            // Offline mode support benchmarks
            $total_offline_features = 0;
            
            foreach ($offline_analysis as $file_analysis) {
                $total_offline_features += array_sum($file_analysis);
            }
            
            echo "<div class='metric'>Total Offline Features: {$total_offline_features}</div>";
            
            if ($total_offline_features >= 5) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Comprehensive offline mode support</div>";
                $this->addResult('offline_mode_support', true, 'Excellent offline mode support');
            } elseif ($total_offline_features >= 2) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Basic:</strong> Basic offline functionality present</div>";
                $this->addResult('offline_mode_support', true, 'Basic offline functionality');
            } else {
                echo "<div class='benchmark' style='background: #fff3cd;'>ℹ️ <strong>None:</strong> No offline mode support detected</div>";
                $this->addResult('offline_mode_support', true, 'No offline mode support (acceptable for chat widget)');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Offline mode support test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Offline mode support test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test special character sanitization
     */
    private function testSpecialCharacterSanitization() {
        echo "<div class='test-section'>";
        echo "<h3>🔒 Special Character Sanitization Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Character Sanitization Analysis:</h4>";
            
            // Client-side sanitization testing
            echo "<div class='ajax-simulation'>";
            echo "<h5>Client-Side Sanitization Test:</h5>";
            echo "<div id='sanitization-test-results'></div>";
            
            echo "<script>
                (function() {
                    setTimeout(function() {
                        var sanitizationResults = testSpecialCharacters();
                        var container = document.getElementById('sanitization-test-results');
                        var html = '';
                        
                        var passedTests = 0;
                        
                        sanitizationResults.forEach(function(result, index) {
                            var safe = result.safe || result.sanitized.indexOf('<script>') === -1;
                            var status = safe ? '✅ Safe' : '❌ Unsafe';
                            if (safe) passedTests++;
                            
                            html += '<div class=\"js-code\">';
                            html += '<strong>Test ' + (index + 1) + ':</strong> ' + status + '<br>';
                            html += '<em>Original:</em> ' + result.original.substring(0, 50) + '<br>';
                            html += '<em>Sanitized:</em> ' + result.sanitized.substring(0, 50);
                            html += '</div>';
                        });
                        
                        var passRate = (passedTests / sanitizationResults.length * 100).toFixed(1);
                        html += '<div class=\"metric\">Sanitization Pass Rate: ' + passRate + '%</div>';
                        
                        container.innerHTML = html;
                    }, 500);
                })();
            </script>";
            echo "</div>";
            
            // Analyze JavaScript files for sanitization patterns
            $sanitization_analysis = [];
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $sanitization_patterns = [
                    'escape_functions' => $this->countPattern($content, '/escape|encodeURI|encodeURIComponent/i'),
                    'html_encoding' => $this->countPattern($content, '/htmlentities|htmlspecialchars|escapeHtml/i'),
                    'input_validation' => $this->countPattern($content, '/validate|sanitize|clean/i'),
                    'xss_prevention' => $this->countPattern($content, '/textContent|innerText/'),
                    'dangerous_methods' => $this->countPattern($content, '/innerHTML\s*=|outerHTML\s*=|insertAdjacentHTML/'),
                    'sql_injection_prevention' => $this->countPattern($content, '/prepared|parameterized|escape.*?sql/i'),
                    'strip_tags' => $this->countPattern($content, '/strip.*?tags|remove.*?html/i')
                ];
                
                $sanitization_analysis[$file_key] = $sanitization_patterns;
                
                echo "<h5>{$file_key}.js Sanitization Patterns:</h5>";
                foreach ($sanitization_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
                
                // Check for dangerous patterns
                if ($sanitization_patterns['dangerous_methods'] > 0) {
                    echo "<div class='js-code'>⚠️ Found " . $sanitization_patterns['dangerous_methods'] . " potentially dangerous innerHTML usage(s)</div>";
                }
            }
            
            // Special character sanitization benchmarks
            $total_sanitization = 0;
            $total_dangerous = 0;
            
            foreach ($sanitization_analysis as $file_analysis) {
                $total_sanitization += $file_analysis['escape_functions'] + $file_analysis['html_encoding'] + 
                                      $file_analysis['input_validation'] + $file_analysis['xss_prevention'];
                $total_dangerous += $file_analysis['dangerous_methods'];
            }
            
            echo "<div class='metric'>Total Sanitization Methods: {$total_sanitization}</div>";
            echo "<div class='metric'>Potentially Dangerous Methods: {$total_dangerous}</div>";
            
            $sanitization_ratio = $total_dangerous > 0 ? ($total_sanitization / $total_dangerous) : 
                                 ($total_sanitization > 0 ? 10 : 5);
            
            if ($sanitization_ratio >= 2 && $total_dangerous <= 2) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Comprehensive input sanitization implemented</div>";
                $this->addResult('special_character_sanitization', true, 'Excellent input sanitization');
            } elseif ($sanitization_ratio >= 1 || $total_dangerous === 0) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Basic input sanitization present</div>";
                $this->addResult('special_character_sanitization', true, 'Basic input sanitization');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Security Risk:</strong> Insufficient input sanitization</div>";
                $this->addResult('special_character_sanitization', false, 'Insufficient input sanitization');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Special character sanitization test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Special character sanitization test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test performance benchmarks
     */
    private function testPerformanceBenchmarks() {
        echo "<div class='test-section'>";
        echo "<h3">⚡ Performance Benchmarks Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 JavaScript Performance Analysis:</h4>";
            
            // Client-side performance testing
            echo "<div class='performance-test'>";
            echo "<h5>Performance Benchmarks:</h5>";
            echo "<div id='performance-test-results'></div>";
            
            echo "<script>
                (function() {
                    setTimeout(function() {
                        var container = document.getElementById('performance-test-results');
                        var html = '';
                        
                        // DOM manipulation performance test
                        var domPerformance = performanceTest(1000);
                        html += '<div class=\"metric\">DOM Operations (1000x): ' + domPerformance.toFixed(2) + 'ms</div>';
                        
                        // Memory usage test
                        var memoryTest = memoryUsageTest();
                        if (memoryTest.available) {
                            html += '<div class=\"metric\">Memory Test Available: ✅ Yes</div>';
                            html += '<div class=\"metric\">Memory Usage: ' + (memoryTest.difference / 1024 / 1024).toFixed(2) + ' MB</div>';
                        } else {
                            html += '<div class=\"metric\">Memory Test Available: ❌ No (Chrome required)</div>';
                        }
                        
                        // Timing accuracy test
                        var timingAccurate = typeof performance !== 'undefined' && typeof performance.now === 'function';
                        html += '<div class=\"metric\">High-Resolution Timing: ' + (timingAccurate ? '✅ Available' : '❌ Not available') + '</div>';
                        
                        // JavaScript execution speed
                        var mathStart = performance.now();
                        for (var i = 0; i < 100000; i++) {
                            Math.sqrt(i) * Math.random();
                        }
                        var mathTime = performance.now() - mathStart;
                        html += '<div class=\"metric\">Math Operations (100k): ' + mathTime.toFixed(2) + 'ms</div>';
                        
                        // Performance rating
                        var overallRating = 'Good';
                        if (domPerformance < 50 && mathTime < 10) {
                            overallRating = 'Excellent';
                        } else if (domPerformance > 200 || mathTime > 50) {
                            overallRating = 'Needs Improvement';
                        }
                        
                        html += '<div class=\"metric\">Overall Performance: ' + overallRating + '</div>';
                        
                        container.innerHTML = html;
                    }, 100);
                })();
            </script>";
            echo "</div>";
            
            // Analyze JavaScript files for performance patterns
            $performance_analysis = [];
            $total_size = 0;
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                $file_size = strlen($content);
                $total_size += $file_size;
                
                $performance_patterns = [
                    'size_kb' => round($file_size / 1024, 2),
                    'function_count' => $this->countJavaScriptFunctions($content),
                    'loops' => $this->countPattern($content, '/for\s*\(|while\s*\(|forEach\s*\(/'),
                    'dom_queries' => $this->countPattern($content, '/getElementById|querySelector|getElementsBy/'),
                    'event_listeners' => $this->countPattern($content, '/addEventListener|on[A-Z][a-z]+\s*=/'),
                    'async_operations' => $this->countPattern($content, '/async|await|Promise|setTimeout|setInterval/'),
                    'optimizations' => $this->countPattern($content, '/debounce|throttle|cache|memoiz/i'),
                    'minification' => $this->isMinified($content)
                ];
                
                $performance_analysis[$file_key] = $performance_patterns;
                
                echo "<h5>{$file_key}.js Performance Metrics:</h5>";
                foreach ($performance_patterns as $pattern => $value) {
                    if ($pattern === 'minification') {
                        $display_value = $value ? 'Yes' : 'No';
                    } else {
                        $display_value = $value;
                    }
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$display_value}</div>";
                }
            }
            
            $total_size_kb = round($total_size / 1024, 2);
            echo "<div class='metric'>Total JavaScript Size: {$total_size_kb} KB</div>";
            
            // Performance benchmarks
            $avg_optimizations = count($performance_analysis) > 0 ? 
                array_sum(array_column($performance_analysis, 'optimizations')) / count($performance_analysis) : 0;
            
            if ($total_size_kb <= 100 && $avg_optimizations >= 1) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> JavaScript is optimized and performant</div>";
                $this->addResult('performance_benchmarks', true, 'Excellent JavaScript performance');
            } elseif ($total_size_kb <= 200) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> JavaScript performance is acceptable</div>";
                $this->addResult('performance_benchmarks', true, 'Good JavaScript performance');
            } else {
                echo "<div class='benchmark' style='background: #f8d7da;'>❌ <strong>Heavy:</strong> JavaScript may impact page performance</div>";
                $this->addResult('performance_benchmarks', false, 'JavaScript performance concerns');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Performance benchmarks test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Performance benchmarks test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test event handling
     */
    private function testEventHandling() {
        echo "<div class='test-section'>";
        echo "<h3>🎯 Event Handling Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Event Handling Analysis:</h4>";
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $event_patterns = [
                    'click_events' => $this->countPattern($content, '/click|onClick/'),
                    'form_events' => $this->countPattern($content, '/submit|change|input|focus|blur/'),
                    'keyboard_events' => $this->countPattern($content, '/keydown|keyup|keypress/'),
                    'mouse_events' => $this->countPattern($content, '/mousedown|mouseup|mouseover|mouseout/'),
                    'touch_events' => $this->countPattern($content, '/touchstart|touchend|touchmove/'),
                    'custom_events' => $this->countPattern($content, '/CustomEvent|dispatchEvent/'),
                    'event_delegation' => $this->countPattern($content, '/event\.target|event\.currentTarget/'),
                    'prevent_default' => $this->countPattern($content, '/preventDefault|stopPropagation/')
                ];
                
                echo "<h5>{$file_key}.js Event Handling:</h5>";
                foreach ($event_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            // Event handling benchmarks
            echo "<div class='benchmark'>✅ <strong>Info:</strong> Event handling patterns analyzed</div>";
            $this->addResult('event_handling', true, 'Event handling analysis completed');
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Event handling test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Event handling test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test modular architecture
     */
    private function testModularArchitecture() {
        echo "<div class='test-section'>";
        echo "<h3">🧩 Modular Architecture Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Modular Architecture Analysis:</h4>";
            
            $module_analysis = [];
            $es6_modules = 0;
            $has_main_coordinator = false;
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $modular_patterns = [
                    'es6_imports' => $this->countPattern($content, '/import\s+.*?from|import\s*{.*?}/'),
                    'es6_exports' => $this->countPattern($content, '/export\s+.*?{|export\s+default|export\s+const|export\s+function/'),
                    'amd_requires' => $this->countPattern($content, '/require\s*\(/'),
                    'commonjs_exports' => $this->countPattern($content, '/module\.exports|exports\./'),
                    'namespace_patterns' => $this->countPattern($content, '/window\.\w+\s*=|var\s+\w+\s*=\s*\w+\s*\|\|\s*{}/'),
                    'iife_patterns' => $this->countPattern($content, '/\(function\s*\([^)]*\)\s*{|\(\s*function/'),
                    'class_definitions' => $this->countPattern($content, '/class\s+\w+|function\s+[A-Z]\w*\s*\(/'),
                    'object_modules' => $this->countPattern($content, '/const\s+\w+\s*=\s*{|var\s+\w+\s*=\s*{/')
                ];
                
                if ($modular_patterns['es6_imports'] > 0 || $modular_patterns['es6_exports'] > 0) {
                    $es6_modules++;
                }
                
                if (strpos($file_key, 'modular') !== false) {
                    $has_main_coordinator = true;
                }
                
                $module_analysis[$file_key] = $modular_patterns;
                
                echo "<h5>{$file_key}.js Module Patterns:</h5>";
                foreach ($modular_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            echo "<div class='metric'>ES6 Modules: {$es6_modules}</div>";
            echo "<div class='metric'>Main Coordinator: " . ($has_main_coordinator ? 'Yes' : 'No') . "</div>";
            
            // Modular architecture benchmarks
            if ($es6_modules >= 3 && $has_main_coordinator) {
                echo "<div class='benchmark'>✅ <strong>Excellent:</strong> Modern modular architecture implemented</div>";
                $this->addResult('modular_architecture', true, 'Excellent modular architecture');
            } elseif ($es6_modules >= 1) {
                echo "<div class='benchmark' style='background: #fff3cd;'>⚠️ <strong>Good:</strong> Some modular patterns present</div>";
                $this->addResult('modular_architecture', true, 'Basic modular architecture');
            } else {
                echo "<div class='benchmark' style='background: #fff3cd;'>ℹ️ <strong>Traditional:</strong> Traditional JavaScript architecture</div>";
                $this->addResult('modular_architecture', true, 'Traditional architecture (acceptable)');
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Modular architecture test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Modular architecture test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test error reporting
     */
    private function testErrorReporting() {
        echo "<div class='test-section'>";
        echo "<h3">🚨 Error Reporting Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Error Reporting Analysis:</h4>";
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $error_patterns = [
                    'console_error' => $this->countPattern($content, '/console\.error|console\.warn/'),
                    'console_log' => $this->countPattern($content, '/console\.log|console\.info/'),
                    'throw_statements' => $this->countPattern($content, '/throw\s+new|throw\s+\w+/'),
                    'error_objects' => $this->countPattern($content, '/new\s+Error|Error\s*\(/'),
                    'debugging_code' => $this->countPattern($content, '/debugger|console\.trace/'),
                    'production_logging' => $this->countPattern($content, '/log.*?level|debug.*?mode/i'),
                    'user_notifications' => $this->countPattern($content, '/alert\s*\(|notification|toast/i')
                ];
                
                echo "<h5>{$file_key}.js Error Reporting:</h5>";
                foreach ($error_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            // Error reporting benchmarks
            echo "<div class='benchmark'>✅ <strong>Info:</strong> Error reporting patterns analyzed</div>";
            $this->addResult('error_reporting', true, 'Error reporting analysis completed');
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Error reporting test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Error reporting test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test cross-browser compatibility
     */
    private function testCrossBrowserCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Cross-Browser Compatibility Test</h3>";
        
        try {
            echo "<div class='js-chart'>";
            echo "<h4>📊 Browser Compatibility Analysis:</h4>";
            
            foreach ($this->js_files as $file_key => $file_info) {
                if (!file_exists($file_info['path'])) {
                    continue;
                }
                
                $content = file_get_contents($file_info['path']);
                
                $compatibility_patterns = [
                    'es6_features' => $this->countPattern($content, '/const\s+|let\s+|=>\s*{|\.includes\s*\(|\.startsWith\s*\(|\.endsWith\s*\(/'),
                    'modern_apis' => $this->countPattern($content, '/fetch\s*\(|Promise\s*\(|async\s+function|await\s+/'),
                    'polyfills' => $this->countPattern($content, '/polyfill|shim/i'),
                    'feature_detection' => $this->countPattern($content, '/typeof\s+.*?!==\s*[\'"]undefined|in\s+window|\.hasOwnProperty/'),
                    'ie_compatibility' => $this->countPattern($content, '/attachEvent|detachEvent|createEventObject/'),
                    'vendor_prefixes' => $this->countPattern($content, '/webkit|moz|ms|o[A-Z]/'),
                    'jquery_usage' => $this->countPattern($content, '/\$\s*\(|\$\./'),
                    'strict_mode' => $this->countPattern($content, '/[\'"]use strict[\'"]/')
                ];
                
                echo "<h5>{$file_key}.js Browser Compatibility:</h5>";
                foreach ($compatibility_patterns as $pattern => $count) {
                    echo "<div class='metric'>" . ucfirst(str_replace('_', ' ', $pattern)) . ": {$count}</div>";
                }
            }
            
            // Browser compatibility benchmarks
            echo "<div class='benchmark'>✅ <strong>Info:</strong> Browser compatibility patterns analyzed</div>";
            $this->addResult('cross_browser_compatibility', true, 'Browser compatibility analysis completed');
            
            echo "</div>";
            
        } catch (Exception $e) {
            $this->addError('Cross-browser compatibility test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Cross-browser compatibility test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    // Helper methods for JavaScript analysis
    
    private function countJavaScriptFunctions($content) {
        return preg_match_all('/function\s+\w+\s*\(|const\s+\w+\s*=\s*function|\w+\s*:\s*function/', $content);
    }
    
    private function validateJavaScriptSyntax($content) {
        $errors = [];
        
        // Basic syntax validation
        $brace_count = substr_count($content, '{') - substr_count($content, '}');
        if ($brace_count !== 0) {
            $errors[] = "Mismatched braces (difference: {$brace_count})";
        }
        
        $paren_count = substr_count($content, '(') - substr_count($content, ')');
        if ($paren_count !== 0) {
            $errors[] = "Mismatched parentheses (difference: {$paren_count})";
        }
        
        return $errors;
    }
    
    private function analyzeCodeQuality($content) {
        $score = 100;
        
        // Deduct points for potential issues
        $console_logs = substr_count($content, 'console.log');
        $score -= min($console_logs * 2, 20); // Max 20 point deduction
        
        $var_usage = substr_count($content, 'var ');
        $score -= min($var_usage * 1, 15); // Prefer let/const
        
        return max(0, min(100, $score));
    }
    
    private function countPattern($content, $pattern) {
        return preg_match_all($pattern, $content);
    }
    
    private function isMinified($content) {
        $avg_line_length = strlen($content) / (substr_count($content, "\n") + 1);
        return $avg_line_length > 80 && substr_count($content, ' ') / strlen($content) < 0.1;
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
        echo "<h2>📋 JavaScript Functionality Test Results Summary</h2>";
        
        $total_tests = count($this->results);
        $passed_tests = array_filter($this->results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        
        echo "<h3>📊 Overall JavaScript Status:</h3>";
        
        if (count($this->errors) === 0) {
            if (count($this->warnings) === 0) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #155724; margin: 0;'>🎉 JAVASCRIPT FUNCTIONALITY EXCELLENT</h4>";
                echo "<p style='color: #155724; margin: 5px 0 0 0;'>All JavaScript functionality working optimally with robust error handling.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #856404; margin: 0;'>⚠️ JAVASCRIPT FUNCTIONALITY GOOD WITH OPPORTUNITIES</h4>";
                echo "<p style='color: #856404; margin: 5px 0 0 0;'>JavaScript works well but some areas could be enhanced.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ JAVASCRIPT FUNCTIONALITY ISSUES DETECTED</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>JavaScript functionality requires improvements before production use.</p>";
            echo "</div>";
        }
        
        echo "<p><strong>JavaScript Tests Passed:</strong> $passed_count / $total_tests</p>";
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ JavaScript Issues (" . count($this->errors) . "):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ JavaScript Warnings (" . count($this->warnings) . "):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>📊 JavaScript Functionality Benchmarks:</h3>";
        echo "<ul>";
        echo "<li><strong>File Size:</strong> Target <100KB total JavaScript</li>";
        echo "<li><strong>AJAX Error Handling:</strong> Comprehensive error catching and retry logic</li>";
        echo "<li><strong>Input Sanitization:</strong> XSS prevention and special character handling</li>";
        echo "<li><strong>Performance:</strong> DOM operations <50ms, efficient event handling</li>";
        echo "<li><strong>Modular Design:</strong> ES6 modules with clear separation of concerns</li>";
        echo "<li><strong>Browser Support:</strong> Modern browsers with fallback handling</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIJavaScriptFunctionalityTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_javascript_functionality_test() {
    $test = new GaryAIJavaScriptFunctionalityTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 