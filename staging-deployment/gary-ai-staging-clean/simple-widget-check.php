<?php
/**
 * Simple Widget Check - Run this in WordPress admin
 * 
 * Add this code to your WordPress admin (Tools > Theme Editor or via wp-admin/admin.php)
 */

// Check current chatbot status
$chatbot_enabled = get_option('gary_ai_chatbot_enabled', 0);
$widget_position = get_option('gary_ai_widget_position', 'bottom-right');
$api_key = get_option('gary_ai_api_key', '');

echo "<h2>Gary AI Chatbot Status Check</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><td><strong>Setting</strong></td><td><strong>Current Value</strong></td><td><strong>Status</strong></td></tr>";

echo "<tr>";
echo "<td>Chatbot Enabled</td>";
echo "<td>" . ($chatbot_enabled ? 'YES (1)' : 'NO (0)') . "</td>";
echo "<td>" . ($chatbot_enabled ? '<span style="color:green">✓ ENABLED</span>' : '<span style="color:red">✗ DISABLED</span>') . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>Widget Position</td>";
echo "<td>{$widget_position}</td>";
echo "<td><span style='color:blue'>ℹ OK</span></td>";
echo "</tr>";

echo "<tr>";
echo "<td>API Key</td>";
echo "<td>" . (empty($api_key) ? 'NOT SET' : 'SET (' . substr($api_key, 0, 20) . '...)') . "</td>";
echo "<td>" . (empty($api_key) ? '<span style="color:orange">⚠ MISSING</span>' : '<span style="color:green">✓ SET</span>') . "</td>";
echo "</tr>";

echo "</table>";

// Show fix if needed
if (!$chatbot_enabled) {
    echo "<h3 style='color:red'>ISSUE FOUND: Chatbot is Disabled</h3>";
    echo "<p>This is why your widget doesn't appear on the website.</p>";
    echo "<p><strong>To fix this, run the following code:</strong></p>";
    echo "<pre style='background:#f0f0f0; padding:10px; border:1px solid #ccc;'>";
    echo "update_option('gary_ai_chatbot_enabled', 1);\n";
    echo "update_option('gary_ai_api_key', 'key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8');\n";
    echo "update_option('gary_ai_agent_id', '1ef70a2a-1405-4ba5-9c27-62de4b263e20');\n";
    echo "update_option('gary_ai_datastore_id', '6f01eb92-f12a-4113-a39f-3c4013303482');";
    echo "</pre>";
} else {
    echo "<h3 style='color:green'>Chatbot is Enabled</h3>";
    echo "<p>The widget should appear on your website. If it doesn't, check:</p>";
    echo "<ul>";
    echo "<li>Browser console for JavaScript errors</li>";
    echo "<li>Network tab for failed asset requests</li>";
    echo "<li>Page source for the widget container HTML</li>";
    echo "</ul>";
}
?>
