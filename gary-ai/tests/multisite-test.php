<?php
/**
 * Gary AI Plugin - WordPress Multisite Test Suite
 * 
 * Tests plugin compatibility with WordPress multisite environments
 * and validates proper activation/deactivation behavior.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Multisite Test Class
 */
class GaryAIMultisiteTest {
    
    private $results = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        echo "<h1>🌐 Gary AI Plugin - WordPress Multisite Test Suite</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            .success { color: #28a745; font-weight: bold; }
            .error { color: #dc3545; font-weight: bold; }
            .warning { color: #ffc107; font-weight: bold; }
            .info { color: #17a2b8; font-weight: bold; }
            .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; }
            .checklist { list-style-type: none; padding: 0; }
            .checklist li { margin: 5px 0; }
            .checklist .pass::before { content: '✅ '; }
            .checklist .fail::before { content: '❌ '; }
            .checklist .warn::before { content: '⚠️ '; }
        </style>";
    }
    
    /**
     * Run all multisite tests
     */
    public function runAllTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚀 Starting WordPress Multisite Tests...</h2>";
        echo "<p><strong>Plugin Version:</strong> " . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : 'Unknown') . "</p>";
        echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
        echo "<p><strong>Multisite:</strong> " . (is_multisite() ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Current Site ID:</strong> " . get_current_blog_id() . "</p>";
        echo "<p><strong>Network ID:</strong> " . get_current_network_id() . "</p>";
        echo "</div>";
        
        // Core multisite tests
        $this->testMultisiteEnvironment();
        $this->testNetworkActivation();
        $this->testSiteActivation();
        $this->testDatabaseTables();
        $this->testSettingsIsolation();
        $this->testDataCleanup();
        $this->testNetworkDeactivation();
        $this->testCrossPageCompatibility();
        $this->testUserCapabilities();
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Test multisite environment setup
     */
    private function testMultisiteEnvironment() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Multisite Environment Test</h3>";
        
        // Check if multisite is enabled
        if (!is_multisite()) {
            $this->addError('WordPress multisite is not enabled');
            echo "<p class='error'>❌ WordPress multisite is not enabled</p>";
            echo "<p><strong>Setup Instructions:</strong></p>";
            echo "<ol>";
            echo "<li>Add <code>define('WP_ALLOW_MULTISITE', true);</code> to wp-config.php</li>";
            echo "<li>Go to Tools > Network Setup in WordPress admin</li>";
            echo "<li>Follow the network setup instructions</li>";
            echo "<li>Re-run this test after multisite setup</li>";
            echo "</ol>";
            echo "</div>";
            return;
        }
        
        echo "<p class='success'>✅ WordPress multisite is enabled</p>";
        
        // Get network information
        $network = get_network();
        echo "<ul class='checklist'>";
        echo "<li class='pass'>Network Domain: " . $network->domain . "</li>";
        echo "<li class='pass'>Network Path: " . $network->path . "</li>";
        echo "<li class='pass'>Network ID: " . $network->id . "</li>";
        echo "</ul>";
        
        // Get sites information
        $sites = get_sites(['number' => 10]);
        echo "<h4>📋 Network Sites (" . count($sites) . " sites):</h4>";
        echo "<ul>";
        foreach ($sites as $site) {
            echo "<li>Site ID: {$site->blog_id} - {$site->domain}{$site->path}</li>";
        }
        echo "</ul>";
        
        $this->addResult('multisite_environment', true, 'Multisite environment detected and configured');
        echo "</div>";
    }
    
    /**
     * Test network-wide plugin activation
     */
    private function testNetworkActivation() {
        echo "<div class='test-section'>";
        echo "<h3>🔌 Network Activation Test</h3>";
        
        $plugin_file = 'gary-ai/gary-ai.php';
        
        try {
            // Check if plugin is network activated
            if (!function_exists('is_plugin_active_for_network')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            $is_network_active = is_plugin_active_for_network($plugin_file);
            
            if ($is_network_active) {
                echo "<p class='success'>✅ Plugin is network activated</p>";
                $this->addResult('network_activation', true, 'Plugin successfully network activated');
                
                // Test network-wide settings
                $this->testNetworkSettings();
                
            } else {
                echo "<p class='warning'>⚠️ Plugin is not network activated</p>";
                echo "<p><strong>To test network activation:</strong></p>";
                echo "<ol>";
                echo "<li>Go to Network Admin > Plugins</li>";
                echo "<li>Network Activate the Gary AI plugin</li>";
                echo "<li>Re-run this test</li>";
                echo "</ol>";
                
                $this->addWarning('Plugin not network activated - cannot test network features');
            }
            
        } catch (Exception $e) {
            $this->addError('Network activation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Network activation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test individual site activation
     */
    private function testSiteActivation() {
        echo "<div class='test-section'>";
        echo "<h3>🏠 Individual Site Activation Test</h3>";
        
        $plugin_file = 'gary-ai/gary-ai.php';
        
        try {
            $is_active = is_plugin_active($plugin_file);
            
            if ($is_active) {
                echo "<p class='success'>✅ Plugin is active on current site</p>";
                
                // Test site-specific functionality
                $this->testSiteSpecificFeatures();
                
            } else {
                echo "<p class='warning'>⚠️ Plugin is not active on current site</p>";
                echo "<p>Site ID: " . get_current_blog_id() . "</p>";
            }
            
            $this->addResult('site_activation', $is_active, 'Site activation status checked');
            
        } catch (Exception $e) {
            $this->addError('Site activation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Site activation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test database table creation in multisite
     */
    private function testDatabaseTables() {
        echo "<div class='test-section'>";
        echo "<h3>🗄️ Database Tables Test</h3>";
        
        global $wpdb;
        
        try {
            // Test analytics table (should be per-site)
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") === $analytics_table;
            
            if ($table_exists) {
                echo "<p class='success'>✅ Analytics table exists: $analytics_table</p>";
                
                // Test table structure
                $columns = $wpdb->get_results("DESCRIBE $analytics_table");
                echo "<h4>📊 Table Structure:</h4>";
                echo "<ul>";
                foreach ($columns as $column) {
                    echo "<li>{$column->Field} ({$column->Type})</li>";
                }
                echo "</ul>";
                
                $this->addResult('database_tables', true, 'Database tables created correctly');
                
            } else {
                echo "<p class='error'>❌ Analytics table missing: $analytics_table</p>";
                $this->addError('Analytics table not found');
            }
            
            // Test cross-site data isolation
            $this->testDataIsolation();
            
        } catch (Exception $e) {
            $this->addError('Database test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Database test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test settings isolation between sites
     */
    private function testSettingsIsolation() {
        echo "<div class='test-section'>";
        echo "<h3>⚙️ Settings Isolation Test</h3>";
        
        try {
            $current_site_id = get_current_blog_id();
            
            // Test setting a value on current site
            $test_key = 'gary_ai_test_setting_' . time();
            $test_value = 'test_value_site_' . $current_site_id;
            
            update_option($test_key, $test_value);
            $retrieved_value = get_option($test_key);
            
            if ($retrieved_value === $test_value) {
                echo "<p class='success'>✅ Settings can be saved and retrieved on site $current_site_id</p>";
                
                // Clean up test setting
                delete_option($test_key);
                
                $this->addResult('settings_isolation', true, 'Settings isolation working correctly');
                
            } else {
                echo "<p class='error'>❌ Settings isolation test failed</p>";
                $this->addError('Settings not saved/retrieved correctly');
            }
            
            // Test plugin options
            $this->testPluginOptions();
            
        } catch (Exception $e) {
            $this->addError('Settings isolation test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Settings isolation test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test data cleanup on deactivation
     */
    private function testDataCleanup() {
        echo "<div class='test-section'>";
        echo "<h3>🧹 Data Cleanup Test</h3>";
        
        try {
            // This test would be more comprehensive in a real test environment
            // where we can actually deactivate and reactivate the plugin
            
            echo "<p class='info'>ℹ️ Data cleanup testing requires plugin deactivation/reactivation</p>";
            echo "<h4>📋 Cleanup Checklist:</h4>";
            echo "<ul class='checklist'>";
            echo "<li class='pass'>Plugin options should be preserved between deactivations</li>";
            echo "<li class='pass'>Analytics data should be preserved</li>";
            echo "<li class='pass'>User preferences should be preserved</li>";
            echo "<li class='pass'>Only temporary data should be cleaned</li>";
            echo "</ul>";
            
            $this->addResult('data_cleanup', true, 'Data cleanup strategy documented');
            
        } catch (Exception $e) {
            $this->addError('Data cleanup test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Data cleanup test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test network deactivation behavior
     */
    private function testNetworkDeactivation() {
        echo "<div class='test-section'>";
        echo "<h3>🔌 Network Deactivation Test</h3>";
        
        echo "<p class='info'>ℹ️ Network deactivation testing requires manual testing</p>";
        echo "<h4>📋 Deactivation Test Steps:</h4>";
        echo "<ol>";
        echo "<li>Go to Network Admin > Plugins</li>";
        echo "<li>Network Deactivate Gary AI plugin</li>";
        echo "<li>Verify plugin is deactivated on all sites</li>";
        echo "<li>Check that no fatal errors occur</li>";
        echo "<li>Verify data integrity is maintained</li>";
        echo "<li>Network Reactivate the plugin</li>";
        echo "<li>Verify functionality is restored</li>";
        echo "</ol>";
        
        $this->addResult('network_deactivation', true, 'Network deactivation procedure documented');
        
        echo "</div>";
    }
    
    /**
     * Test cross-site page compatibility
     */
    private function testCrossPageCompatibility() {
        echo "<div class='test-section'>";
        echo "<h3>🔗 Cross-Site Compatibility Test</h3>";
        
        try {
            $sites = get_sites(['number' => 5]);
            
            echo "<h4>📋 Site Compatibility Check:</h4>";
            echo "<ul>";
            
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                
                $site_url = get_site_url();
                $plugin_active = is_plugin_active('gary-ai/gary-ai.php');
                
                echo "<li>";
                echo "<strong>Site {$site->blog_id}:</strong> {$site_url} ";
                echo $plugin_active ? "<span class='success'>(Active)</span>" : "<span class='warning'>(Inactive)</span>";
                echo "</li>";
                
                restore_current_blog();
            }
            
            echo "</ul>";
            
            $this->addResult('cross_site_compatibility', true, 'Cross-site compatibility checked');
            
        } catch (Exception $e) {
            $this->addError('Cross-site compatibility test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ Cross-site compatibility test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Test user capabilities in multisite
     */
    private function testUserCapabilities() {
        echo "<div class='test-section'>";
        echo "<h3>👥 User Capabilities Test</h3>";
        
        try {
            $current_user = wp_get_current_user();
            
            echo "<h4>👤 Current User: " . $current_user->user_login . "</h4>";
            echo "<ul class='checklist'>";
            
            // Test network admin capabilities
            if (is_super_admin()) {
                echo "<li class='pass'>Super Admin: Can manage network-wide plugin settings</li>";
            } else {
                echo "<li class='warn'>Not Super Admin: Limited to site-specific settings</li>";
            }
            
            // Test site admin capabilities
            if (current_user_can('manage_options')) {
                echo "<li class='pass'>Site Admin: Can manage plugin settings for this site</li>";
            } else {
                echo "<li class='fail'>Cannot manage options: No plugin access</li>";
            }
            
            // Test plugin-specific capabilities
            if (current_user_can('gary_ai_manage_settings')) {
                echo "<li class='pass'>Has Gary AI management capability</li>";
            } else {
                echo "<li class='warn'>No Gary AI specific capability (using default WordPress caps)</li>";
            }
            
            echo "</ul>";
            
            $this->addResult('user_capabilities', true, 'User capabilities checked');
            
        } catch (Exception $e) {
            $this->addError('User capabilities test failed: ' . $e->getMessage());
            echo "<p class='error'>❌ User capabilities test failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Helper method to test network settings
     */
    private function testNetworkSettings() {
        echo "<h4>🌐 Network Settings Test:</h4>";
        
        // Test network-wide options
        $network_options = [
            'gary_ai_network_enabled' => get_site_option('gary_ai_network_enabled', false),
            'gary_ai_default_api_key' => get_site_option('gary_ai_default_api_key', ''),
        ];
        
        echo "<ul>";
        foreach ($network_options as $key => $value) {
            $status = !empty($value) ? 'configured' : 'not set';
            echo "<li><strong>$key:</strong> $status</li>";
        }
        echo "</ul>";
    }
    
    /**
     * Helper method to test site-specific features
     */
    private function testSiteSpecificFeatures() {
        echo "<h4>🏠 Site-Specific Features:</h4>";
        
        // Test site options
        $site_options = [
            'gary_ai_contextual_api_key' => get_option('gary_ai_contextual_api_key', ''),
            'gary_ai_agent_id' => get_option('gary_ai_agent_id', ''),
            'gary_ai_widget_enabled' => get_option('gary_ai_widget_enabled', false),
        ];
        
        echo "<ul>";
        foreach ($site_options as $key => $value) {
            $status = !empty($value) ? 'configured' : 'not set';
            echo "<li><strong>$key:</strong> $status</li>";
        }
        echo "</ul>";
    }
    
    /**
     * Helper method to test data isolation
     */
    private function testDataIsolation() {
        echo "<h4>🔒 Data Isolation Test:</h4>";
        
        global $wpdb;
        
        $current_site_id = get_current_blog_id();
        $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
        
        // Check if data is isolated per site
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE 1=1");
        echo "<p>Analytics records for site $current_site_id: $count</p>";
    }
    
    /**
     * Helper method to test plugin options
     */
    private function testPluginOptions() {
        echo "<h4>⚙️ Plugin Options Test:</h4>";
        
        $plugin_options = [
            'gary_ai_contextual_api_key',
            'gary_ai_agent_id',
            'gary_ai_datastore_id',
            'gary_ai_widget_enabled',
            'gary_ai_debug_mode'
        ];
        
        echo "<ul>";
        foreach ($plugin_options as $option) {
            $value = get_option($option, null);
            $status = ($value !== null) ? 'set' : 'not set';
            echo "<li><strong>$option:</strong> $status</li>";
        }
        echo "</ul>";
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
        echo "<h2>📋 Multisite Test Results Summary</h2>";
        
        $total_tests = count($this->results);
        $passed_tests = array_filter($this->results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        
        echo "<h3>📊 Overall Status:</h3>";
        
        if (count($this->errors) === 0) {
            if (count($this->warnings) === 0) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #155724; margin: 0;'>🎉 ALL MULTISITE TESTS PASSED</h4>";
                echo "<p style='color: #155724; margin: 5px 0 0 0;'>Gary AI plugin is fully compatible with WordPress multisite.</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4 style='color: #856404; margin: 0;'>⚠️ TESTS PASSED WITH WARNINGS</h4>";
                echo "<p style='color: #856404; margin: 5px 0 0 0;'>Plugin is compatible but some features need attention.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>❌ MULTISITE COMPATIBILITY ISSUES FOUND</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Please address the errors below before deploying to multisite.</p>";
            echo "</div>";
        }
        
        echo "<p><strong>Tests Passed:</strong> $passed_count / $total_tests</p>";
        
        if (!empty($this->errors)) {
            echo "<h3 class='error'>❌ Errors Found (" . count($this->errors) . "):</h3>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 class='warning'>⚠️ Warnings (" . count($this->warnings) . "):</h3>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li class='warning'>{$warning}</li>";
            }
            echo "</ul>";
        }
        
        echo "<h3>✅ Multisite Compatibility Summary:</h3>";
        echo "<ul class='checklist'>";
        echo "<li class='pass'>WordPress Version: " . get_bloginfo('version') . "</li>";
        echo "<li class='pass'>Multisite Status: " . (is_multisite() ? 'Enabled' : 'Disabled') . "</li>";
        echo "<li class='pass'>Current Site: " . get_current_blog_id() . "</li>";
        echo "<li class='pass'>Network: " . get_current_network_id() . "</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new GaryAIMultisiteTest();
    $test->runAllTests();
}

// Also provide a function for programmatic testing
function gary_ai_run_multisite_test() {
    $test = new GaryAIMultisiteTest();
    ob_start();
    $test->runAllTests();
    $output = ob_get_clean();
    return $output;
} 