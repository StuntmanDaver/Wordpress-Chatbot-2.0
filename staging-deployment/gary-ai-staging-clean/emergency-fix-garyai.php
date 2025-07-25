<?php
/**
 * Emergency Fix for "Undefined constant 'garyAI'" Error
 * 
 * This script creates a clean, working version of the gary-ai.php file
 * with all potential garyAI constant issues resolved.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

echo "<h2>Emergency Fix for garyAI Constant Error</h2>\n";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";

// Step 1: Backup current file
$plugin_file = __DIR__ . '/gary-ai.php';
$backup_file = __DIR__ . '/gary-ai-backup-' . date('Y-m-d-H-i-s') . '.php';

if (file_exists($plugin_file)) {
    if (copy($plugin_file, $backup_file)) {
        echo "<span style='color: green;'>✓ Backup created: " . basename($backup_file) . "</span><br>\n";
    } else {
        echo "<span style='color: red;'>✗ Failed to create backup</span><br>\n";
        exit;
    }
} else {
    echo "<span style='color: red;'>✗ Plugin file not found: {$plugin_file}</span><br>\n";
    exit;
}

// Step 2: Read current file content
$content = file_get_contents($plugin_file);
if (!$content) {
    echo "<span style='color: red;'>✗ Failed to read plugin file</span><br>\n";
    exit;
}

echo "<span style='color: blue;'>Original file size: " . strlen($content) . " bytes</span><br>\n";

// Step 3: Fix potential garyAI constant issues
$fixes_applied = 0;

// Fix 1: Ensure all garyAI references are properly quoted or handled
$patterns_to_fix = [
    // Fix unquoted garyAI in JavaScript context
    '/([^\'"])garyAI([^\'"])/' => '$1\'garyAI\'$2',
    // Fix any direct garyAI constant references
    '/\bgaryAI\b(?!\s*[=:])/' => '\'garyAI\'',
    // Fix any echo statements with unquoted garyAI
    '/echo\s+[^;]*garyAI[^;]*;/' => '',
];

foreach ($patterns_to_fix as $pattern => $replacement) {
    $new_content = preg_replace($pattern, $replacement, $content);
    if ($new_content !== $content) {
        $content = $new_content;
        $fixes_applied++;
    }
}

// Step 4: Ensure proper JavaScript localization
$localization_fix = "
        // Localize script with proper error handling
        if (wp_script_is('gary-ai-chat-widget', 'enqueued')) {
            wp_localize_script('gary-ai-chat-widget', 'garyAI', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gary_ai_nonce'),
                'strings' => [
                    'placeholder' => __('Type your message...', 'gary-ai'),
                    'send' => __('Send', 'gary-ai'),
                    'connecting' => __('Connecting...', 'gary-ai'),
                    'error' => __('Something went wrong. Please try again.', 'gary-ai'),
                    'agent_name' => get_option('gary_ai_chatbot_name', 'Gary AI'),
                    'thinking' => __('Thinking...', 'gary-ai'),
                    'offline' => __('You appear to be offline. Please check your internet connection.', 'gary-ai')
                ]
            ]);
        }";

// Replace the existing localization if it exists
if (strpos($content, 'wp_localize_script(\'gary-ai-chat-widget\', \'garyAI\'') !== false) {
    $content = preg_replace(
        '/wp_localize_script\(\'gary-ai-chat-widget\',\s*\'garyAI\',\s*\[[^\]]+\]\);/',
        trim($localization_fix),
        $content
    );
    $fixes_applied++;
}

// Step 5: Ensure addWidgetContainer method is clean
$clean_widget_method = '
    /**
     * Add widget container to footer
     */
    public function addWidgetContainer() {
        // Only add if chatbot is enabled
        if (!get_option(\'gary_ai_chatbot_enabled\', 0)) {
            return;
        }
        
        $position = get_option(\'gary_ai_widget_position\', \'bottom-right\');
        echo \'<div id="gary-ai-widget-container" class="gary-ai-widget-\' . esc_attr($position) . \'"></div>\';
    }';

// Replace the addWidgetContainer method if it exists
if (strpos($content, 'function addWidgetContainer') !== false) {
    $content = preg_replace(
        '/public\s+function\s+addWidgetContainer\(\)\s*\{[^}]+\}/',
        trim($clean_widget_method),
        $content
    );
    $fixes_applied++;
}

// Step 6: Write the fixed content back to file
if (file_put_contents($plugin_file, $content)) {
    echo "<span style='color: green;'>✓ Fixed file written successfully</span><br>\n";
    echo "<span style='color: blue;'>New file size: " . strlen($content) . " bytes</span><br>\n";
    echo "<span style='color: blue;'>Fixes applied: {$fixes_applied}</span><br>\n";
} else {
    echo "<span style='color: red;'>✗ Failed to write fixed file</span><br>\n";
    exit;
}

// Step 7: Verify the fix
echo "<h3>Verification</h3>\n";

// Check for any remaining garyAI constant references
$lines = explode("\n", $content);
$problematic_lines = [];

foreach ($lines as $line_num => $line) {
    // Look for unquoted garyAI that could be interpreted as a constant
    if (preg_match('/\bgaryAI\b(?![\'"])/', $line) && !preg_match('/wp_localize_script|\/\*|\/\/|\*/', $line)) {
        $problematic_lines[] = ($line_num + 1) . ': ' . trim($line);
    }
}

if (empty($problematic_lines)) {
    echo "<span style='color: green;'>✓ No problematic garyAI references found</span><br>\n";
} else {
    echo "<span style='color: orange;'>⚠ Potential issues found:</span><br>\n";
    foreach ($problematic_lines as $line) {
        echo "<code style='background: #fff; padding: 2px; border: 1px solid #ddd; display: block;'>{$line}</code>\n";
    }
}

echo "<h3>Next Steps</h3>\n";
echo "1. Clear all WordPress caches<br>\n";
echo "2. Clear PHP opcode cache if available<br>\n";
echo "3. Test the plugin functionality<br>\n";
echo "4. If issues persist, restore from backup: " . basename($backup_file) . "<br>\n";

echo "</div>\n";
?>
