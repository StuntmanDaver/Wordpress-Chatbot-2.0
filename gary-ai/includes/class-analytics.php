<?php
/**
 * Analytics Handler for Gary AI Chatbot
 * 
 * Handles analytics data collection, calculations, and reporting
 * for the enhanced admin dashboard in version 1.1
 * 
 * @package GaryAIChatbot
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GaryAIAnalytics {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Initialize analytics features
     */
    public function init() {
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerAnalyticsEndpoints']);
        
        // Add analytics tracking hooks
        add_action('gary_ai_conversation_created', [$this, 'trackConversation'], 10, 2);
        add_action('gary_ai_feedback_submitted', [$this, 'trackFeedback'], 10, 2);
        add_action('gary_ai_response_generated', [$this, 'trackResponseTime'], 10, 3);
        
        // Create analytics tables on activation
        add_action('gary_ai_plugin_activated', [$this, 'createAnalyticsTables']);
        
        // Schedule daily analytics cleanup
        add_action('wp', [$this, 'scheduleAnalyticsCleanup']);
        add_action('gary_ai_daily_analytics_cleanup', [$this, 'performDailyCleanup']);
    }
    
    /**
     * Create analytics database tables
     */
    public function createAnalyticsTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics events table
        $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
        $sql_analytics = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_data longtext,
            session_id varchar(255),
            user_id bigint(20),
            user_ip varchar(45),
            user_agent text,
            page_url text,
            referer_url text,
            response_time_ms int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Performance metrics table
        $performance_table = $wpdb->prefix . 'gary_ai_performance';
        $sql_performance = "CREATE TABLE $performance_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_type varchar(50) NOT NULL,
            metric_value decimal(10,3) NOT NULL,
            session_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY metric_type (metric_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // User sessions table
        $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
        $sql_sessions = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL UNIQUE,
            user_id bigint(20),
            user_ip varchar(45),
            user_agent text,
            first_visit datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            page_views int(11) DEFAULT 1,
            messages_sent int(11) DEFAULT 0,
            session_duration int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY user_ip (user_ip),
            KEY last_activity (last_activity)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_analytics);
        dbDelta($sql_performance);
        dbDelta($sql_sessions);
    }
    
    /**
     * Register analytics REST API endpoints
     */
    public function registerAnalyticsEndpoints() {
        // Dashboard overview
        register_rest_route('gary-ai/v1', '/analytics/overview', [
            'methods' => 'GET',
            'callback' => [$this, 'getAnalyticsOverview'],
            'permission_callback' => [$this, 'checkAnalyticsPermission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Conversation metrics
        register_rest_route('gary-ai/v1', '/analytics/conversations', [
            'methods' => 'GET',
            'callback' => [$this, 'getConversationMetrics'],
            'permission_callback' => [$this, 'checkAnalyticsPermission'],
            'args' => [
                'period' => [
                    'default' => '7d',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'granularity' => [
                    'default' => 'daily',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // User engagement metrics
        register_rest_route('gary-ai/v1', '/analytics/engagement', [
            'methods' => 'GET',
            'callback' => [$this, 'getUserEngagementMetrics'],
            'permission_callback' => [$this, 'checkAnalyticsPermission'],
            'args' => [
                'period' => [
                    'default' => '7d',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Performance metrics
        register_rest_route('gary-ai/v1', '/analytics/performance', [
            'methods' => 'GET',
            'callback' => [$this, 'getPerformanceMetrics'],
            'permission_callback' => [$this, 'checkAnalyticsPermission'],
            'args' => [
                'period' => [
                    'default' => '24h',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Export analytics data
        register_rest_route('gary-ai/v1', '/analytics/export', [
            'methods' => 'POST',
            'callback' => [$this, 'exportAnalyticsData'],
            'permission_callback' => [$this, 'checkAnalyticsPermission'],
            'args' => [
                'format' => [
                    'default' => 'csv',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'period' => [
                    'default' => '30d',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'metrics' => [
                    'default' => ['conversations', 'users', 'performance'],
                    'sanitize_callback' => [$this, 'sanitizeMetricsList']
                ]
            ]
        ]);
        
        // Real-time analytics (for live dashboard updates)
        register_rest_route('gary-ai/v1', '/analytics/realtime', [
            'methods' => 'GET',
            'callback' => [$this, 'getRealtimeAnalytics'],
            'permission_callback' => [$this, 'checkAnalyticsPermission']
        ]);
    }
    
    /**
     * Get analytics overview
     */
    public function getAnalyticsOverview($request) {
        $period = $request->get_param('period');
        $date_range = $this->parsePeriod($period);
        
        global $wpdb;
        
        try {
            // Total conversations
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            $total_conversations = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $conversations_table WHERE created_at >= %s",
                $date_range['start']
            ));
            
            // Active users (unique sessions)
            $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
            $active_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $sessions_table WHERE last_activity >= %s",
                $date_range['start']
            ));
            
            // Average response time
            $performance_table = $wpdb->prefix . 'gary_ai_performance';
            $avg_response_time = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(metric_value) FROM $performance_table 
                 WHERE metric_type = 'response_time' AND created_at >= %s",
                $date_range['start']
            ));
            
            // User satisfaction (positive feedback percentage)
            $feedback_table = $wpdb->prefix . 'gary_ai_chatbot_feedback';
            $satisfaction_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    SUM(CASE WHEN feedback_type = 'positive' THEN 1 ELSE 0 END) as positive,
                    COUNT(*) as total
                 FROM $feedback_table WHERE created_at >= %s",
                $date_range['start']
            ));
            
            $satisfaction_rate = 0;
            if ($satisfaction_data[0]->total > 0) {
                $satisfaction_rate = round(($satisfaction_data[0]->positive / $satisfaction_data[0]->total) * 100, 1);
            }
            
            // Growth rates (compare with previous period)
            $prev_period = $this->getPreviousPeriod($date_range);
            $prev_conversations = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $conversations_table WHERE created_at >= %s AND created_at < %s",
                $prev_period['start'],
                $prev_period['end']
            ));
            
            $conversation_growth = $this->calculateGrowthRate($total_conversations, $prev_conversations);
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'total_conversations' => [
                        'value' => (int) $total_conversations,
                        'growth' => $conversation_growth,
                        'period' => $period
                    ],
                    'active_users' => [
                        'value' => (int) $active_users,
                        'period' => $period
                    ],
                    'avg_response_time' => [
                        'value' => round((float) $avg_response_time / 1000, 2), // Convert to seconds
                        'unit' => 's',
                        'period' => $period
                    ],
                    'satisfaction_rate' => [
                        'value' => $satisfaction_rate,
                        'unit' => '%',
                        'period' => $period
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Analytics Error: ' . $e->getMessage());
            
            return new WP_Error(
                'analytics_error',
                __('Failed to retrieve analytics overview.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get conversation metrics
     */
    public function getConversationMetrics($request) {
        $period = $request->get_param('period');
        $granularity = $request->get_param('granularity');
        $date_range = $this->parsePeriod($period);
        
        global $wpdb;
        
        try {
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            
            // Determine date format for grouping
            $date_format = $this->getDateFormat($granularity);
            
            // Conversation volume over time
            $volume_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(created_at, %s) as date_label,
                    COUNT(*) as count
                 FROM $conversations_table 
                 WHERE created_at >= %s AND created_at <= %s
                 GROUP BY date_label
                 ORDER BY created_at ASC",
                $date_format,
                $date_range['start'],
                $date_range['end']
            ));
            
            // Most common message types/topics (simplified analysis)
            $message_analysis = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    CASE 
                        WHEN LOWER(message) LIKE '%%help%%' OR LOWER(message) LIKE '%%support%%' THEN 'Help/Support'
                        WHEN LOWER(message) LIKE '%%price%%' OR LOWER(message) LIKE '%%cost%%' OR LOWER(message) LIKE '%%pricing%%' THEN 'Pricing'
                        WHEN LOWER(message) LIKE '%%how%%' OR LOWER(message) LIKE '%%tutorial%%' THEN 'How-to'
                        WHEN LOWER(message) LIKE '%%what%%' OR LOWER(message) LIKE '%%info%%' THEN 'Information'
                        ELSE 'Other'
                    END as category,
                    COUNT(*) as count,
                    AVG(LENGTH(message)) as avg_length
                 FROM $conversations_table 
                 WHERE created_at >= %s AND created_at <= %s
                 GROUP BY category
                 ORDER BY count DESC",
                $date_range['start'],
                $date_range['end']
            ));
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'volume_over_time' => $volume_data,
                    'message_categories' => $message_analysis,
                    'period' => $period,
                    'granularity' => $granularity
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Conversation Metrics Error: ' . $e->getMessage());
            
            return new WP_Error(
                'conversation_metrics_error',
                __('Failed to retrieve conversation metrics.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics($request) {
        $period = $request->get_param('period');
        $date_range = $this->parsePeriod($period);
        
        global $wpdb;
        
        try {
            $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            
            // Session duration analysis
            $session_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    AVG(session_duration) as avg_duration,
                    MIN(session_duration) as min_duration,
                    MAX(session_duration) as max_duration,
                    COUNT(*) as total_sessions
                 FROM $sessions_table 
                 WHERE last_activity >= %s",
                $date_range['start']
            ));
            
            // Messages per session
            $engagement_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    s.session_id,
                    s.session_duration,
                    COUNT(c.id) as message_count
                 FROM $sessions_table s
                 LEFT JOIN $conversations_table c ON s.session_id = c.session_id
                 WHERE s.last_activity >= %s
                 GROUP BY s.session_id
                 ORDER BY message_count DESC",
                $date_range['start']
            ));
            
            // Calculate engagement metrics
            $total_messages = array_sum(array_column($engagement_data, 'message_count'));
            $total_sessions = count($engagement_data);
            $avg_messages_per_session = $total_sessions > 0 ? round($total_messages / $total_sessions, 2) : 0;
            
            // Return rate (users who return within the period)
            $return_rate_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT s1.session_id) as returning_sessions
                 FROM $sessions_table s1
                 WHERE s1.last_activity >= %s 
                 AND EXISTS (
                     SELECT 1 FROM $sessions_table s2 
                     WHERE s2.user_ip = s1.user_ip 
                     AND s2.session_id != s1.session_id 
                     AND s2.first_visit < s1.first_visit
                 )",
                $date_range['start']
            ));
            
            $return_rate = $total_sessions > 0 ? 
                round(($return_rate_data[0]->returning_sessions / $total_sessions) * 100, 1) : 0;
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'avg_session_duration' => round((float) $session_data[0]->avg_duration / 60, 2), // Convert to minutes
                    'avg_messages_per_session' => $avg_messages_per_session,
                    'total_sessions' => (int) $total_sessions,
                    'return_rate' => $return_rate,
                    'engagement_distribution' => array_slice($engagement_data, 0, 10), // Top 10 most engaged sessions
                    'period' => $period
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Engagement Metrics Error: ' . $e->getMessage());
            
            return new WP_Error(
                'engagement_metrics_error',
                __('Failed to retrieve engagement metrics.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($request) {
        $period = $request->get_param('period');
        $date_range = $this->parsePeriod($period);
        
        global $wpdb;
        
        try {
            $performance_table = $wpdb->prefix . 'gary_ai_performance';
            
            // Response time metrics
            $response_time_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    AVG(metric_value) as avg_response_time,
                    MIN(metric_value) as min_response_time,
                    MAX(metric_value) as max_response_time,
                    COUNT(*) as total_requests
                 FROM $performance_table 
                 WHERE metric_type = 'response_time' AND created_at >= %s",
                $date_range['start']
            ));
            
            // Error rate
            $error_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    SUM(CASE WHEN metric_type = 'error' THEN 1 ELSE 0 END) as errors,
                    SUM(CASE WHEN metric_type = 'request' THEN 1 ELSE 0 END) as requests
                 FROM $performance_table 
                 WHERE created_at >= %s",
                $date_range['start']
            ));
            
            $error_rate = $error_data[0]->requests > 0 ? 
                round(($error_data[0]->errors / $error_data[0]->requests) * 100, 2) : 0;
            
            // Performance over time (hourly for 24h, daily for longer periods)
            $time_granularity = strpos($period, 'h') !== false ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
            $performance_timeline = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(created_at, %s) as time_label,
                    AVG(CASE WHEN metric_type = 'response_time' THEN metric_value END) as avg_response_time,
                    COUNT(CASE WHEN metric_type = 'error' THEN 1 END) as error_count,
                    COUNT(CASE WHEN metric_type = 'request' THEN 1 END) as request_count
                 FROM $performance_table 
                 WHERE created_at >= %s
                 GROUP BY time_label
                 ORDER BY created_at ASC",
                $time_granularity,
                $date_range['start']
            ));
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'avg_response_time' => round((float) $response_time_data[0]->avg_response_time / 1000, 2), // Convert to seconds
                    'min_response_time' => round((float) $response_time_data[0]->min_response_time / 1000, 2),
                    'max_response_time' => round((float) $response_time_data[0]->max_response_time / 1000, 2),
                    'error_rate' => $error_rate,
                    'total_requests' => (int) $response_time_data[0]->total_requests,
                    'performance_timeline' => $performance_timeline,
                    'period' => $period
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Performance Metrics Error: ' . $e->getMessage());
            
            return new WP_Error(
                'performance_metrics_error',
                __('Failed to retrieve performance metrics.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Export analytics data
     */
    public function exportAnalyticsData($request) {
        $format = $request->get_param('format');
        $period = $request->get_param('period');
        $metrics = $request->get_param('metrics');
        
        try {
            $export_data = [];
            
            if (in_array('conversations', $metrics)) {
                $conversations = $this->getConversationMetrics(new WP_REST_Request('GET', '', ['period' => $period]));
                $export_data['conversations'] = $conversations->get_data();
            }
            
            if (in_array('users', $metrics)) {
                $users = $this->getUserEngagementMetrics(new WP_REST_Request('GET', '', ['period' => $period]));
                $export_data['users'] = $users->get_data();
            }
            
            if (in_array('performance', $metrics)) {
                $performance = $this->getPerformanceMetrics(new WP_REST_Request('GET', '', ['period' => $period]));
                $export_data['performance'] = $performance->get_data();
            }
            
            // Generate export file
            $filename = 'gary_ai_analytics_' . date('Y-m-d_H-i-s') . '.' . $format;
            $file_path = wp_upload_dir()['path'] . '/' . $filename;
            
            if ($format === 'csv') {
                $this->generateCSVExport($export_data, $file_path);
            } else {
                $this->generateJSONExport($export_data, $file_path);
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => wp_upload_dir()['url'] . '/' . $filename,
                    'file_size' => filesize($file_path),
                    'format' => $format
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Export Error: ' . $e->getMessage());
            
            return new WP_Error(
                'export_error',
                __('Failed to export analytics data.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get real-time analytics
     */
    public function getRealtimeAnalytics($request) {
        global $wpdb;
        
        try {
            $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
            $conversations_table = $wpdb->prefix . 'gary_ai_chatbot_conversations';
            
            // Active users (last 5 minutes)
            $active_users = $wpdb->get_var(
                "SELECT COUNT(DISTINCT session_id) FROM $sessions_table 
                 WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
            );
            
            // Recent conversations (last 10)
            $recent_conversations = $wpdb->get_results(
                "SELECT message, response, created_at, session_id 
                 FROM $conversations_table 
                 ORDER BY created_at DESC 
                 LIMIT 10"
            );
            
            // Current response time (last hour average)
            $performance_table = $wpdb->prefix . 'gary_ai_performance';
            $current_response_time = $wpdb->get_var(
                "SELECT AVG(metric_value) FROM $performance_table 
                 WHERE metric_type = 'response_time' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'active_users' => (int) $active_users,
                    'current_response_time' => round((float) $current_response_time / 1000, 2),
                    'recent_conversations' => $recent_conversations,
                    'timestamp' => current_time('mysql')
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Gary AI Realtime Analytics Error: ' . $e->getMessage());
            
            return new WP_Error(
                'realtime_error',
                __('Failed to retrieve real-time analytics.', 'gary-ai'),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Track conversation event
     */
    public function trackConversation($conversation_id, $session_id) {
        $this->recordAnalyticsEvent('conversation_created', [
            'conversation_id' => $conversation_id,
            'session_id' => $session_id
        ], $session_id);
        
        // Update session activity
        $this->updateSessionActivity($session_id);
    }
    
    /**
     * Track feedback event
     */
    public function trackFeedback($feedback_id, $feedback_data) {
        $this->recordAnalyticsEvent('feedback_submitted', [
            'feedback_id' => $feedback_id,
            'feedback_type' => $feedback_data['type'],
            'session_id' => $feedback_data['session_id']
        ], $feedback_data['session_id']);
    }
    
    /**
     * Track response time
     */
    public function trackResponseTime($session_id, $response_time_ms, $request_data) {
        $this->recordPerformanceMetric('response_time', $response_time_ms, $session_id);
        
        $this->recordAnalyticsEvent('response_generated', [
            'response_time_ms' => $response_time_ms,
            'message_length' => strlen($request_data['message'] ?? ''),
            'session_id' => $session_id
        ], $session_id);
    }
    
    /**
     * Record analytics event
     */
    private function recordAnalyticsEvent($event_type, $event_data, $session_id = null) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
        
        $wpdb->insert(
            $analytics_table,
            [
                'event_type' => $event_type,
                'event_data' => json_encode($event_data),
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null,
                'user_ip' => $this->getUserIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'referer_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'created_at' => current_time('mysql')
            ],
            [
                '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
            ]
        );
    }
    
    /**
     * Record performance metric
     */
    private function recordPerformanceMetric($metric_type, $metric_value, $session_id = null) {
        global $wpdb;
        
        $performance_table = $wpdb->prefix . 'gary_ai_performance';
        
        $wpdb->insert(
            $performance_table,
            [
                'metric_type' => $metric_type,
                'metric_value' => $metric_value,
                'session_id' => $session_id,
                'created_at' => current_time('mysql')
            ],
            [
                '%s', '%f', '%s', '%s'
            ]
        );
    }
    
    /**
     * Update session activity
     */
    private function updateSessionActivity($session_id) {
        if (!$session_id) {
            return;
        }
        
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
        
        // Check if session exists
        $session_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $sessions_table WHERE session_id = %s",
            $session_id
        ));
        
        if ($session_exists) {
            // Update existing session
            $wpdb->update(
                $sessions_table,
                [
                    'last_activity' => current_time('mysql'),
                    'messages_sent' => new WP_REST_Request('messages_sent + 1')
                ],
                ['session_id' => $session_id],
                ['%s', '%s'],
                ['%s']
            );
        } else {
            // Create new session
            $wpdb->insert(
                $sessions_table,
                [
                    'session_id' => $session_id,
                    'user_id' => get_current_user_id() ?: null,
                    'user_ip' => $this->getUserIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'first_visit' => current_time('mysql'),
                    'last_activity' => current_time('mysql'),
                    'messages_sent' => 1
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s', '%d']
            );
        }
    }
    
    /**
     * Schedule analytics cleanup
     */
    public function scheduleAnalyticsCleanup() {
        if (!wp_next_scheduled('gary_ai_daily_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'gary_ai_daily_analytics_cleanup');
        }
    }
    
    /**
     * Perform daily analytics cleanup
     */
    public function performDailyCleanup() {
        global $wpdb;
        
        // Delete analytics events older than 90 days
        $analytics_table = $wpdb->prefix . 'gary_ai_analytics';
        $wpdb->query(
            "DELETE FROM $analytics_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        // Delete performance metrics older than 30 days
        $performance_table = $wpdb->prefix . 'gary_ai_performance';
        $wpdb->query(
            "DELETE FROM $performance_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // Mark inactive sessions (no activity for 24 hours)
        $sessions_table = $wpdb->prefix . 'gary_ai_sessions';
        $wpdb->update(
            $sessions_table,
            ['is_active' => 0],
            ['last_activity' => [
                'value' => 'DATE_SUB(NOW(), INTERVAL 24 HOUR)',
                'compare' => '<'
            ]],
            ['%d'],
            ['%s']
        );
    }
    
    /**
     * Helper methods
     */
    
    /**
     * Check analytics permission
     */
    public function checkAnalyticsPermission() {
        return current_user_can('manage_options');
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
    
    /**
     * Get previous period for growth calculations
     */
    private function getPreviousPeriod($current_range) {
        $duration = strtotime($current_range['end']) - strtotime($current_range['start']);
        $prev_end = date('Y-m-d H:i:s', strtotime($current_range['start']));
        $prev_start = date('Y-m-d H:i:s', strtotime($current_range['start']) - $duration);
        
        return ['start' => $prev_start, 'end' => $prev_end];
    }
    
    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    /**
     * Get date format for grouping
     */
    private function getDateFormat($granularity) {
        switch ($granularity) {
            case 'hourly':
                return '%Y-%m-%d %H:00:00';
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%Y-%u';
            case 'monthly':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * Sanitize metrics list
     */
    public function sanitizeMetricsList($metrics) {
        if (!is_array($metrics)) {
            return ['conversations', 'users', 'performance'];
        }
        
        $allowed = ['conversations', 'users', 'performance', 'feedback'];
        return array_intersect($metrics, $allowed);
    }
    
    /**
     * Get user IP address
     */
    private function getUserIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Generate CSV export
     */
    private function generateCSVExport($data, $file_path) {
        $file = fopen($file_path, 'w');
        
        // Write headers
        fputcsv($file, ['Metric', 'Period', 'Value', 'Details']);
        
        foreach ($data as $section => $section_data) {
            if (isset($section_data['data'])) {
                foreach ($section_data['data'] as $metric => $value) {
                    fputcsv($file, [
                        $section . '_' . $metric,
                        $section_data['data']['period'] ?? 'N/A',
                        is_array($value) ? json_encode($value) : $value,
                        ''
                    ]);
                }
            }
        }
        
        fclose($file);
    }
    
    /**
     * Generate JSON export
     */
    private function generateJSONExport($data, $file_path) {
        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    }
} 