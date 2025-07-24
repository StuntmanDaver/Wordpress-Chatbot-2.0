<?php
/**
 * Gary AI Uninstall
 *
 * This file is triggered when the plugin is uninstalled from WordPress.
 * It is responsible for cleaning up all plugin-related data.
 *
 * @package GaryAI
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Ensure WordPress is loaded.
if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

/**
 * Cleanup all Gary AI plugin data.
 */
function gary_ai_uninstall_cleanup() {
    global $wpdb;

    // List of tables to drop.
    $tables = [
        $wpdb->prefix . 'gary_ai_conversations',
        $wpdb->prefix . 'gary_ai_analytics',
        $wpdb->prefix . 'gary_ai_performance',
        $wpdb->prefix . 'gary_ai_sessions',
    ];

    // Drop tables.
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }

    // List of options to delete.
    $options = [
        'gary_ai_version',
        'gary_ai_settings',
        'gary_ai_contextual_api_key',
        'gary_ai_agent_id',
        'gary_ai_datastore_id',
        'gary_ai_chatbot_enabled',
    ];

    // Delete options.
    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear any transients.
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_gary\_ai\_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_gary\_ai\_%'");

    // Clear any scheduled hooks.
    wp_clear_scheduled_hook('gary_ai_daily_cleanup');
}

// Run the cleanup function.
gary_ai_uninstall_cleanup(); 