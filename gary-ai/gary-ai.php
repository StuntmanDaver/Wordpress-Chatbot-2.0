<?php
/**
 * Plugin Name: Gary AI
 * Plugin URI: https://github.com/gary-ai/wordpress-plugin
 * Description: AI-powered chatbot widget for WordPress using Contextual AI technology. Provides intelligent customer support and engagement through advanced conversational AI.
 * Version: 1.0.0
 * Author: Gary AI Team
 * Author URI: https://gary-ai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gary-ai
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package GaryAI
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GARY_AI_VERSION', '1.0.0');
define('GARY_AI_PLUGIN_FILE', __FILE__);
define('GARY_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GARY_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GARY_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Gary AI Plugin Class
 */
class GaryAI {
    
    /**
     * Plugin instance
     * @var GaryAI
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     * @return GaryAI
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Plugin initialization
        add_action('plugins_loaded', [$this, 'init']);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_action('wp_footer', [$this, 'addWidgetContainer']);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        
        // AJAX hooks - Note: gary_ai_chat handlers are in GaryAIAdminAjax class
        add_action('wp_ajax_gary_ai_test_connection', [$this, 'handleTestConnection']);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
            $this->createDatabaseTables();
            $this->setDefaultOptions();
            
            // Clear rewrite rules
            flush_rewrite_rules();
            
            error_log('Gary AI Plugin activated successfully');
        } catch (Exception $e) {
            error_log('Gary AI Plugin activation error: ' . $e->getMessage());
            wp_die('Plugin activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        try {
            // Clear scheduled events
            wp_clear_scheduled_hook('gary_ai_cleanup_sessions');
            
            // Clear rewrite rules
            flush_rewrite_rules();
            
            error_log('Gary AI Plugin deactivated successfully');
        } catch (Exception $e) {
            error_log('Gary AI Plugin deactivation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('gary-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includeFiles();
        
        // Initialize components
        $this->initializeComponents();
    }
    
    /**
     * Include required files
     */
    private function includeFiles() {
        $includes = [
            'includes/class-contextual-ai-client.php',
            'includes/class-admin-ajax.php',
            'includes/class-analytics.php',
            'includes/class-gdpr-compliance.php',
            'includes/class-jwt-auth.php'
        ];
        
        foreach ($includes as $file) {
            $file_path = GARY_AI_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Gary AI: Required file missing: $file");
            }
        }
    }
    
    /**
     * Initialize plugin components
     */
    private function initializeComponents() {
        // Initialize AJAX handler if class exists
        if (class_exists('GaryAIAdminAjax')) {
            new GaryAIAdminAjax();
        }
        
        // Initialize GDPR compliance if class exists
        if (class_exists('GaryAIGDPR')) {
            new GaryAIGDPR();
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueFrontendAssets() {
        // Only enqueue if chatbot is enabled
        if (!get_option('gary_ai_chatbot_enabled', 0)) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'gary-ai-chat-widget',
            GARY_AI_PLUGIN_URL . 'assets/css/chat-widget.css',
            [],
            GARY_AI_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'gary-ai-chat-widget',
            GARY_AI_PLUGIN_URL . 'assets/js/chat-widget.js',
            ['jquery'],
            GARY_AI_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('gary-ai-chat-widget', 'garyAI', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gary_ai_nonce'),
            'strings' => [
                'placeholder' => __('Type your message...', 'gary-ai'),
                'send' => __('Send', 'gary-ai'),
                'connecting' => __('Connecting...', 'gary-ai'),
                'error' => __('Something went wrong. Please try again.', 'gary-ai')
            ]
        ]);
    }
    
    /**
     * Add widget container to footer
     */
    public function addWidgetContainer() {
        // Only add if chatbot is enabled
        if (!get_option('gary_ai_chatbot_enabled', 0)) {
            return;
        }
        
        $position = get_option('gary_ai_widget_position', 'bottom-right');
        echo '<div id="gary-ai-widget-container" class="gary-ai-widget-' . esc_attr($position) . '"></div>';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook) {
        // Only enqueue on our admin pages
        if (strpos($hook, 'gary-ai') === false) {
            return;
        }
        
        wp_enqueue_style(
            'gary-ai-admin',
            GARY_AI_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GARY_AI_VERSION
        );
        
        wp_enqueue_script(
            'gary-ai-admin',
            GARY_AI_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            GARY_AI_VERSION,
            true
        );
        
        wp_localize_script('gary-ai-admin', 'garyAIAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gary_ai_nonce'),
            'strings' => [
                'test_success' => __('Connection test successful!', 'gary-ai'),
                'test_error' => __('Connection test failed. Please check your settings.', 'gary-ai')
            ]
        ]);
    }
    
    /**
     * Register settings
     */
    public function registerSettings() {
        // Register individual settings with the same group
        register_setting('gary_ai_options', 'gary_ai_chatbot_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('gary_ai_options', 'gary_ai_contextual_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        register_setting('gary_ai_options', 'gary_ai_agent_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        register_setting('gary_ai_options', 'gary_ai_datastore_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        // Add settings section
        add_settings_section(
            'gary_ai_main_section',
            __('Gary AI Configuration', 'gary-ai'),
            null,
            'gary_ai_options'
        );
        
        // Add settings fields
        add_settings_field(
            'gary_ai_chatbot_enabled',
            __('Enable Chatbot', 'gary-ai'),
            [$this, 'renderChatbotEnabledField'],
            'gary_ai_options',
            'gary_ai_main_section'
        );
        
        add_settings_field(
            'gary_ai_contextual_api_key',
            __('API Key', 'gary-ai'),
            [$this, 'renderApiKeyField'],
            'gary_ai_options',
            'gary_ai_main_section'
        );
        
        add_settings_field(
            'gary_ai_agent_id',
            __('Agent ID', 'gary-ai'),
            [$this, 'renderAgentIdField'],
            'gary_ai_options',
            'gary_ai_main_section'
        );
        
        add_settings_field(
            'gary_ai_datastore_id',
            __('Datastore ID', 'gary-ai'),
            [$this, 'renderDatastoreIdField'],
            'gary_ai_options',
            'gary_ai_main_section'
        );
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Gary AI', 'gary-ai'),
            __('Gary AI', 'gary-ai'),
            'manage_options',
            'gary-ai',
            [$this, 'renderSettingsPage'],
            'dashicons-format-chat',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function renderAdminPage() {
        echo '<div class="wrap"><h1>' . __('Gary AI Dashboard', 'gary-ai') . '</h1>';
        echo '<p>' . __('Welcome to Gary AI! Configure your chatbot settings below.', 'gary-ai') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        echo '<div class="wrap"><h1>' . __('Gary AI Settings', 'gary-ai') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('gary_ai_options');
        do_settings_sections('gary_ai_options');
        echo '<p><button type="button" id="gary-ai-test-connection" class="button">' . __('Test Connection', 'gary-ai') . '</button></p>';
        echo '<div class="gary-ai-test-result" style="display:none;"></div>';
        submit_button();
        echo '</form></div>';
    }
    
    /**
     * Render chatbot enabled field
     */
    public function renderChatbotEnabledField() {
        $value = get_option('gary_ai_chatbot_enabled', 0);
        echo '<input type="checkbox" name="gary_ai_chatbot_enabled" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Enable the Gary AI chatbot widget on your website.', 'gary-ai') . '</p>';
    }
    
    /**
     * Render API key field
     */
    public function renderApiKeyField() {
        $value = get_option('gary_ai_contextual_api_key', '');
        echo '<input type="text" name="gary_ai_contextual_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Contextual AI API key.', 'gary-ai') . '</p>';
    }
    
    /**
     * Render agent ID field
     */
    public function renderAgentIdField() {
        $value = get_option('gary_ai_agent_id', '');
        echo '<input type="text" name="gary_ai_agent_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Contextual AI Agent ID.', 'gary-ai') . '</p>';
    }
    
    /**
     * Render datastore ID field
     */
    public function renderDatastoreIdField() {
        $value = get_option('gary_ai_datastore_id', '');
        echo '<input type="text" name="gary_ai_datastore_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Contextual AI Datastore ID.', 'gary-ai') . '</p>';
    }
    

    
    /**
     * Handle test connection AJAX
     */
    public function handleTestConnection() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gary_ai_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        try {
            // Get credentials from POST data or options
            $api_key = sanitize_text_field($_POST['api_key'] ?? get_option('gary_ai_contextual_api_key', ''));
            $agent_id = sanitize_text_field($_POST['agent_id'] ?? get_option('gary_ai_agent_id', ''));
            $datastore_id = sanitize_text_field($_POST['datastore_id'] ?? get_option('gary_ai_datastore_id', ''));
            
            // Validate credentials format
            $validation = ContextualAIClient::validateCredentials($api_key, $agent_id, $datastore_id);
            if (!$validation['valid']) {
                wp_send_json_error(['message' => 'Validation failed: ' . implode(', ', $validation['errors'])]);
                return;
            }
            
            // Create client and test connection
            $client = new ContextualAIClient();
            if (!empty($_POST['api_key'])) {
                // Use provided credentials for testing
                $client->setCredentials($api_key, $agent_id, $datastore_id);
            }
            
            $result = $client->testConnection();
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => 'Connection failed: ' . $result->get_error_message()]);
            } else {
                wp_send_json_success(['message' => 'Connection test successful! API is responding correctly.']);
            }
            
        } catch (Exception $e) {
            error_log('Gary AI: Test connection error - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Connection test failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Create database tables
     */
    private function createDatabaseTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Conversations table
        $conversations_table = $wpdb->prefix . 'gary_ai_conversations';
        $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) DEFAULT NULL,
            session_id varchar(100) NOT NULL,
            message text NOT NULL,
            response text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Analytics table
        $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            session_id varchar(100) NOT NULL,
            user_id int(11) DEFAULT NULL,
            event_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        try {
            dbDelta($conversations_sql);
            dbDelta($analytics_sql);
        } catch (Exception $e) {
            error_log('Gary AI: Database table creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Set default options
     */
    private function setDefaultOptions() {
        $defaults = [
            'gary_ai_chatbot_enabled' => 0,
            'gary_ai_widget_position' => 'bottom-right',
            'gary_ai_primary_color' => '#007cba',
            'gary_ai_chatbot_name' => 'Gary AI Assistant',
            'gary_ai_welcome_message' => 'Hello! How can I help you today?'
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
GaryAI::getInstance(); 