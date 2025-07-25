<?php
/**
 * Admin AJAX Handler for Gary AI Chatbot
 * 
 * Handles AJAX requests from the admin dashboard for analytics,
 * configuration, and system operations
 * 
 * @package GaryAIChatbot
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GaryAIAdminAjax {
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->analytics = new GaryAIAnalytics();
        add_action('wp_ajax_gary_ai_analytics_overview', [$this, 'handleAnalyticsOverview']);
        add_action('wp_ajax_gary_ai_conversation_metrics', [$this, 'handleConversationMetrics']);
        add_action('wp_ajax_gary_ai_engagement_metrics', [$this, 'handleEngagementMetrics']);
        add_action('wp_ajax_gary_ai_performance_metrics', [$this, 'handlePerformanceMetrics']);
        add_action('wp_ajax_gary_ai_realtime_analytics', [$this, 'handleRealtimeAnalytics']);
        add_action('wp_ajax_gary_ai_export_analytics', [$this, 'handleExportAnalytics']);
        add_action('wp_ajax_gary_ai_export_conversations', [$this, 'handleExportConversations']);
        add_action('wp_ajax_gary_ai_clear_analytics', [$this, 'handleClearAnalytics']);
        add_action('wp_ajax_gary_ai_save_settings', [$this, 'handleSaveSettings']);
        add_action('wp_ajax_gary_ai_test_config', [$this, 'handleTestConfiguration']);
        
        // Frontend chat handlers
        add_action('wp_ajax_gary_ai_chat', [$this, 'handleChatMessage']);
        add_action('wp_ajax_nopriv_gary_ai_chat', [$this, 'handleChatMessage']);
    }
    
    /**
     * Handle analytics overview request
     */
    public function handleAnalyticsOverview() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '7d');
            
            $request = new WP_REST_Request('GET', '/gary-ai/v1/analytics/overview');
            $request->set_param('period', $period);
            
            $response = $this->analytics->getAnalyticsOverview($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Analytics Overview Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load analytics overview');
        }
    }
    
    /**
     * Handle conversation metrics request
     */
    public function handleConversationMetrics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '7d');
            $granularity = sanitize_text_field($_GET['granularity'] ?? 'daily');
            
            $request = new WP_REST_Request('GET', '/gary-ai/v1/analytics/conversations');
            $request->set_param('period', $period);
            $request->set_param('granularity', $granularity);
            
            $response = $this->analytics->getConversationMetrics($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Conversation Metrics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load conversation metrics');
        }
    }
    
    /**
     * Handle engagement metrics request
     */
    public function handleEngagementMetrics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '7d');
            
            $request = new WP_REST_Request('GET', '/gary-ai/v1/analytics/engagement');
            $request->set_param('period', $period);
            
            $response = $this->analytics->getUserEngagementMetrics($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Engagement Metrics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load engagement metrics');
        }
    }
    
    /**
     * Handle performance metrics request
     */
    public function handlePerformanceMetrics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '24h');
            
            $request = new WP_REST_Request('GET', '/gary-ai/v1/analytics/performance');
            $request->set_param('period', $period);
            
            $response = $this->analytics->getPerformanceMetrics($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Performance Metrics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load performance metrics');
        }
    }
    
    /**
     * Handle real-time analytics request
     */
    public function handleRealtimeAnalytics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $request = new WP_REST_Request('GET', '/gary-ai/v1/analytics/realtime');
            $response = $this->analytics->getRealtimeAnalytics($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Realtime Analytics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load real-time analytics');
        }
    }
    
    /**
     * Handle analytics export request
     */
    public function handleExportAnalytics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '30d');
            $format = sanitize_text_field($_GET['format'] ?? 'csv');
            $metrics = ['conversations', 'users', 'performance']; // Default metrics
            
            $request = new WP_REST_Request('POST', '/gary-ai/v1/analytics/export');
            $request->set_param('period', $period);
            $request->set_param('format', $format);
            $request->set_param('metrics', $metrics);
            
            $response = $this->analytics->exportAnalyticsData($request);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            wp_send_json_success($response->get_data());
            
        } catch (Exception $e) {
            error_log('Gary AI Export Analytics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to export analytics data');
        }
    }
    
    /**
     * Handle conversations export request
     */
    public function handleExportConversations() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            $period = sanitize_text_field($_GET['period'] ?? '30d');
            $date_range = $this->parsePeriod($period);
            
            global $wpdb;
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            
            $conversations = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    session_id,
                    message,
                    response,
                    created_at
                 FROM $conversations_table 
                 WHERE created_at >= %s AND created_at <= %s
                 ORDER BY created_at DESC",
                $date_range['start'],
                $date_range['end']
            ));
            
            // Generate CSV
            $filename = 'gary_ai_conversations_' . date('Y-m-d_H-i-s') . '.csv';
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            $file = fopen($file_path, 'w');
            
            // CSV headers
            fputcsv($file, ['Session ID', 'User Message', 'AI Response', 'Date']);
            
            // CSV data
            foreach ($conversations as $conversation) {
                fputcsv($file, [
                    $conversation->session_id,
                    $conversation->message,
                    wp_trim_words($conversation->response, 50),
                    $conversation->created_at
                ]);
            }
            
            fclose($file);
            
            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($file_path));
            
            readfile($file_path);
            unlink($file_path); // Clean up temporary file
            exit;
            
        } catch (Exception $e) {
            error_log('Gary AI Export Conversations Error: ' . $e->getMessage());
            wp_send_json_error('Failed to export conversations');
        }
    }
    
    /**
     * Handle clear analytics request
     */
    public function handleClearAnalytics() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            global $wpdb;
            
            // Clear analytics tables
            $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
            $performance_table = $wpdb->prefix . 'gary_ai_performance';
            $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
            
            $wpdb->query("TRUNCATE TABLE $analytics_table");
            $wpdb->query("TRUNCATE TABLE $performance_table");
            $wpdb->query("TRUNCATE TABLE $sessions_table");
            
            wp_send_json_success('Analytics data cleared successfully');
            
        } catch (Exception $e) {
            error_log('Gary AI Clear Analytics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to clear analytics data');
        }
    }
    
    /**
     * Handle save settings request
     */
    public function handleSaveSettings() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }
        
        try {
            // Sanitize and save settings
            $settings = [
                'chatbot_enabled' => isset($_POST['chatbot_enabled']) ? 1 : 0,
                'chatbot_name' => sanitize_text_field($_POST['chatbot_name'] ?? 'Gary AI Assistant'),
                'welcome_message' => sanitize_textarea_field($_POST['welcome_message'] ?? ''),
                'analytics_enabled' => isset($_POST['analytics_enabled']) ? 1 : 0,
                'data_retention' => intval($_POST['data_retention'] ?? 90),
                'real_time_updates' => isset($_POST['real_time_updates']) ? 1 : 0,
                'contextual_ai_api_key' => sanitize_text_field($_POST['contextual_ai_api_key'] ?? ''),
                'agent_id' => sanitize_text_field($_POST['agent_id'] ?? ''),
                'datastore_id' => sanitize_text_field($_POST['datastore_id'] ?? ''),
                'widget_position' => sanitize_text_field($_POST['widget_position'] ?? 'bottom-right'),
                'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#007cba'),
                'widget_theme' => sanitize_text_field($_POST['widget_theme'] ?? 'light')
            ];
            
            // Save each setting
            foreach ($settings as $key => $value) {
                update_option('gary_ai_' . $key, $value);
            }
            
            wp_send_json_success('Settings saved successfully');
            
        } catch (Exception $e) {
            error_log('Gary AI Save Settings Error: ' . $e->getMessage());
            wp_send_json_error('Failed to save settings');
        }
    }
    
    /**
     * Handle test configuration request
     */
    public function handleTestConfiguration() {
        if (!$this->verifyNonce() || !current_user_can('manage_options')) {
            wp_die('Unauthorized', 'Error', ['response' => 403]);
        }

        try {
            // Get credentials from POST or fallback to saved options
            $api_key = sanitize_text_field($_POST['api_key'] ?? get_option('gary_ai_contextual_api_key', ''));
            $agent_id = sanitize_text_field($_POST['agent_id'] ?? get_option('gary_ai_agent_id', ''));

            if (empty($api_key) || empty($agent_id)) {
                wp_send_json_error('API key and Agent ID are required');
                return;
            }

            // Initialize client with provided credentials
            $client = new ContextualAIClient();
            $client->setCredentials($api_key, $agent_id);

            // Test connection by sending a simple query
            $test_response = $client->query('Hello, this is a test message.', uniqid(), false);

            if ($test_response && isset($test_response['success']) && $test_response['success']) {
                wp_send_json_success('Configuration test successful');
            } else {
                wp_send_json_error('Configuration test failed - unable to get valid response from API');
            }

        } catch (Exception $e) {
            error_log('Gary AI Test Configuration Error: ' . $e->getMessage());
            wp_send_json_error('Configuration test failed: ' . $e->getMessage());
        }
    }

        
        /**
     * Handle chat message from frontend with advanced features support
     */
    public function handleChatMessage() {
        // Verify nonce
        if (!check_ajax_referer('gary_ai_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }
        
        // Get message and session/conversation IDs
        $message = sanitize_text_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        $structured_output = $_POST['structured_output'] ?? null;
        
        if (empty($message)) {
            wp_send_json_error(['message' => 'Message cannot be empty']);
            return;
        }
        
        try {
            // Initialize AI client
            $ai_client = new ContextualAIClient();
            
            // Get user ID if logged in
            $user_id = is_user_logged_in() ? get_current_user_id() : null;
            
            // Build query options
            $options = [
                'session_id' => $session_id,
                'user_id' => $user_id
            ];
            
            // Add conversation ID for multi-turn support
            if (!empty($conversation_id)) {
                $options['conversation_id'] = $conversation_id;
            }
            
            // Add structured output if provided
            if (!empty($structured_output) && is_array($structured_output)) {
                $options['structured_output'] = $structured_output;
            }
            
            // Send message to AI with enhanced options
            $response = $ai_client->sendMessage($message, $options);

            if ($response && isset($response['response'])) {
                // Store conversation in database with backward compatibility
                global $wpdb;
                $table_name = $wpdb->prefix . 'gary_ai_conversations';

                // Check if new fields exist in database schema
                $columns = $wpdb->get_col("DESCRIBE {$table_name}", 0);
                $has_conversation_id = in_array('conversation_id', $columns);
                $has_message_id = in_array('message_id', $columns);

                // Build data array based on available fields
                $data = [
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'message' => $message,
                    'response' => $response['response'],
                    'created_at' => current_time('mysql')
                ];
                
                $format = ['%d', '%s', '%s', '%s', '%s'];

                // Add new fields only if they exist in the database
                if ($has_conversation_id) {
                    $data['conversation_id'] = $response['conversation_id'] ?? $conversation_id;
                    $format[] = '%s';
                }
                
                if ($has_message_id) {
                    $data['message_id'] = $response['message_id'] ?? null;
                    $format[] = '%s';
                }

                $wpdb->insert($table_name, $data, $format);

                // Send successful response with enhanced data
                wp_send_json_success([
                    'message' => $response['response'],
                    'message_id' => $response['message_id'] ?? null,
                    'conversation_id' => $response['conversation_id'] ?? null,
                    'session_id' => $response['session_id'] ?? $session_id,
                    'citations' => $response['retrieval_info'] ?? [],
                    'structured_data' => $response['structured_data'] ?? null,
                    'usage' => $response['usage'] ?? []
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to get response from AI']);
            }
            
        } catch (Exception $e) {
            error_log('Gary AI Chat Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred. Please try again.']);
        }
    }
        
        /**
         * Verify nonce for security
         */
        private function verifyNonce() {
            $nonce = $_REQUEST['nonce'] ?? '';
            return wp_verify_nonce($nonce, 'gary_ai_nonce');
        }
        
        /**
         * Parse period string to date range
         */
        private function parsePeriod($period) {
            $end = current_time('mysql');
            
            switch ($period) {
                case '24h':
                    $start = date('Y-m-d H:i:s', strtotime('-24 hours'));
                    break;
                case '7d':
                    $start = date('Y-m-d H:i:s', strtotime('-7 days'));
                    break;
                case '30d':
                    $start = date('Y-m-d H:i:s', strtotime('-30 days'));
                    break;
                case '90d':
                    $start = date('Y-m-d H:i:s', strtotime('-90 days'));
                    break;
                default:
                    $start = date('Y-m-d H:i:s', strtotime('-30 days'));
            }
            
            return ['start' => $start, 'end' => $end];
        }
    }
} 