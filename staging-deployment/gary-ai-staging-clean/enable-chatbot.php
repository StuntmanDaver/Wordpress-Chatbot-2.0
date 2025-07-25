<?php
/**
 * Quick Fix: Enable Gary AI Chatbot Widget
 * 
 * This script directly enables the chatbot and sets up basic configuration
 * Run this in WordPress admin or via wp-cli
 */

// WordPress environment check
if (!defined('ABSPATH')) {
    // If running outside WordPress, try to load WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please run this script from WordPress admin or ensure wp-load.php is accessible.');
    }
}

// Enable the chatbot
update_option('gary_ai_chatbot_enabled', 1);

// Set default configuration
$defaults = [
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

// Set API credentials if not already set
$api_credentials = [
    'gary_ai_api_key' => 'key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8',
    'gary_ai_agent_id' => '1ef70a2a-1405-4ba5-9c27-62de4b263e20',
    'gary_ai_datastore_id' => '6f01eb92-f12a-4113-a39f-3c4013303482'
];

foreach ($api_credentials as $option => $value) {
    if (empty(get_option($option))) {
        update_option($option, $value);
    }
}

// Output success message
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::success('Gary AI chatbot enabled and configured successfully!');
} else {
    echo "<h2>Gary AI Chatbot Enabled Successfully!</h2>";
    echo "<p><strong>Status:</strong> Chatbot is now enabled and should appear on your website.</p>";
    echo "<p><strong>Configuration:</strong> API credentials have been set automatically.</p>";
    echo "<p><strong>Next:</strong> Visit your website to see the chatbot widget in the bottom-right corner.</p>";
}
?>
