<?php
/**
 * Plugin Name: Gary AI
 * Plugin URI: https://gary-ai.com
 * Description: AI-powered chatbot widget for WordPress using Contextual AI technology
 * Version: 1.0.1
 * Author: Gary AI Team
 * Author URI: https://gary-ai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gary-ai
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * 
 * ⚠️  IMPORTANT: When making changes to this file, update CHANGELOG.md
 * Document all modifications under the [Unreleased] section following semantic versioning
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version compatibility
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Gary AI:</strong> This plugin requires PHP 7.4 or higher. ';
        echo 'You are running PHP ' . PHP_VERSION . '. ';
        echo 'Please contact your hosting provider to upgrade PHP.';
        echo '</p></div>';
    });
    return; // Stop loading the plugin
}

// Check WordPress version compatibility
global $wp_version;
if (version_compare($wp_version, '5.0', '<')) {
    add_action('admin_notices', function() {
        global $wp_version;
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Gary AI:</strong> This plugin requires WordPress 5.0 or higher. ';
        echo 'You are running WordPress ' . $wp_version . '. ';
        echo 'Please update WordPress to continue using this plugin.';
        echo '</p></div>';
    });
    return; // Stop loading the plugin
}

// Define plugin constants
define('GARY_AI_VERSION', '1.0.1');
define('GARY_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GARY_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GARY_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load required classes
require_once GARY_AI_PLUGIN_PATH . 'includes/class-contextual-ai-client.php';

// Only load admin ajax if analytics class exists (for now, we'll comment it out)
// require_once GARY_AI_PLUGIN_PATH . 'includes/class-admin-ajax.php';

/**
 * Main plugin class
 */
class GaryAI {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize plugin
        add_action('init', array($this, 'init'));
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add AJAX handlers first (before WordPress loads)
        add_action('wp_ajax_gary_ai_chat', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_gary_ai_chat', array($this, 'handle_chat_message'));
        add_action('wp_ajax_gary_ai_test_connection', array($this, 'handle_test_connection'));
        
        // Schedule data cleanup
        add_action('gary_ai_daily_cleanup', array($this, 'cleanup_old_conversations'));
        if (!wp_next_scheduled('gary_ai_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'gary_ai_daily_cleanup');
        }
        
        // Add backup plugin compatibility
        $this->add_backup_compatibility();
        
        // Check for plugin updates
        $this->check_for_updates();
        
        // Initialize telemetry/analytics
        $this->init_telemetry();
        
        // Initialize multisite compatibility
        $this->init_multisite_compatibility();
        
        // Initialize theme compatibility checks
        $this->init_theme_compatibility();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        error_log('Gary AI: Plugin initialization started');
        
        // Add WordPress hooks here (proper timing)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Check for plugin conflicts
        $this->check_plugin_conflicts();
        
        // Check for API version warnings
        $this->check_api_version_warnings();
        
        // Load text domain for translations
        $textdomain_loaded = load_plugin_textdomain('gary-ai', false, dirname(GARY_AI_PLUGIN_BASENAME) . '/languages');
        error_log('Gary AI: Text domain loaded: ' . ($textdomain_loaded ? 'YES' : 'NO'));
        
        // Register settings
        $this->register_settings();
        
        error_log('Gary AI: Plugin initialization completed');
    }
    
    /**
     * Register plugin settings
     */
    private function register_settings() {
        error_log('Gary AI: Registering plugin settings');
        
        register_setting('gary_ai_settings', 'gary_ai_contextual_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'key-mbd3zA3LKrje2pcJmC95yr_xp1JuhQPEcLgVM5h0-LpmdrfAQ'
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_agent_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1ef70a2a-1405-4ba5-9c27-62de4b263e20'
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_datastore_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '6f01eb92-f12a-4113-a39f-3c4013303482'
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_agent_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Gary AI'
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_widget_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_widget_position', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'bottom-right'
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_telemetry_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_share_analytics', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        // Proxy settings
        register_setting('gary_ai_settings', 'gary_ai_proxy_host', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_proxy_port', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_proxy_username', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('gary_ai_settings', 'gary_ai_proxy_password', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        error_log('Gary AI: All plugin settings registered successfully');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Enhanced debug logging
        error_log('Gary AI: enqueue_frontend_assets called on URL: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown'));
        error_log('Gary AI: is_admin() = ' . (is_admin() ? 'TRUE' : 'FALSE'));
        error_log('Gary AI: current_user_can(manage_options) = ' . (current_user_can('manage_options') ? 'TRUE' : 'FALSE'));
        
        // Check widget enabled setting with detailed logging
        $widget_enabled = get_option('gary_ai_widget_enabled', true);
        error_log('Gary AI: Widget enabled setting: ' . ($widget_enabled ? 'TRUE' : 'FALSE'));
        
        if (!$widget_enabled) {
            error_log('Gary AI: Widget is disabled in settings - STOPPING enqueue');
            return;
        }
        
        // Load widget assets regardless of API credentials for testing
        // This ensures the widget appears even without valid credentials
        $api_key = get_option('gary_ai_contextual_api_key');
        $agent_id = get_option('gary_ai_agent_id');
        
        error_log('Gary AI: API Key present: ' . (!empty($api_key) ? 'YES' : 'NO'));
        error_log('Gary AI: Agent ID present: ' . (!empty($agent_id) ? 'YES' : 'NO'));
        
        // Widget will load even without credentials for visual testing
        
        // Use minified assets in production
        $css_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'chat-widget.css' : 'chat-widget.min.css';
        $js_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'chat-widget.js' : 'chat-widget.min.js';
        
        // Enqueue styles
        wp_enqueue_style(
            'gary-ai-widget',
            GARY_AI_PLUGIN_URL . 'assets/css/' . $css_file,
            array(),
            GARY_AI_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'gary-ai-widget',
            GARY_AI_PLUGIN_URL . 'assets/js/' . $js_file,
            array('jquery'),
            GARY_AI_VERSION,
            true
        );
        
        // Localize script with data (NO SENSITIVE CREDENTIALS)
        wp_localize_script('gary-ai-widget', 'garyAI', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gary_ai_nonce'),
            'widget_position' => get_option('gary_ai_widget_position', 'bottom-right'),
            'api_configured' => !empty($api_key) && !empty($agent_id),
            'debug' => WP_DEBUG,
            'strings' => array(
                'placeholder' => __('Type your message...', 'gary-ai'),
                'send' => __('Send', 'gary-ai'),
                'thinking' => __('Thinking...', 'gary-ai'),
                'error' => __('Sorry, something went wrong. Please try again.', 'gary-ai'),
                'minimize' => __('Minimize', 'gary-ai'),
                'close' => __('Close', 'gary-ai'),
                'rate_limit' => __('Too many requests. Please wait before trying again.', 'gary-ai'),
                'offline' => __('You appear to be offline. Please check your internet connection.', 'gary-ai'),
                'connection_error' => __('Connection error. Please try again.', 'gary-ai'),
                'typing' => __('Gary AI is typing...', 'gary-ai')
            ),
            // Security notice for developers (non-sensitive)
            'security_note' => __('API credentials are handled server-side only for security', 'gary-ai')
        ));
        
        // Add widget render hook with proper timing
        add_action('wp_footer', array($this, 'render_widget_html'), 20);
        
        error_log('Gary AI: Widget assets enqueued successfully - CSS: ' . GARY_AI_PLUGIN_URL . 'assets/css/' . $css_file);
        error_log('Gary AI: Widget assets enqueued successfully - JS: ' . GARY_AI_PLUGIN_URL . 'assets/js/' . $js_file);
    }
    
    /**
     * Render widget HTML
     */
    public function render_widget_html() {
        error_log('Gary AI: render_widget_html called on URL: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown'));
        error_log('Gary AI: is_admin() = ' . (is_admin() ? 'TRUE' : 'FALSE'));
        
        // Check if widget is enabled with detailed logging
        $widget_enabled = get_option('gary_ai_widget_enabled', true);
        error_log('Gary AI: Widget enabled setting in render: ' . ($widget_enabled ? 'TRUE' : 'FALSE'));
        
        if (!$widget_enabled) {
            error_log('Gary AI: Widget is disabled in settings - STOPPING render');
            return;
        }
        
        // Don't show in admin
        if (is_admin()) {
            error_log('Gary AI: Skipping widget render in admin area - STOPPING render');
            return;
        }
        
        $position = get_option('gary_ai_widget_position', 'bottom-right');
        $agent_name = get_option('gary_ai_agent_name', 'Gary AI');
        
        error_log('Gary AI: Widget HTML rendered successfully with position: ' . $position);
        ?>
        <div id="gary-ai-widget-container" 
             class="gary-ai-widget-<?php echo esc_attr($position); ?>"
             role="region"
             aria-label="<?php esc_attr_e('AI Chat Assistant', 'gary-ai'); ?>">
            <!-- Toggle Button -->
            <button class="gary-ai-toggle" 
                    aria-label="<?php esc_attr_e('Open chat widget', 'gary-ai'); ?>"
                    aria-expanded="false"
                    aria-controls="gary-ai-widget">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="currentColor"/>
                </svg>
            </button>
            
            <!-- Main Widget -->
            <div id="gary-ai-widget" 
                 class="gary-ai-widget" 
                 style="display: none;"
                 role="dialog"
                 aria-labelledby="gary-ai-title"
                 aria-describedby="gary-ai-messages">
                <div class="gary-ai-header">
                    <div class="gary-ai-title">
                        <strong id="gary-ai-title"><?php echo esc_html($agent_name); ?></strong>
                        <span class="gary-ai-status" aria-live="polite"><?php esc_html_e('Online', 'gary-ai'); ?></span>
                    </div>
                    <div class="gary-ai-controls">
                        <button class="gary-ai-export" 
                                aria-label="<?php esc_attr_e('Export conversation', 'gary-ai'); ?>"
                                title="<?php esc_attr_e('Export chat history', 'gary-ai'); ?>">📄</button>
                        <button class="gary-ai-minimize" 
                                aria-label="<?php esc_attr_e('Minimize chat window', 'gary-ai'); ?>">−</button>
                        <button class="gary-ai-close" 
                                aria-label="<?php esc_attr_e('Close chat window', 'gary-ai'); ?>">×</button>
                    </div>
                </div>
                
                <div id="gary-ai-messages" 
                     class="gary-ai-messages"
                     role="log"
                     aria-live="polite"
                     aria-label="<?php esc_attr_e('Chat conversation', 'gary-ai'); ?>">
                    <div class="gary-ai-message bot" role="article" aria-label="<?php esc_attr_e('AI response', 'gary-ai'); ?>">
                        <?php esc_html_e('Hello! How can I help you today?', 'gary-ai'); ?>
                    </div>
                </div>
                
                <div class="gary-ai-input-area" role="group" aria-label="<?php esc_attr_e('Message input area', 'gary-ai'); ?>">
                    <label for="gary-ai-input" class="screen-reader-text"><?php esc_html_e('Type your message', 'gary-ai'); ?></label>
                    <input type="text" 
                           id="gary-ai-input" 
                           class="gary-ai-input" 
                           placeholder="<?php esc_attr_e('Type your message...', 'gary-ai'); ?>"
                           maxlength="500"
                           aria-label="<?php esc_attr_e('Chat message input', 'gary-ai'); ?>"
                           aria-describedby="gary-ai-char-count">
                    <button id="gary-ai-send" 
                            class="gary-ai-send" 
                            aria-label="<?php esc_attr_e('Send message', 'gary-ai'); ?>"
                            type="submit">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
                        </svg>
                        <span class="screen-reader-text"><?php esc_html_e('Send', 'gary-ai'); ?></span>
                    </button>
                    <div id="gary-ai-char-count" class="screen-reader-text" aria-live="polite"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_gary-ai') {
            return;
        }
        
        // Use minified assets in production
        $admin_css_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'admin.css' : 'admin.min.css';
        $admin_js_file = (defined('WP_DEBUG') && WP_DEBUG) ? 'admin.js' : 'admin.min.js';
        
        wp_enqueue_style(
            'gary-ai-admin',
            GARY_AI_PLUGIN_URL . 'assets/css/' . $admin_css_file,
            array(),
            GARY_AI_VERSION
        );
        
        wp_enqueue_script(
            'gary-ai-admin',
            GARY_AI_PLUGIN_URL . 'assets/js/' . $admin_js_file,
            array('jquery'),
            GARY_AI_VERSION,
            true
        );
        
        wp_localize_script('gary-ai-admin', 'garyAIAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gary_ai_admin_nonce'),
            'strings' => array(
                'test_success' => __('Connection successful!', 'gary-ai'),
                'test_error' => __('Connection failed. Please check your credentials.', 'gary-ai'),
                'saving' => __('Saving...', 'gary-ai'),
                'saved' => __('Settings saved!', 'gary-ai'),
                'api_key_required' => __('API Key is required', 'gary-ai'),
                'api_key_format' => __('API Key should start with "key-"', 'gary-ai'),
                'api_key_short' => __('API Key appears to be too short', 'gary-ai'),
                'agent_id_required' => __('Agent ID is required', 'gary-ai'),
                'agent_id_format' => __('Agent ID should be a valid UUID format', 'gary-ai'),
                'datastore_id_format' => __('Datastore ID should be a valid UUID format', 'gary-ai'),
                'validation_errors' => __('Validation Errors:', 'gary-ai')
            )
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        error_log('Gary AI: Adding admin menu');
        
        $menu_slug = add_menu_page(
            __('Gary AI', 'gary-ai'),
            __('Gary AI', 'gary-ai'),
            'manage_options',
            'gary-ai',
            array($this, 'render_admin_page'),
            'dashicons-format-chat',
            30
        );
        
        if ($menu_slug) {
            error_log('Gary AI: Admin menu added successfully with slug: ' . $menu_slug);
        } else {
            error_log('Gary AI: ERROR - Failed to add admin menu');
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        error_log('Gary AI: Rendering admin page');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('gary_ai_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gary_ai_contextual_api_key"><?php _e('Contextual AI API Key', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="gary_ai_contextual_api_key" 
                                   name="gary_ai_contextual_api_key" 
                                   value="<?php echo esc_attr(get_option('gary_ai_contextual_api_key')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your Contextual AI API key', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gary_ai_agent_id"><?php _e('Agent ID', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="gary_ai_agent_id" 
                                   name="gary_ai_agent_id" 
                                   value="<?php echo esc_attr(get_option('gary_ai_agent_id')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your Contextual AI Agent ID', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gary_ai_datastore_id"><?php _e('Datastore ID', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="gary_ai_datastore_id" 
                                   name="gary_ai_datastore_id" 
                                   value="<?php echo esc_attr(get_option('gary_ai_datastore_id')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your Contextual AI Datastore ID (optional)', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Widget Settings', 'gary-ai'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           name="gary_ai_widget_enabled" 
                                           value="1" 
                                           <?php checked(get_option('gary_ai_widget_enabled', true)); ?> />
                                    <?php _e('Enable chat widget', 'gary-ai'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gary_ai_widget_position"><?php _e('Widget Position', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <select name="gary_ai_widget_position" id="gary_ai_widget_position">
                                <option value="bottom-right" <?php selected(get_option('gary_ai_widget_position'), 'bottom-right'); ?>>
                                    <?php _e('Bottom Right', 'gary-ai'); ?>
                                </option>
                                <option value="bottom-left" <?php selected(get_option('gary_ai_widget_position'), 'bottom-left'); ?>>
                                    <?php _e('Bottom Left', 'gary-ai'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <?php submit_button(__('Save Settings', 'gary-ai'), 'primary', 'submit', false); ?>
                    <button type="button" id="gary-ai-test-connection" class="button button-secondary">
                        <?php _e('Test Connection', 'gary-ai'); ?>
                    </button>
                </p>
                
                <!-- Test Result Container -->
                <div class="gary-ai-test-result" style="margin-top: 20px;"></div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Plugin activation
     */
    private function create_database_tables() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Conversations table with compatible default
            $table_name = $wpdb->prefix . 'gary_ai_conversations';
            error_log('Gary AI: Creating table with name: ' . $table_name);
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) DEFAULT NULL,
                session_id varchar(255) NOT NULL,
                message text NOT NULL,
                response text NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY session_id (session_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            // Analytics table with compatible default
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                event_type varchar(100) NOT NULL,
                event_data longtext NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            $analytics_result = dbDelta($analytics_sql);

->

    private function create_database_tables() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Conversations table with compatible default
            $table_name = $wpdb->prefix . 'gary_ai_conversations';
            error_log('Gary AI: Creating table with name: ' . $table_name);
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) DEFAULT NULL,
                session_id varchar(255) NOT NULL,
                message text NOT NULL,
                response text NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY session_id (session_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            // Analytics table with compatible default
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                event_type varchar(100) NOT NULL,
                event_data longtext NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            $analytics_result = dbDelta($analytics_sql);
            
            // Enhanced error handling
            if (!empty($wpdb->last_error)) {
                error_log('Gary AI: Database table creation failed: ' . $wpdb->last_error);
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>Gary AI: Database error - ' . esc_html($wpdb->last_error) . '. Contact support.</p></div>';
                });
            } else {
                error_log('Gary AI: Database tables created successfully');
            }
            
            // Verify table was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                error_log("Gary AI: ERROR - Table $table_name was not created successfully");
                // Try alternative creation method
                $wpdb->query($sql);
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                if ($table_exists) {
                    error_log("Gary AI: Table $table_name created successfully using direct query");
                } else {
                    error_log("Gary AI: CRITICAL ERROR - Unable to create table $table_name");
                }
            } else {
                error_log("Gary AI: Table $table_name verified to exist");
            }
            
        } catch (Exception $e) {
            error_log('Gary AI: Database table creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle chat message AJAX request
     */
    public function handle_chat_message() {
        // Verify nonce with detailed logging
        $nonce_check = check_ajax_referer('gary_ai_nonce', 'nonce', false);
        if (!$nonce_check) {
            error_log('Gary AI: Chat nonce verification failed. Nonce: ' . ($_POST['nonce'] ?? 'MISSING'));
            wp_send_json_error(array(
                'message' => 'Invalid security token',
                'debug' => WP_DEBUG ? array(
                    'nonce_received' => $_POST['nonce'] ?? 'MISSING',
                    'expected_action' => 'gary_ai_nonce'
                ) : null
            ));
            return;
        }
        error_log('Gary AI: Chat nonce verification successful');
        
        // Get message and session ID
        $message = sanitize_text_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message cannot be empty'));
            return;
        }
        
        try {
            // Initialize AI client
            $ai_client = new ContextualAIClient();
            
            // Get user ID if logged in
            $user_id = is_user_logged_in() ? get_current_user_id() : null;
            
            // Send message to AI
            $response = $ai_client->sendMessage($message, array(
                'session_id' => $session_id,
                'user_id' => $user_id
            ));
            
            if ($response && isset($response['message'])) {
                // Store conversation in database with proper table naming
                global $wpdb;
                $table_name = $wpdb->prefix . 'gary_ai_conversations';
                
                $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'session_id' => $session_id,
                        'message' => $message,
                        'response' => $response['message'],
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s', '%s')
                );
                
                // Send successful response
                wp_send_json_success(array(
                    'message' => $response['message'],
                    'citations' => isset($response['citations']) ? $response['citations'] : array()
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to get response from AI'));
            }
            
        } catch (Exception $e) {
            error_log('Gary AI Chat Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Chat Error: ' . $e->getMessage(),
                'debug' => WP_DEBUG ? array(
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ) : null
            ));
        }
    }
    
    /**
     * Handle test connection AJAX request
     */
    public function handle_test_connection() {
        error_log('Gary AI: handle_test_connection called');
        error_log('Gary AI: POST data received: ' . print_r($_POST, true));
        
        // Check CURL extension first
        if (!function_exists('curl_init')) {
            error_log('Gary AI: CURL extension not available');
            wp_send_json_error(array('message' => 'CURL extension not available on server'));
            return;
        }
        error_log('Gary AI: CURL extension is available');
        
        // Verify nonce with detailed logging
        $nonce_received = $_POST['nonce'] ?? 'MISSING';
        error_log('Gary AI: Nonce received: ' . $nonce_received);
        $nonce_check = check_ajax_referer('gary_ai_admin_nonce', 'nonce', false);
        if (!$nonce_check) {
            error_log('Gary AI: Admin nonce verification failed. Nonce: ' . $nonce_received);
            wp_send_json_error(array(
                'message' => 'Invalid security token',
                'debug' => WP_DEBUG ? array(
                    'nonce_received' => $nonce_received,
                    'expected_action' => 'gary_ai_admin_nonce'
                ) : null
            ));
            return;
        }
        error_log('Gary AI: Admin nonce verification successful');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        try {
            // Get API credentials from POST data (current form values)
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            $agent_id = sanitize_text_field($_POST['agent_id'] ?? '');
            $datastore_id = sanitize_text_field($_POST['datastore_id'] ?? '');
            
            // Fall back to saved values if form values are empty
            if (empty($api_key)) {
                $api_key = get_option('gary_ai_contextual_api_key');
            }
            if (empty($agent_id)) {
                $agent_id = get_option('gary_ai_agent_id');
            }
            if (empty($datastore_id)) {
                $datastore_id = get_option('gary_ai_datastore_id');
            }
            
            error_log('Gary AI: Testing with API Key: ' . (!empty($api_key) ? substr($api_key, 0, 20) . '...' : 'EMPTY'));
            error_log('Gary AI: Testing with Agent ID: ' . $agent_id);
            
            if (empty($api_key) || empty($agent_id)) {
                wp_send_json_error(array('message' => 'API key and Agent ID are required'));
                return;
            }
            
            // Direct API test (bypass ContextualAIClient for reliability)
            $api_url = 'https://api.contextual.ai/v1/agents/' . $agent_id . '/query';
            $headers = [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ];
            $data = json_encode([
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello, this is a WordPress admin connection test.']
                ]
            ]);

            error_log('Gary AI: API URL: ' . $api_url);
            error_log('Gary AI: API Headers: ' . print_r($headers, true));
            error_log('Gary AI: API Data: ' . $data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            // Add verbose logging if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                curl_setopt($ch, CURLOPT_VERBOSE, true);
            }

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_info = curl_getinfo($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            error_log('Gary AI: CURL Info: ' . print_r($curl_info, true));

            if ($curl_error) {
                error_log('Gary AI: CURL Error: ' . $curl_error);
                wp_send_json_error(array('message' => 'Network error: ' . $curl_error));
                return;
            }

            error_log('Gary AI: API Response HTTP Code: ' . $http_code);
            error_log('Gary AI: API Response (first 500 chars): ' . substr($response, 0, 500));

            if ($http_code === 200) {
                $result = json_decode($response, true);
                if (isset($result['message']['content'])) {
                    $ai_message = $result['message']['content'];
                } elseif (isset($result['choices'][0]['message']['content'])) {
                    $ai_message = $result['choices'][0]['message']['content'];
                } elseif (isset($result['message'])) {
                    $ai_message = $result['message'];
                } else {
                    $ai_message = json_encode($result);
                }
                wp_send_json_success(array(
                    'message' => 'Connection successful! AI responded: ' . substr($ai_message, 0, 100) . '...'
                ));
            } else {
                $error_message = 'API returned HTTP ' . $http_code;
                if ($http_code === 401) {
                    $error_message = 'Invalid API credentials (HTTP 401)';
                } elseif ($http_code === 404) {
                    $error_message = 'Invalid Agent ID or endpoint (HTTP 404)';
                }
                wp_send_json_error(array('message' => $error_message));
            }
            
        } catch (Exception $e) {
            error_log('Gary AI Test Connection Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Connection failed: ' . $e->getMessage(),
                'debug' => WP_DEBUG ? array(
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ) : null
            ));
        }
    }
    
    /**
     * Clean up old conversations (data retention policy)
     */
    public function cleanup_old_conversations() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'gary_ai_conversations';
            
            // Delete conversations older than 90 days
            $retention_days = apply_filters('gary_ai_retention_days', 90);
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
            
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE created_at < %s",
                    $cutoff_date
                )
            );
            
            if ($deleted !== false) {
                error_log("Gary AI: Cleaned up {$deleted} old conversation records (older than {$retention_days} days)");
            } else {
                error_log("Gary AI: Error during conversation cleanup: " . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('Gary AI: Exception during conversation cleanup: ' . $e->getMessage());
        }
    }
    
    /**
     * Check for potential plugin conflicts
     */
    private function check_plugin_conflicts() {
        // List of known conflicting chat plugins
        $conflicting_plugins = array(
            'wp-chatbot/wp-chatbot.php' => 'WP Chatbot',
            'chatbot/chatbot.php' => 'ChatBot',
            'livechat/livechat.php' => 'LiveChat',
            'tidio-live-chat/tidio-live-chat.php' => 'Tidio Live Chat',
            'smartsupp-live-chat/smartsupp-live-chat.php' => 'Smartsupp Live Chat',
            'chatra-live-chat/chatra.php' => 'Chatra Live Chat',
            'crisp-live-chat/crisp.php' => 'Crisp Live Chat',
            'zendesk-chat/zendesk-chat.php' => 'Zendesk Chat',
            'intercom/intercom.php' => 'Intercom',
            'helpcrunch/helpcrunch.php' => 'HelpCrunch',
            'ai-chatbot/ai-chatbot.php' => 'AI ChatBot',
            'botsify/botsify.php' => 'Botsify',
            'chatfuel/chatfuel.php' => 'Chatfuel'
        );
        
        $active_conflicts = array();
        
        foreach ($conflicting_plugins as $plugin_path => $plugin_name) {
            if (is_plugin_active($plugin_path)) {
                $active_conflicts[] = $plugin_name;
                error_log("Gary AI: Potential conflict detected with plugin: {$plugin_name}");
            }
        }
        
        // Check for chat-related JavaScript conflicts
        $this->check_js_conflicts();
        
        if (!empty($active_conflicts)) {
            add_action('admin_notices', function() use ($active_conflicts) {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>' . __('Gary AI Warning:', 'gary-ai') . '</strong> ';
                echo sprintf(
                    __('Potential conflicts detected with: %s. Multiple chat plugins may cause conflicts. Consider disabling other chat plugins for best performance.', 'gary-ai'),
                    implode(', ', $active_conflicts)
                );
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Check for JavaScript conflicts
     */
    private function check_js_conflicts() {
        // Add a JS snippet to detect common chat widget conflicts
        add_action('wp_footer', function() {
            ?>
            <script type="text/javascript">
            (function() {
                if (typeof window.garyAIConflictCheck === 'undefined') {
                    window.garyAIConflictCheck = true;
                    
                    // Check for common conflicting objects
                    var conflicts = [];
                    
                    if (typeof window.Intercom !== 'undefined') conflicts.push('Intercom');
                    if (typeof window.Zendesk !== 'undefined') conflicts.push('Zendesk');
                    if (typeof window.LC_API !== 'undefined') conflicts.push('LiveChat');
                    if (typeof window.tidioIdentify !== 'undefined') conflicts.push('Tidio');
                    if (typeof window.$crisp !== 'undefined') conflicts.push('Crisp');
                    if (typeof window.smartsupp !== 'undefined') conflicts.push('Smartsupp');
                    if (typeof window.Chatra !== 'undefined') conflicts.push('Chatra');
                    
                    if (conflicts.length > 0 && typeof console !== 'undefined') {
                        console.warn('Gary AI: JavaScript conflicts detected with: ' + conflicts.join(', '));
                    }
                }
            })();
            </script>
            <?php
                          }, 999);
     }
     
     /**
      * Check for API version warnings
      */
     private function check_api_version_warnings() {
         $version_warning = get_transient('gary_ai_api_version_warning');
         
         if ($version_warning) {
             add_action('admin_notices', function() use ($version_warning) {
                 echo '<div class="notice notice-warning"><p>';
                 echo '<strong>' . __('Gary AI API Warning:', 'gary-ai') . '</strong> ';
                 echo sprintf(
                     __('API version mismatch detected. Expected: %s, Current: %s. Plugin functionality may be affected. Please check for plugin updates.', 'gary-ai'),
                     esc_html($version_warning['expected']),
                     esc_html($version_warning['actual'])
                 );
                 echo '</p></div>';
             });
         }
     }
      
     /**
     * Check for database schema migrations
     */
    private function check_database_migration() {
        $current_db_version = get_option('gary_ai_db_version', '0.0.0');
        
        if (version_compare($current_db_version, GARY_AI_VERSION, '<')) {
            error_log("Gary AI: Database migration needed from {$current_db_version} to " . GARY_AI_VERSION);
            
            // Run migration based on version
            $this->run_database_migration($current_db_version, GARY_AI_VERSION);
            
            // Update database version
            update_option('gary_ai_db_version', GARY_AI_VERSION);
            error_log("Gary AI: Database migration completed to " . GARY_AI_VERSION);
        }
    }
    
    /**
     * Run database migration
     */
    private function run_database_migration($from_version, $to_version) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'gary_ai_conversations';
            
            // Migration from 0.0.0 to 1.0.0 (initial install)
            if (version_compare($from_version, '1.0.0', '<')) {
                // Check if we need to add new columns
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
                $existing_columns = array_column($columns, 'Field');
                
                // Add locale column if it doesn't exist (future enhancement)
                if (!in_array('locale', $existing_columns)) {
                    $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN locale VARCHAR(10) DEFAULT 'en-US' AFTER response");
                    error_log('Gary AI: Added locale column to conversations table');
                }
                
                // Add metadata column for future extensibility
                if (!in_array('metadata', $existing_columns)) {
                    $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN metadata TEXT NULL AFTER locale");
                    error_log('Gary AI: Added metadata column to conversations table');
                }
                
                // Add index for better performance
                $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
                $index_names = array_column($indexes, 'Key_name');
                
                if (!in_array('idx_user_session', $index_names)) {
                    $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_user_session (user_id, session_id)");
                    error_log('Gary AI: Added user_session index to conversations table');
                }
            }
            
            // Future migration logic can be added here
            // Example:
            // if (version_compare($from_version, '1.1.0', '<')) {
            //     // Migration logic for 1.1.0
            // }
            
        } catch (Exception $e) {
                         error_log('Gary AI: Database migration error: ' . $e->getMessage());
         }
     }
     
     /**
      * Add backup plugin compatibility
      */
     private function add_backup_compatibility() {
         // Add Gary AI tables to backup plugins
         
         // UpdraftPlus compatibility
         add_filter('updraftplus_exclude_tables', array($this, 'include_in_updraftplus_backup'), 10, 1);
         
         // BackupBuddy compatibility
         add_filter('backupbuddy_database_tables', array($this, 'include_in_backupbuddy_backup'), 10, 1);
         
         // WP DB Backup compatibility
         add_filter('wp_db_backup_include_tables', array($this, 'include_in_wpdb_backup'), 10, 1);
         
         // BackWPup compatibility
         add_filter('backwpup_mysqldump_tables', array($this, 'include_in_backwpup_backup'), 10, 1);
         
         // All-in-One WP Migration compatibility
         add_filter('ai1wm_export_include_tables', array($this, 'include_in_ai1wm_backup'), 10, 1);
     }
     
     /**
      * Include Gary AI tables in UpdraftPlus backups
      */
     public function include_in_updraftplus_backup($excluded_tables) {
         global $wpdb;
         $gary_ai_tables = array(
             $wpdb->prefix . 'gary_ai_conversations'
         );
         
         // Remove our tables from exclusion list
         return array_diff($excluded_tables, $gary_ai_tables);
     }
     
     /**
      * Include Gary AI tables in various backup plugins
      */
     public function include_in_backupbuddy_backup($tables) {
         global $wpdb;
         $tables[] = $wpdb->prefix . 'gary_ai_conversations';
         return $tables;
     }
     
     public function include_in_wpdb_backup($tables) {
         global $wpdb;
         $tables[] = $wpdb->prefix . 'gary_ai_conversations';
         return $tables;
     }
     
     public function include_in_backwpup_backup($tables) {
         global $wpdb;
         $tables[] = $wpdb->prefix . 'gary_ai_conversations';
         return $tables;
     }
     
     public function include_in_ai1wm_backup($tables) {
         global $wpdb;
                   $tables[] = $wpdb->prefix . 'gary_ai_conversations';
          return $tables;
      }
      
      /**
       * Check for plugin updates
       */
      private function check_for_updates() {
          // Check for updates once per day
          $last_check = get_transient('gary_ai_update_check');
          
          if ($last_check === false) {
              // Simulate checking for updates (in a real implementation, this would check a remote server)
              $this->perform_update_check();
              
              // Cache the check for 24 hours
              set_transient('gary_ai_update_check', time(), DAY_IN_SECONDS);
          }
      }
      
      /**
       * Perform update check
       */
      private function perform_update_check() {
          try {
              // In a real implementation, this would check against your update server
              // For now, we'll just log that an update check was performed
              error_log('Gary AI: Update check performed. Current version: ' . GARY_AI_VERSION);
              
              // Example of how you might check for updates:
              // $update_url = 'https://your-update-server.com/check-version';
              // $response = wp_remote_get($update_url . '?plugin=gary-ai&version=' . GARY_AI_VERSION);
              // 
              // if (!is_wp_error($response)) {
              //     $update_data = json_decode(wp_remote_retrieve_body($response), true);
              //     if (version_compare($update_data['latest_version'], GARY_AI_VERSION, '>')) {
              //         set_transient('gary_ai_update_available', $update_data, DAY_IN_SECONDS);
              //     }
              // }
              
          } catch (Exception $e) {
              error_log('Gary AI: Update check failed: ' . $e->getMessage());
          }
      }
      
      /**
       * Initialize telemetry/analytics system
       */
      private function init_telemetry() {
          // Only enable telemetry if user has opted in
          if (get_option('gary_ai_telemetry_enabled', false)) {
              // Track plugin usage events
              add_action('gary_ai_chat_sent', array($this, 'track_chat_message'));
              add_action('gary_ai_widget_opened', array($this, 'track_widget_interaction'));
              add_action('gary_ai_error_occurred', array($this, 'track_error'));
              
              // Schedule weekly analytics report
              if (!wp_next_scheduled('gary_ai_weekly_analytics')) {
                  wp_schedule_event(time(), 'weekly', 'gary_ai_weekly_analytics');
              }
              add_action('gary_ai_weekly_analytics', array($this, 'send_weekly_analytics'));
          }
      }
      
      /**
       * Track chat message analytics
       */
      public function track_chat_message($message_data) {
          try {
              $analytics_data = array(
                  'event' => 'chat_message',
                  'timestamp' => current_time('mysql'),
                  'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                  'session_id' => $message_data['session_id'] ?? '',
                  'message_length' => strlen($message_data['message'] ?? ''),
                  'response_time' => $message_data['response_time'] ?? 0,
                  'success' => $message_data['success'] ?? true,
                  'site_url' => get_site_url(),
                  'plugin_version' => GARY_AI_VERSION,
                  'wp_version' => get_bloginfo('version'),
                  'php_version' => PHP_VERSION
              );
              
              $this->store_analytics_data($analytics_data);
              
          } catch (Exception $e) {
              error_log('Gary AI: Analytics tracking error: ' . $e->getMessage());
          }
      }
      
      /**
       * Track widget interaction analytics
       */
      public function track_widget_interaction($interaction_data) {
          try {
              $analytics_data = array(
                  'event' => 'widget_interaction',
                  'timestamp' => current_time('mysql'),
                  'action' => $interaction_data['action'] ?? 'unknown',
                  'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                  'session_id' => $interaction_data['session_id'] ?? '',
                  'site_url' => get_site_url(),
                  'plugin_version' => GARY_AI_VERSION
              );
              
              $this->store_analytics_data($analytics_data);
              
          } catch (Exception $e) {
              error_log('Gary AI: Analytics tracking error: ' . $e->getMessage());
          }
      }
      
      /**
       * Track error analytics
       */
      public function track_error($error_data) {
          try {
              $analytics_data = array(
                  'event' => 'error',
                  'timestamp' => current_time('mysql'),
                  'error_type' => $error_data['type'] ?? 'unknown',
                  'error_message' => $error_data['message'] ?? '',
                  'error_file' => $error_data['file'] ?? '',
                  'error_line' => $error_data['line'] ?? 0,
                  'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                  'site_url' => get_site_url(),
                  'plugin_version' => GARY_AI_VERSION,
                  'wp_version' => get_bloginfo('version'),
                  'php_version' => PHP_VERSION
              );
              
              $this->store_analytics_data($analytics_data);
              
          } catch (Exception $e) {
              error_log('Gary AI: Analytics tracking error: ' . $e->getMessage());
          }
      }
      
      /**
       * Store analytics data locally
       */
      private function store_analytics_data($data) {
          global $wpdb;
          
          $table_name = $wpdb->prefix . 'gary_ai_analytics';
          
          $wpdb->insert(
              $table_name,
              array(
                  'event_type' => $data['event'],
                  'event_data' => json_encode($data),
                  'created_at' => current_time('mysql')
              ),
              array('%s', '%s', '%s')
          );
      }
      
      /**
       * Send weekly analytics report
       */
      public function send_weekly_analytics() {
          try {
              global $wpdb;
              $table_name = $wpdb->prefix . 'gary_ai_analytics';
              
              // Get analytics from last week
              $week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
              $analytics = $wpdb->get_results($wpdb->prepare(
                  "SELECT * FROM {$table_name} WHERE created_at >= %s",
                  $week_ago
              ));
              
              if (!empty($analytics)) {
                  // Process and aggregate data
                  $report = $this->generate_analytics_report($analytics);
                  
                  // Send to analytics endpoint (optional - only if user consents)
                  if (get_option('gary_ai_share_analytics', false)) {
                      $this->send_analytics_to_server($report);
                  }
                  
                  // Store aggregated report locally
                  update_option('gary_ai_last_analytics_report', $report);
              }
              
          } catch (Exception $e) {
              error_log('Gary AI: Weekly analytics error: ' . $e->getMessage());
          }
      }
      
      /**
       * Generate analytics report
       */
      private function generate_analytics_report($analytics_data) {
          $report = array(
              'period' => 'weekly',
              'generated_at' => current_time('mysql'),
              'site_url' => get_site_url(),
              'plugin_version' => GARY_AI_VERSION,
              'total_events' => count($analytics_data),
              'chat_messages' => 0,
              'widget_interactions' => 0,
              'errors' => 0,
              'unique_sessions' => array(),
              'average_response_time' => 0
          );
          
          $total_response_time = 0;
          $response_time_count = 0;
          
          foreach ($analytics_data as $event) {
              $data = json_decode($event->event_data, true);
              
              switch ($data['event']) {
                  case 'chat_message':
                      $report['chat_messages']++;
                      if (isset($data['response_time'])) {
                          $total_response_time += $data['response_time'];
                          $response_time_count++;
                      }
                      break;
                  case 'widget_interaction':
                      $report['widget_interactions']++;
                      break;
                  case 'error':
                      $report['errors']++;
                      break;
              }
              
              if (!empty($data['session_id'])) {
                  $report['unique_sessions'][$data['session_id']] = true;
              }
          }
          
          $report['unique_sessions'] = count($report['unique_sessions']);
          $report['average_response_time'] = $response_time_count > 0 ? 
              $total_response_time / $response_time_count : 0;
          
          return $report;
      }
      
      /**
       * Send analytics to server (optional)
       */
      private function send_analytics_to_server($report) {
          // Only send anonymized data if user has explicitly consented
          $anonymized_report = array(
              'plugin_version' => $report['plugin_version'],
              'wp_version' => get_bloginfo('version'),
              'php_version' => PHP_VERSION,
              'total_events' => $report['total_events'],
              'chat_messages' => $report['chat_messages'],
              'widget_interactions' => $report['widget_interactions'],
              'errors' => $report['errors'],
              'unique_sessions' => $report['unique_sessions'],
              'average_response_time' => $report['average_response_time']
              // Note: No site_url or other identifying information
          );
          
          wp_remote_post('https://analytics.gary-ai.com/collect', array(
              'body' => json_encode($anonymized_report),
              'headers' => array('Content-Type' => 'application/json'),
              'timeout' => 5,
                             'sslverify' => true
           ));
       }
       
       /**
        * Initialize multisite compatibility
        */
       private function init_multisite_compatibility() {
           if (is_multisite()) {
               // Add network admin menu for multisite
               add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
               
               // Handle network-wide activation/deactivation
               add_action('wpmu_new_blog', array($this, 'activate_new_site'));
               add_action('wp_uninitialize_site', array($this, 'deactivate_site'));
               
               // Network-wide settings management
               add_action('network_admin_edit_gary_ai_network_settings', array($this, 'save_network_settings'));
           }
       }
       
       /**
        * Add network admin menu for multisite
        */
       public function add_network_admin_menu() {
           add_menu_page(
               __('Gary AI Network', 'gary-ai'),
               __('Gary AI Network', 'gary-ai'),
               'manage_network_options',
               'gary-ai-network',
               array($this, 'render_network_admin_page'),
               'dashicons-format-chat',
               30
           );
       }
       
       /**
        * Render network admin page
        */
       public function render_network_admin_page() {
           if (isset($_POST['submit'])) {
               check_admin_referer('gary_ai_network_settings');
               $this->save_network_settings();
               echo '<div class="notice notice-success"><p>' . __('Network settings saved!', 'gary-ai') . '</p></div>';
           }
           
           $network_enabled = get_site_option('gary_ai_network_enabled', false);
           $network_api_key = get_site_option('gary_ai_network_api_key', '');
           $network_agent_id = get_site_option('gary_ai_network_agent_id', '');
           ?>
           <div class="wrap">
               <h1><?php _e('Gary AI Network Settings', 'gary-ai'); ?></h1>
               <p><?php _e('Configure Gary AI settings for all sites in this network.', 'gary-ai'); ?></p>
               
               <form method="post" action="">
                   <?php wp_nonce_field('gary_ai_network_settings'); ?>
                   
                   <table class="form-table">
                       <tr>
                           <th scope="row"><?php _e('Enable Network-wide', 'gary-ai'); ?></th>
                           <td>
                               <label>
                                   <input type="checkbox" name="gary_ai_network_enabled" value="1" <?php checked($network_enabled); ?> />
                                   <?php _e('Enable Gary AI for all sites in network', 'gary-ai'); ?>
                               </label>
                           </td>
                       </tr>
                       <tr>
                           <th scope="row">
                               <label for="gary_ai_network_api_key"><?php _e('Network API Key', 'gary-ai'); ?></label>
                           </th>
                           <td>
                               <input type="password" id="gary_ai_network_api_key" name="gary_ai_network_api_key" 
                                      value="<?php echo esc_attr($network_api_key); ?>" class="regular-text" />
                               <p class="description"><?php _e('API key to use across all network sites (leave empty to allow individual site configuration)', 'gary-ai'); ?></p>
                           </td>
                       </tr>
                       <tr>
                           <th scope="row">
                               <label for="gary_ai_network_agent_id"><?php _e('Network Agent ID', 'gary-ai'); ?></label>
                           </th>
                           <td>
                               <input type="text" id="gary_ai_network_agent_id" name="gary_ai_network_agent_id" 
                                      value="<?php echo esc_attr($network_agent_id); ?>" class="regular-text" />
                               <p class="description"><?php _e('Agent ID to use across all network sites', 'gary-ai'); ?></p>
                           </td>
                       </tr>
                   </table>
                   
                   <?php submit_button(__('Save Network Settings', 'gary-ai')); ?>
               </form>
           </div>
           <?php
       }
       
       /**
        * Save network settings
        */
       public function save_network_settings() {
           if (!current_user_can('manage_network_options')) {
               return;
           }
           
           update_site_option('gary_ai_network_enabled', !empty($_POST['gary_ai_network_enabled']));
           update_site_option('gary_ai_network_api_key', sanitize_text_field($_POST['gary_ai_network_api_key'] ?? ''));
           update_site_option('gary_ai_network_agent_id', sanitize_text_field($_POST['gary_ai_network_agent_id'] ?? ''));
       }
       
       /**
        * Activate plugin on new multisite blog
        */
       public function activate_new_site($blog_id) {
           if (get_site_option('gary_ai_network_enabled', false)) {
               switch_to_blog($blog_id);
               $this->activate();
               restore_current_blog();
           }
       }
       
       /**
        * Deactivate plugin on site deletion
        */
       public function deactivate_site($site) {
           switch_to_blog($site->blog_id);
                       $this->deactivate();
            restore_current_blog();
        }
        
        /**
         * Initialize theme compatibility checks
         */
        private function init_theme_compatibility() {
            // Check theme compatibility on theme switch
            add_action('after_switch_theme', array($this, 'check_theme_compatibility'));
            
            // Perform initial theme compatibility check
            add_action('wp_loaded', array($this, 'check_theme_compatibility'));
        }
        
        /**
         * Check theme compatibility
         */
        public function check_theme_compatibility() {
            $current_theme = wp_get_theme();
            $theme_name = $current_theme->get('Name');
            $theme_version = $current_theme->get('Version');
            
            error_log("Gary AI: Checking compatibility with theme: {$theme_name} v{$theme_version}");
            
            // List of known problematic themes
            $problematic_themes = array(
                'Divi' => array(
                    'issues' => 'May conflict with widget positioning due to Divi Builder',
                    'solution' => 'Use custom CSS to adjust widget z-index'
                ),
                'Elementor' => array(
                    'issues' => 'May conflict with popup/modal systems',
                    'solution' => 'Disable Elementor popups or adjust widget timing'
                ),
                'Avada' => array(
                    'issues' => 'Heavy theme may cause performance issues',
                    'solution' => 'Enable caching and optimize widget loading'
                ),
                'BeTheme' => array(
                    'issues' => 'May override widget styles',
                    'solution' => 'Use !important CSS declarations'
                )
            );
            
            // Check for known issues
            foreach ($problematic_themes as $problem_theme => $info) {
                if (stripos($theme_name, $problem_theme) !== false) {
                    $this->add_theme_compatibility_notice($theme_name, $info);
                }
            }
            
            // Check for common theme features that might conflict
            $this->check_theme_features();
            
            // Store theme info for analytics
            update_option('gary_ai_current_theme', array(
                'name' => $theme_name,
                'version' => $theme_version,
                'last_checked' => current_time('mysql')
            ));
        }
        
        /**
         * Check for theme features that might cause conflicts
         */
        private function check_theme_features() {
            $issues = array();
            
            // Check if theme has its own chat widget
            if (function_exists('wp_chatbot_init') || 
                class_exists('LiveChat') || 
                function_exists('tawk_to_init')) {
                $issues[] = array(
                    'type' => 'chat_conflict',
                    'message' => __('Theme appears to have its own chat system. This may conflict with Gary AI.', 'gary-ai')
                );
            }
            
            // Check for heavy JavaScript frameworks
            global $wp_scripts;
            if (isset($wp_scripts->registered['gsap']) || 
                isset($wp_scripts->registered['three']) ||
                isset($wp_scripts->registered['animation-framework'])) {
                $issues[] = array(
                    'type' => 'js_framework',
                    'message' => __('Theme uses heavy JavaScript frameworks. Consider enabling performance optimizations.', 'gary-ai')
                );
            }
            
            // Check for custom CSS that might interfere
            $custom_css = wp_get_custom_css();
            if (strpos($custom_css, 'z-index') !== false || strpos($custom_css, 'position: fixed') !== false) {
                $issues[] = array(
                    'type' => 'css_conflict',
                    'message' => __('Custom CSS detected that may affect widget positioning.', 'gary-ai')
                );
            }
            
            if (!empty($issues)) {
                set_transient('gary_ai_theme_issues', $issues, DAY_IN_SECONDS);
            }
        }
        
        /**
         * Add theme compatibility notice
         */
        private function add_theme_compatibility_notice($theme_name, $info) {
            add_action('admin_notices', function() use ($theme_name, $info) {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>' . __('Gary AI Theme Compatibility:', 'gary-ai') . '</strong> ';
                echo sprintf(
                    __('Your theme "%s" may have compatibility issues: %s. Solution: %s', 'gary-ai'),
                    esc_html($theme_name),
                    esc_html($info['issues']),
                    esc_html($info['solution'])
                );
                echo '</p></div>';
            });
        }
}

// Initialize plugin
GaryAI::get_instance(); 