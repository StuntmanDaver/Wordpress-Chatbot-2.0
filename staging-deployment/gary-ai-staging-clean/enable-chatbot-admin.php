<?php
/**
 * Gary AI Chatbot Enabler - Add this to the plugin's main file temporarily
 */

// Add this code to gary-ai.php temporarily, then remove after use
add_action('admin_menu', 'gary_ai_enable_menu');
add_action('admin_init', 'gary_ai_handle_enable');

function gary_ai_enable_menu() {
    add_management_page(
        'Enable Gary AI',
        'Enable Gary AI',
        'manage_options',
        'gary-ai-enable',
        'gary_ai_enable_page'
    );
}

function gary_ai_enable_page() {
    ?>
    <div class="wrap">
        <h1>Gary AI Chatbot Enabler</h1>
        
        <?php
        $enabled = get_option('gary_ai_chatbot_enabled', 0);
        $api_key = get_option('gary_ai_api_key', '');
        ?>
        
        <table class="form-table">
            <tr>
                <th>Current Status</th>
                <td><?php echo $enabled ? '<span style="color:green">ENABLED</span>' : '<span style="color:red">DISABLED</span>'; ?></td>
            </tr>
            <tr>
                <th>API Key</th>
                <td><?php echo empty($api_key) ? 'Not Set' : 'Set (' . substr($api_key, 0, 20) . '...)'; ?></td>
            </tr>
        </table>
        
        <form method="post" action="">
            <?php wp_nonce_field('gary_ai_enable'); ?>
            <input type="hidden" name="action" value="enable_gary_ai">
            <p class="submit">
                <input type="submit" class="button-primary" value="Enable Gary AI Chatbot">
            </p>
        </form>
        
        <h3>Manual Commands (if needed):</h3>
        <pre style="background:#f0f0f0; padding:10px; border:1px solid #ccc;">
update_option('gary_ai_chatbot_enabled', 1);
update_option('gary_ai_api_key', 'key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8');
update_option('gary_ai_agent_id', '1ef70a2a-1405-4ba5-9c27-62de4b263e20');
update_option('gary_ai_datastore_id', '6f01eb92-f12a-4113-a39f-3c4013303482');
        </pre>
    </div>
    <?php
}

function gary_ai_handle_enable() {
    if (isset($_POST['action']) && $_POST['action'] === 'enable_gary_ai') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gary_ai_enable')) {
            return;
        }
        
        update_option('gary_ai_chatbot_enabled', 1);
        update_option('gary_ai_api_key', 'key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8');
        update_option('gary_ai_agent_id', '1ef70a2a-1405-4ba5-9c27-62de4b263e20');
        update_option('gary_ai_datastore_id', '6f01eb92-f12a-4113-a39f-3c4013303482');
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Gary AI Chatbot has been enabled!</strong> Visit your website to see the widget.</p></div>';
        });
    }
}
?>
