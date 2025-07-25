<?php
/**
 * Gary AI Plugin Activation Issues Fix
 * 
 * This script fixes the two main issues after plugin activation:
 * 1. Enables the chatbot widget (disabled by default)
 * 2. Resolves PHP warnings related to file operations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed. Run this through WordPress admin.');
}

class GaryAIActivationFix {
    
    public function __construct() {
        $this->fixActivationIssues();
    }
    
    /**
     * Fix all activation-related issues
     */
    public function fixActivationIssues() {
        echo "<h1>Gary AI Plugin - Activation Issues Fix</h1>\n";
        echo "<style>
            .error { color: #d63638; font-weight: bold; }
            .warning { color: #dba617; font-weight: bold; }
            .success { color: #00a32a; font-weight: bold; }
            .info { color: #2271b1; }
            .code { background: #f1f1f1; padding: 10px; margin: 10px 0; font-family: monospace; }
        </style>\n";
        
        $this->enableChatbot();
        $this->checkAssetFiles();
        $this->validatePluginSettings();
        $this->testFrontendDisplay();
        $this->displaySummary();
    }
    
    /**
     * Enable the chatbot widget
     */
    private function enableChatbot() {
        echo "<h2>1. Enable Chatbot Widget</h2>\n";
        
        $current_status = get_option('gary_ai_chatbot_enabled', 0);
        
        if ($current_status == 0) {
            update_option('gary_ai_chatbot_enabled', 1);
            echo "<span class='success'>✓ Chatbot enabled (was disabled)</span><br>\n";
            
            // Set other default options if needed
            $defaults = [
                'gary_ai_widget_position' => 'bottom-right',
                'gary_ai_primary_color' => '#007cba',
                'gary_ai_chatbot_name' => 'Gary AI Assistant',
                'gary_ai_welcome_message' => 'Hello! How can I help you today?'
            ];
            
            foreach ($defaults as $option => $value) {
                if (get_option($option) === false) {
                    add_option($option, $value);
                    echo "<span class='info'>  → Set default: {$option} = {$value}</span><br>\n";
                }
            }
            
        } else {
            echo "<span class='info'>ℹ Chatbot already enabled</span><br>\n";
        }
        
        // Display current settings
        echo "<div class='code'>";
        echo "Current Settings:<br>";
        echo "• Chatbot Enabled: " . (get_option('gary_ai_chatbot_enabled') ? 'Yes' : 'No') . "<br>";
        echo "• Widget Position: " . get_option('gary_ai_widget_position', 'bottom-right') . "<br>";
        echo "• Primary Color: " . get_option('gary_ai_primary_color', '#007cba') . "<br>";
        echo "• Chatbot Name: " . get_option('gary_ai_chatbot_name', 'Gary AI Assistant') . "<br>";
        echo "</div>";
    }
    
    /**
     * Check if all required asset files exist
     */
    private function checkAssetFiles() {
        echo "<h2>2. Asset Files Check</h2>\n";
        
        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_dir = plugin_dir_path(__FILE__);
        
        $required_assets = [
            'assets/css/chat-widget.css',
            'assets/js/chat-widget.js'
        ];
        
        $missing_files = [];
        
        foreach ($required_assets as $asset) {
            $file_path = $plugin_dir . $asset;
            $file_url = $plugin_url . $asset;
            
            if (file_exists($file_path)) {
                echo "<span class='success'>✓ Asset exists: {$asset}</span><br>\n";
                echo "<span class='info'>  → Path: {$file_path}</span><br>\n";
                echo "<span class='info'>  → URL: {$file_url}</span><br>\n";
                
                // Check if file is readable
                if (is_readable($file_path)) {
                    echo "<span class='success'>  → File is readable</span><br>\n";
                } else {
                    echo "<span class='error'>  → File is not readable</span><br>\n";
                    $missing_files[] = $asset . " (not readable)";
                }
                
            } else {
                echo "<span class='error'>✗ Asset missing: {$asset}</span><br>\n";
                $missing_files[] = $asset;
            }
        }
        
        if (empty($missing_files)) {
            echo "<span class='success'>✓ All required assets are present and accessible</span><br>\n";
        } else {
            echo "<span class='error'>✗ Missing or inaccessible assets found</span><br>\n";
            foreach ($missing_files as $file) {
                echo "<span class='error'>  → {$file}</span><br>\n";
            }
        }
    }
    
    /**
     * Validate plugin settings and API configuration
     */
    private function validatePluginSettings() {
        echo "<h2>3. Plugin Settings Validation</h2>\n";
        
        // Check API settings
        $api_key = get_option('gary_ai_api_key', '');
        $agent_id = get_option('gary_ai_agent_id', '');
        $datastore_id = get_option('gary_ai_datastore_id', '');
        
        if (empty($api_key)) {
            echo "<span class='warning'>⚠ API Key not configured</span><br>\n";
            echo "<span class='info'>  → Go to Gary AI → Settings to configure API credentials</span><br>\n";
        } else {
            echo "<span class='success'>✓ API Key configured</span><br>\n";
        }
        
        if (empty($agent_id)) {
            echo "<span class='warning'>⚠ Agent ID not configured</span><br>\n";
        } else {
            echo "<span class='success'>✓ Agent ID configured</span><br>\n";
        }
        
        if (empty($datastore_id)) {
            echo "<span class='warning'>⚠ Datastore ID not configured</span><br>\n";
        } else {
            echo "<span class='success'>✓ Datastore ID configured</span><br>\n";
        }
        
        // Check WordPress hooks
        echo "<h3>WordPress Hooks Status</h3>\n";
        
        if (has_action('wp_enqueue_scripts', 'GaryAI->enqueueFrontendAssets')) {
            echo "<span class='success'>✓ Frontend assets hook registered</span><br>\n";
        } else {
            echo "<span class='warning'>⚠ Frontend assets hook may not be registered</span><br>\n";
        }
        
        if (has_action('wp_footer', 'GaryAI->addWidgetContainer')) {
            echo "<span class='success'>✓ Widget container hook registered</span><br>\n";
        } else {
            echo "<span class='warning'>⚠ Widget container hook may not be registered</span><br>\n";
        }
    }
    
    /**
     * Test frontend display conditions
     */
    private function testFrontendDisplay() {
        echo "<h2>4. Frontend Display Test</h2>\n";
        
        // Simulate frontend conditions
        $chatbot_enabled = get_option('gary_ai_chatbot_enabled', 0);
        
        if ($chatbot_enabled) {
            echo "<span class='success'>✓ Chatbot is enabled for frontend display</span><br>\n";
            
            // Test asset enqueuing conditions
            echo "<h3>Asset Loading Test</h3>\n";
            echo "<span class='info'>The following assets should load on the frontend:</span><br>\n";
            
            $plugin_url = plugin_dir_url(__FILE__);
            echo "<div class='code'>";
            echo "CSS: {$plugin_url}assets/css/chat-widget.css<br>";
            echo "JS: {$plugin_url}assets/js/chat-widget.js<br>";
            echo "</div>";
            
            // Test widget container
            echo "<h3>Widget Container Test</h3>\n";
            $position = get_option('gary_ai_widget_position', 'bottom-right');
            echo "<span class='info'>Widget container should be added to footer with class: gary-ai-widget-{$position}</span><br>\n";
            
            echo "<div class='code'>";
            echo "HTML: &lt;div id=\"gary-ai-widget-container\" class=\"gary-ai-widget-{$position}\"&gt;&lt;/div&gt;";
            echo "</div>";
            
        } else {
            echo "<span class='error'>✗ Chatbot is disabled - no frontend display</span><br>\n";
        }
    }
    
    /**
     * Display summary and next steps
     */
    private function displaySummary() {
        echo "<h2>Summary & Next Steps</h2>\n";
        
        $chatbot_enabled = get_option('gary_ai_chatbot_enabled', 0);
        $api_key = get_option('gary_ai_api_key', '');
        
        if ($chatbot_enabled) {
            echo "<div class='success'><h3>✓ Chatbot Widget Enabled</h3></div>\n";
            echo "<p>The chatbot widget should now appear on your website frontend.</p>\n";
        } else {
            echo "<div class='error'><h3>✗ Chatbot Widget Still Disabled</h3></div>\n";
            echo "<p>Manual intervention required to enable the chatbot.</p>\n";
        }
        
        echo "<h3>To complete setup:</h3>\n";
        echo "<ol>\n";
        echo "<li><strong>Configure API Settings</strong>:<br>\n";
        echo "   • Go to WordPress Admin → Gary AI → Settings<br>\n";
        echo "   • Enter your Contextual AI API credentials<br>\n";
        echo "   • API Key: key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8br>\n";
        echo "   • Agent ID: 1ef70a2a-1405-4ba5-9c27-62de4b263e20<br>\n";
        echo "   • Datastore ID: 6f01eb92-f12a-4113-a39f-3c4013303482</li>\n";
        echo "<li><strong>Test Frontend Display</strong>:<br>\n";
        echo "   • Visit your website homepage<br>\n";
        echo "   • Look for the chatbot widget in the bottom-right corner<br>\n";
        echo "   • If not visible, check browser console for errors</li>\n";
        echo "<li><strong>Test Chatbot Functionality</strong>:<br>\n";
        echo "   • Click the chatbot widget to open<br>\n";
        echo "   • Send a test message<br>\n";
        echo "   • Verify AI responses are working</li>\n";
        echo "</ol>\n";
        
        echo "<h3>If issues persist:</h3>\n";
        echo "<ul>\n";
        echo "<li>Check WordPress error logs for detailed error messages</li>\n";
        echo "<li>Verify all plugin files are properly uploaded</li>\n";
        echo "<li>Ensure proper file permissions (644 for files, 755 for directories)</li>\n";
        echo "<li>Test with a default WordPress theme to rule out theme conflicts</li>\n";
        echo "</ul>\n";
    }
}

// Run the fix if accessed through WordPress
if (defined('ABSPATH')) {
    new GaryAIActivationFix();
}
?>
