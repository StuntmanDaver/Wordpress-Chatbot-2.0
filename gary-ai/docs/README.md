# Gary AI WordPress Plugin: Complete Guide

This document provides a comprehensive overview of the Gary AI WordPress plugin, including its architecture, installation, troubleshooting, and development guidelines.

## 1. Project Overview

The Gary AI plugin integrates a customizable, AI-powered chatbot into a WordPress site. It is designed to be easy to configure and deploy, providing a seamless user experience.

### Key Features:

*   **AI-Powered Chat:** Connects to the Contextual AI API to provide intelligent, conversational responses.
*   **Customizable Widget:** The chat widget's appearance and position can be customized.
*   **WordPress Integration:** Fully integrated with the WordPress dashboard for easy management.
*   **Secure:** Uses API keys for secure communication with the AI service.

## 2. Installation and Setup

Follow these steps to ensure a clean and successful installation:

### Step 1: Clean Up Old Versions

Before installing, it's critical to remove any previous versions of the plugin to avoid conflicts.

1.  **Deactivate and Delete the Plugin:** In the WordPress admin panel, go to "Plugins," find "Gary AI," and click "Deactivate," then "Delete."
2.  **Clean the Database:** Run the following SQL query to remove any leftover settings from your WordPress database:
    ```sql
    DELETE FROM wp_options WHERE option_name LIKE 'gary_ai_%';
    ```

### Step 2: Install the Plugin

1.  **Download:** Get the latest version of the plugin, named `gary-ai.zip`.
2.  **Upload:** In the WordPress admin panel, go to "Plugins" > "Add New" > "Upload Plugin."
3.  **Activate:** Select the `gary-ai.zip` file and click "Install Now," then "Activate Plugin."

### Step 3: Configure Credentials

1.  **Navigate to Settings:** Go to the "Gary AI" settings page in the WordPress admin panel.
2.  **Enter Credentials:** Fill in the following fields with the correct values:
    *   **API Key:** Your Contextual AI API key.
    *   **Agent ID:** The ID of your AI agent.
    *   **Datastore ID:** The ID for your data store.
3.  **Save and Test:** Save the settings and click the "Test Connection" button to verify that the credentials are correct.

## 3. Troubleshooting

If you encounter issues, follow this systematic guide to diagnose and resolve the problem.

### Common Issues and Solutions

| Issue                      | Solution                                                                                                                              |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| **Widget Not Appearing**  | 1. **Check Credentials:** Ensure the API key and other IDs are correct. <br> 2. **Verify Assets:** Make sure the CSS and JS files are loading correctly (check the browser's developer console for 404 errors). <br> 3. **Theme/Plugin Conflict:** Temporarily switch to a default WordPress theme and disable other plugins to isolate the issue. |
| **Connection Errors**      | 1. **Test API from Server:** Use `curl` to test the API connection directly from your server. <br> 2. **Check Hosting Restrictions:** Some hosting providers may block outbound API requests. |
| **AJAX Errors in Admin**   | 1. **Check Browser Console:** Look for JavaScript errors in the browser's developer console. <br> 2. **Verify Action Names:** Ensure that the AJAX action names in the JavaScript code match the hooks in the PHP code. |

### Diagnostic Script

For advanced troubleshooting, you can use this diagnostic script. Save it as `diagnostic.php` in your WordPress root directory and access it via your browser.

```php
<?php
// WordPress diagnostic - upload as diagnostic.php to WordPress root
require_once('wp-load.php');

echo "<h1>Gary AI Plugin Diagnostic</h1>";

// Check plugin status
echo "<h2>1. Plugin Status</h2>";
if (is_plugin_active('gary-ai/gary-ai.php')) {
    echo "✅ Plugin is active<br>";
} else {
    echo "❌ Plugin is NOT active<br>";
}

// Check file details
$plugin_file = WP_PLUGIN_DIR . '/gary-ai/gary-ai.php';
if (file_exists($plugin_file)) {
    $size = filesize($plugin_file);
    $modified = date('Y-m-d H:i:s', filemtime($plugin_file));
    echo "✅ Main file exists: {$size} bytes, modified {$modified}<br>";
} else {
    echo "❌ Main plugin file NOT found<br>";
}

// Check credentials
echo "<h2>2. Stored Credentials</h2>";
$api_key = get_option('gary_ai_contextual_api_key');
$agent_id = get_option('gary_ai_agent_id');
$datastore_id = get_option('gary_ai_datastore_id');

echo "API Key: " . (empty($api_key) ? "❌ EMPTY" : "✅ SET (" . substr($api_key, 0, 20) . "...)") . "<br>";
echo "Agent ID: " . (empty($agent_id) ? "❌ EMPTY" : "✅ SET (" . $agent_id . ")") . "<br>";
echo "Datastore ID: " . (empty($datastore_id) ? "❌ EMPTY" : "✅ SET (" . $datastore_id . ")") . "<br>";

// Check widget settings
echo "<h2>3. Widget Settings</h2>";
$widget_enabled = get_option('gary_ai_widget_enabled', true);
echo "Widget Enabled: " . ($widget_enabled ? "✅ YES" : "❌ NO") . "<br>";
?>
```

## 4. Development

### Project Structure

The final plugin structure should be as follows:

```
gary-ai/
├── gary-ai.php
├── includes/
│   └── class-contextual-ai-client.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── chat-widget.css
│   └── js/
│       ├── admin.js
│       └── chat-widget.js
├── docs/
│   └── README.md
└── release/
    ├── gary-ai.zip
    └── gary-ai/
```

### Build Process

The project uses a build process to create the final `gary-ai.zip` file. The `create-release.js` script handles this process, which includes:

1.  Copying the necessary files to a temporary directory.
2.  Generating the final `gary-ai.zip` package.
3.  Cleaning up temporary files.

### Coding Standards

*   Follow WordPress coding standards for PHP and JavaScript.
*   All text should be translatable.
*   Document all functions, classes, and hooks. 