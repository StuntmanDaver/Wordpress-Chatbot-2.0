<?php
/**
 * GDPR Compliance Handler
 * 
 * Handles GDPR compliance features including data consent, export, and deletion
 * 
 * @package GaryAIChatbot
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GaryAIGDPRCompliance {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Initialize GDPR compliance features
     */
    public function init() {
        // Add privacy policy hooks
        add_action('admin_init', [$this, 'addPrivacyPolicyContent']);
        
        // Add personal data exporters
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerDataExporter']);
        
        // Add personal data erasers
        add_filter('wp_privacy_personal_data_erasers', [$this, 'registerDataEraser']);
        
        // Add consent management
        add_action('wp_enqueue_scripts', [$this, 'enqueueConsentScripts']);
        
        // Add REST API endpoints for privacy controls
        add_action('rest_api_init', [$this, 'registerPrivacyEndpoints']);
    }
    
    /**
     * Add privacy policy content
     */
    public function addPrivacyPolicyContent() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        
        $content = $this->getPrivacyPolicyContent();
        
        wp_add_privacy_policy_content(
            'Gary AI',
            wp_kses_post(wpautop($content, false))
        );
    }
    
    /**
     * Get privacy policy content
     */
    private function getPrivacyPolicyContent() {
        return '
        <h2>Gary AI Data Collection</h2>
        
        <h3>What Data We Collect</h3>
        <p>When you use our chatbot, we may collect the following information:</p>
        <ul>
            <li>Messages you send to the chatbot</li>
            <li>Your IP address for rate limiting and security</li>
            <li>Browser information (User Agent) for compatibility</li>
            <li>Session identifiers for conversation continuity</li>
            <li>Feedback ratings (thumbs up/down) you provide</li>
            <li>Timestamps of your interactions</li>
        </ul>
        
        <h3>How We Use Your Data</h3>
        <p>We use the collected data to:</p>
        <ul>
            <li>Provide chatbot responses to your questions</li>
            <li>Improve the quality of our responses</li>
            <li>Prevent abuse and ensure security</li>
            <li>Analyze usage patterns to enhance the service</li>
        </ul>
        
        <h3>Data Storage and Retention</h3>
        <p>Your chat data is stored securely in our database. We retain conversation data for up to 12 months for service improvement purposes. You can request deletion of your data at any time.</p>
        
        <h3>Third-Party Services</h3>
        <p>We use Contextual AI services to process your messages and generate responses. Please review their privacy policy for information about how they handle data.</p>
        
        <h3>Your Rights</h3>
        <p>Under GDPR, you have the right to:</p>
        <ul>
            <li>Access your personal data</li>
            <li>Rectify inaccurate data</li>
            <li>Erase your personal data</li>
            <li>Restrict processing of your data</li>
            <li>Data portability</li>
            <li>Object to processing</li>
        </ul>
        
        <p>To exercise these rights, please contact us using the information provided in our main privacy policy.</p>
        ';
    }
    
    /**
     * Register data exporter
     */
    public function registerDataExporter($exporters) {
        $exporters['gary-ai'] = [
            'exporter_friendly_name' => __('Gary AI Data', 'gary-ai'),
            'callback' => [$this, 'exportUserData'],
        ];
        
        return $exporters;
    }
    
    /**
     * Export user data
     */
    public function exportUserData($email_address, $page = 1) {
        $data_to_export = [];
        $done = true;
        
        // Get user by email
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return [
                'data' => $data_to_export,
                'done' => $done,
            ];
        }
        
        global $wpdb;
        
        // Export conversation data
        $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$conversations_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user->ID
        ));
        
        if (!empty($conversations)) {
            $conversation_data = [];
            foreach ($conversations as $conversation) {
                $conversation_data[] = [
                    'name' => __('Conversation', 'gary-ai'),
                    'value' => sprintf(
                        __('Date: %s, Message: %s, Response: %s', 'gary-ai'),
                        $conversation->created_at,
                        wp_trim_words($conversation->message, 20),
                        wp_trim_words($conversation->response, 20)
                    )
                ];
            }
            
            $data_to_export[] = [
                'group_id' => 'gary-ai-chatbot-conversations',
                'group_label' => __('Chatbot Conversations', 'gary-ai'),
                'item_id' => 'conversations-' . $user->ID,
                'data' => $conversation_data,
            ];
        }
        
        // Export feedback data
        $feedback_table = $wpdb->prefix . 'gary_ai_chatbot_feedback';
        $feedback = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$feedback_table} WHERE user_id = %d ORDER BY created_at DESC",
            $user->ID
        ));
        
        if (!empty($feedback)) {
            $feedback_data = [];
            foreach ($feedback as $fb) {
                $feedback_data[] = [
                    'name' => __('Feedback', 'gary-ai'),
                    'value' => sprintf(
                        __('Date: %s, Type: %s, Message: %s', 'gary-ai'),
                        $fb->created_at,
                        $fb->feedback_type,
                        wp_trim_words($fb->message_content, 20)
                    )
                ];
            }
            
            $data_to_export[] = [
                'group_id' => 'gary-ai-chatbot-feedback',
                'group_label' => __('Chatbot Feedback', 'gary-ai'),
                'item_id' => 'feedback-' . $user->ID,
                'data' => $feedback_data,
            ];
        }
        
        return [
            'data' => $data_to_export,
            'done' => $done,
        ];
    }
    
    /**
     * Register data eraser
     */
    public function registerDataEraser($erasers) {
        $erasers['gary-ai'] = [
            'eraser_friendly_name' => __('Gary AI Data', 'gary-ai'),
            'callback' => [$this, 'eraseUserData'],
        ];
        
        return $erasers;
    }
    
    /**
     * Erase user data
     */
    public function eraseUserData($email_address, $page = 1) {
        $items_removed = 0;
        $items_retained = 0;
        $messages = [];
        $done = true;
        
        // Get user by email
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return [
                'items_removed' => $items_removed,
                'items_retained' => $items_retained,
                'messages' => $messages,
                'done' => $done,
            ];
        }
        
        global $wpdb;
        
        try {
            // Delete conversation data
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            $conversations_deleted = $wpdb->delete(
                $conversations_table,
                ['user_id' => $user->ID],
                ['%d']
            );
            
            if ($conversations_deleted !== false) {
                $items_removed += $conversations_deleted;
                if ($conversations_deleted > 0) {
                    $messages[] = sprintf(
                        __('Removed %d chatbot conversations.', 'gary-ai'),
                        $conversations_deleted
                    );
                }
            }
            
            // Delete feedback data
            $feedback_table = $wpdb->prefix . 'gary_ai_chatbot_feedback';
            $feedback_deleted = $wpdb->delete(
                $feedback_table,
                ['user_id' => $user->ID],
                ['%d']
            );
            
            if ($feedback_deleted !== false) {
                $items_removed += $feedback_deleted;
                if ($feedback_deleted > 0) {
                    $messages[] = sprintf(
                        __('Removed %d chatbot feedback entries.', 'gary-ai'),
                        $feedback_deleted
                    );
                }
            }
            
            // Also anonymize data by IP address if user is not logged in
            $user_ip = $this->getUserIPAddress($user);
            if ($user_ip) {
                $this->anonymizeDataByIP($user_ip);
            }
            
        } catch (Exception $e) {
            $messages[] = __('An error occurred while deleting chatbot data.', 'gary-ai');
            error_log('Gary AI GDPR deletion error: ' . $e->getMessage());
        }
        
        return [
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => $done,
        ];
    }
    
    /**
     * Enqueue consent management scripts
     */
    public function enqueueConsentScripts() {
        if (!$this->shouldShowConsentBanner()) {
            return;
        }
        
        wp_enqueue_script(
            'gary-ai-chatbot-consent',
            GARY_AI_CHATBOT_PLUGIN_URL . 'assets/js/consent.js',
            ['jquery'],
            GARY_AI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'gary-ai-chatbot-consent',
            GARY_AI_CHATBOT_PLUGIN_URL . 'assets/css/consent.css',
            [],
            GARY_AI_VERSION
        );
        
        wp_localize_script('gary-ai-chatbot-consent', 'garyAIConsent', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gary_ai_consent'),
            'strings' => [
                'consentTitle' => __('Cookie Consent', 'gary-ai'),
                'consentText' => __('We use cookies to improve your chatbot experience and analyze usage. Do you accept our use of cookies?', 'gary-ai'),
                'acceptButton' => __('Accept', 'gary-ai'),
                'declineButton' => __('Decline', 'gary-ai'),
                'learnMore' => __('Learn More', 'gary-ai')
            ]
        ]);
    }
    
    /**
     * Register privacy control endpoints
     */
    public function registerPrivacyEndpoints() {
        register_rest_route('gary-ai/v1', '/privacy/consent', [
            'methods' => 'POST',
            'callback' => [$this, 'handleConsentUpdate'],
            'permission_callback' => '__return_true',
            'args' => [
                'consent' => [
                    'required' => true,
                    'type' => 'boolean',
                    'sanitize_callback' => 'rest_sanitize_boolean'
                ],
                'session_id' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        register_rest_route('gary-ai/v1', '/privacy/data-request', [
            'methods' => 'POST',
            'callback' => [$this, 'handleDataRequest'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'request_type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['export', 'delete'],
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }
    
    /**
     * Handle consent update
     */
    public function handleConsentUpdate($request) {
        $consent = $request->get_param('consent');
        $session_id = $request->get_param('session_id');
        
        // Store consent preference
        if ($session_id) {
            set_transient('gary_ai_consent_' . md5($session_id), $consent, DAY_IN_SECONDS);
        }
        
        // Set cookie for consent preference
        setcookie(
            'gary_ai_chatbot_consent',
            $consent ? 'accepted' : 'declined',
            time() + (365 * DAY_IN_SECONDS),
            '/',
            '',
            is_ssl(),
            true
        );
        
        return rest_ensure_response([
            'success' => true,
            'message' => $consent ? 
                __('Consent granted. Thank you!', 'gary-ai') : 
                __('Consent declined. Limited functionality may be available.', 'gary-ai')
        ]);
    }
    
    /**
     * Handle data request
     */
    public function handleDataRequest($request) {
        $request_type = $request->get_param('request_type');
        $user = wp_get_current_user();
        
        if (!$user || !$user->exists()) {
            return new WP_Error(
                'unauthorized',
                __('You must be logged in to make data requests.', 'gary-ai'),
                ['status' => 401]
            );
        }
        
        try {
            if ($request_type === 'export') {
                $export_data = $this->exportUserData($user->user_email);
                
                return rest_ensure_response([
                    'success' => true,
                    'message' => __('Data export completed.', 'gary-ai'),
                    'data' => $export_data['data']
                ]);
                
            } elseif ($request_type === 'delete') {
                $delete_result = $this->eraseUserData($user->user_email);
                
                return rest_ensure_response([
                    'success' => true,
                    'message' => __('Data deletion completed.', 'gary-ai'),
                    'items_removed' => $delete_result['items_removed'],
                    'messages' => $delete_result['messages']
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Gary AI privacy request error: ' . $e->getMessage());
            
            return new WP_Error(
                'request_failed',
                __('Privacy request failed. Please try again.', 'gary-ai'),
                ['status' => 500]
            );
        }
        
        return new WP_Error(
            'invalid_request',
            __('Invalid request type.', 'gary-ai'),
            ['status' => 400]
        );
    }
    
    /**
     * Check if consent banner should be shown
     */
    private function shouldShowConsentBanner() {
        // Check if consent has already been given/declined
        if (isset($_COOKIE['gary_ai_chatbot_consent'])) {
            return false;
        }
        
        // Check if this is an admin page
        if (is_admin()) {
            return false;
        }
        
        // Check if GDPR compliance is required (EU visitors)
        // This is a simplified check - in production, you might want to use a more sophisticated geo-location service
        return true;
    }
    
    /**
     * Get user IP address for anonymization
     */
    private function getUserIPAddress($user) {
        // This would need to be implemented based on how you track user IP addresses
        // For now, return null as we don't store IP addresses linked to user accounts
        return null;
    }
    
    /**
     * Anonymize data by IP address
     */
    private function anonymizeDataByIP($ip_address) {
        global $wpdb;
        
        // Anonymize conversation data
        $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
        $wpdb->update(
            $conversations_table,
            ['user_id' => null],
            ['user_id' => null], // This would need proper IP tracking implementation
            ['%d'],
            ['%s']
        );
        
        // Anonymize feedback data
        $feedback_table = $wpdb->prefix . 'gary_ai_chatbot_feedback';
        $wpdb->update(
            $feedback_table,
            [
                'user_id' => null,
                'user_ip' => wp_privacy_anonymize_ip($ip_address)
            ],
            ['user_ip' => $ip_address],
            ['%d', '%s'],
            ['%s']
        );
    }
    
    /**
     * Check if user has given consent
     */
    public function hasUserConsent($session_id = null) {
        // Check cookie consent
        if (isset($_COOKIE['gary_ai_chatbot_consent']) && $_COOKIE['gary_ai_chatbot_consent'] === 'accepted') {
            return true;
        }
        
        // Check session consent
        if ($session_id) {
            $consent = get_transient('gary_ai_consent_' . md5($session_id));
            return $consent === true;
        }
        
        return false;
    }
}
