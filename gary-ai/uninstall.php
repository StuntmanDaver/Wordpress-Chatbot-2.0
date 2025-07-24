<?php
/**
 * Gary AI Plugin Uninstall Script
 * 
 * This file is executed when the plugin is deleted through WordPress admin.
 * It handles cleanup of database tables, options, and any other plugin data.
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check - ensure this is being called properly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean up plugin data
 */
function gary_ai_uninstall_cleanup() {
    global $wpdb;
    
    try {
        // Get table names
        $tables = [
            $wpdb->prefix . 'gary_ai_conversations',
            $wpdb->prefix . 'gary_ai_analytics',
            $wpdb->prefix . 'gary_ai_performance',
            $wpdb->prefix . 'gary_ai_sessions'
        ];
        
        // Drop tables if they exist
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table));
        }
        
        // Clean up options
        $options_to_delete = [
            'gary_ai_chatbot_enabled',
            'gary_ai_contextual_api_key',
            'gary_ai_agent_id',
            'gary_ai_datastore_id',
            'gary_ai_widget_position',
            'gary_ai_primary_color',
            'gary_ai_chatbot_name',
            'gary_ai_welcome_message',
            'gary_ai_analytics_enabled',
            'gary_ai_data_retention',
            'gary_ai_real_time_updates',
            'gary_ai_widget_theme'
        ];
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // Clean up transients
        delete_transient('gary_ai_api_cache');
        delete_transient('gary_ai_analytics_cache');
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('gary_ai_cleanup_sessions');
        wp_clear_scheduled_hook('gary_ai_analytics_cleanup');
        
        // Log successful cleanup
        error_log('Gary AI Plugin: Uninstall cleanup completed successfully');
        
    } catch (Exception $e) {
        // Log any errors but don't stop the uninstall process
        error_log('Gary AI Plugin: Uninstall cleanup error: ' . $e->getMessage());
    }
}

// Execute cleanup
gary_ai_uninstall_cleanup(); 