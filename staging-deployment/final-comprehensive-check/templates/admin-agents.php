<?php
/**
 * Admin Agents Page Template
 * Provides UI for creating and configuring agents with specific datastores
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the Contextual AI client
$contextual_client = new ContextualAIClient();
$api_key = get_option('gary_ai_contextual_api_key', '');

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST) {
    // Verify nonce for security
    if (!isset($_POST['gary_ai_nonce']) || !wp_verify_nonce($_POST['gary_ai_nonce'], 'gary_ai_agent_action')) {
        wp_die(__('Security check failed. Please try again.', 'gary-ai'));
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_agent':
                if (isset($_POST['agent_name']) && !empty($_POST['agent_name'])) {
                    try {
                        $agent_data = [
                            'name' => sanitize_text_field($_POST['agent_name']),
                            'datastore_id' => sanitize_text_field($_POST['datastore_id']),
                            'system_prompt' => sanitize_textarea_field($_POST['system_prompt']),
                            'temperature' => floatval($_POST['temperature']),
                            'max_tokens' => intval($_POST['max_tokens'])
                        ];
                        
                        $result = $contextual_client->createAgent($agent_data);
                        if ($result && isset($result['id'])) {
                            $message = 'Agent "' . esc_html($_POST['agent_name']) . '" created successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to create agent. Please check your configuration.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error creating agent: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'update_agent':
                if (isset($_POST['agent_id']) && !empty($_POST['agent_id'])) {
                    try {
                        $agent_data = [
                            'name' => sanitize_text_field($_POST['agent_name']),
                            'datastore_id' => sanitize_text_field($_POST['datastore_id']),
                            'system_prompt' => sanitize_textarea_field($_POST['system_prompt']),
                            'temperature' => floatval($_POST['temperature']),
                            'max_tokens' => intval($_POST['max_tokens'])
                        ];
                        
                        $result = $contextual_client->updateAgent($_POST['agent_id'], $agent_data);
                        if ($result) {
                            $message = 'Agent updated successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to update agent.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error updating agent: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete_agent':
                if (isset($_POST['agent_id']) && !empty($_POST['agent_id'])) {
                    try {
                        $result = $contextual_client->deleteAgent($_POST['agent_id']);
                        if ($result) {
                            $message = 'Agent deleted successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to delete agent.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error deleting agent: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'set_default_agent':
                if (isset($_POST['agent_id']) && !empty($_POST['agent_id'])) {
                    update_option('gary_ai_agent_id', sanitize_text_field($_POST['agent_id']));
                    $message = 'Default agent updated successfully!';
                    $message_type = 'success';
                }
                break;
        }
    }
}

// Get list of datastores and agents
$datastores = [];
$agents = [];
$current_agent_id = get_option('gary_ai_agent_id', '');

if (!empty($api_key)) {
    try {
        $datastores = $contextual_client->listDatastores();
        $agents = $contextual_client->listAgents();
    } catch (Exception $e) {
        $message = 'Error loading data: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle edit mode
$edit_agent = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_agent_id = sanitize_text_field($_GET['edit']);
    foreach ($agents as $agent) {
        if ($agent['id'] === $edit_agent_id) {
            $edit_agent = $agent;
            break;
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Agent Management', 'gary-ai'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($api_key)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Please configure your Contextual AI API key in the Settings page before managing agents.', 'gary-ai'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button"><?php _e('Go to Settings', 'gary-ai'); ?></a></p>
        </div>
    <?php elseif (empty($datastores)): ?>
        <div class="notice notice-warning">
            <p><?php _e('No datastores found. Please create a datastore first before creating agents.', 'gary-ai'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=gary-ai-datastores'); ?>" class="button"><?php _e('Create Datastore', 'gary-ai'); ?></a></p>
        </div>
    <?php else: ?>
        
        <!-- Create/Edit Agent Section -->
        <div class="gary-ai-section">
            <h2><?php echo $edit_agent ? __('Edit Agent', 'gary-ai') : __('Create New Agent', 'gary-ai'); ?></h2>
            
            <?php if ($edit_agent): ?>
                <p><a href="<?php echo admin_url('admin.php?page=gary-ai-agents'); ?>" class="button button-secondary"><?php _e('← Back to Agents', 'gary-ai'); ?></a></p>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('gary_ai_agent_action', 'gary_ai_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $edit_agent ? 'update_agent' : 'create_agent'; ?>">
                <?php if ($edit_agent): ?>
                    <input type="hidden" name="agent_id" value="<?php echo esc_attr($edit_agent['id']); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="agent_name"><?php _e('Agent Name', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="agent_name" name="agent_name" class="regular-text" 
                                   value="<?php echo $edit_agent ? esc_attr($edit_agent['name']) : ''; ?>" required>
                            <p class="description"><?php _e('Enter a descriptive name for your agent.', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="datastore_id"><?php _e('Datastore', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <select id="datastore_id" name="datastore_id" required>
                                <option value=""><?php _e('Select a datastore...', 'gary-ai'); ?></option>
                                <?php foreach ($datastores as $datastore): ?>
                                    <option value="<?php echo esc_attr($datastore['id']); ?>" 
                                            <?php echo ($edit_agent && $edit_agent['datastore_id'] === $datastore['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($datastore['name'] ?? $datastore['id']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select the datastore this agent will use for knowledge.', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="system_prompt"><?php _e('System Prompt', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <textarea id="system_prompt" name="system_prompt" rows="6" class="large-text"><?php 
                                echo $edit_agent ? esc_textarea($edit_agent['system_prompt']) : 'You are a helpful AI assistant. Use the provided knowledge base to answer questions accurately and helpfully.'; 
                            ?></textarea>
                            <p class="description"><?php _e('Define how the agent should behave and respond to users.', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="temperature"><?php _e('Temperature', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="temperature" name="temperature" min="0" max="1" step="0.1" 
                                   value="<?php echo $edit_agent ? esc_attr($edit_agent['temperature']) : '0.7'; ?>">
                            <p class="description"><?php _e('Controls randomness in responses (0.0 = deterministic, 1.0 = very creative).', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_tokens"><?php _e('Max Tokens', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_tokens" name="max_tokens" min="100" max="4000" 
                                   value="<?php echo $edit_agent ? esc_attr($edit_agent['max_tokens']) : '1000'; ?>">
                            <p class="description"><?php _e('Maximum length of agent responses.', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button($edit_agent ? __('Update Agent', 'gary-ai') : __('Create Agent', 'gary-ai'), 'primary'); ?>
            </form>
        </div>
        
        <?php if (!$edit_agent): ?>
            <!-- Existing Agents Section -->
            <div class="gary-ai-section">
                <h2><?php _e('Existing Agents', 'gary-ai'); ?></h2>
                
                <?php if (empty($agents)): ?>
                    <p><?php _e('No agents found. Create your first agent above.', 'gary-ai'); ?></p>
                <?php else: ?>
                    <div class="gary-ai-agents-grid">
                        <?php foreach ($agents as $agent): ?>
                            <div class="gary-ai-agent-card <?php echo ($agent['id'] === $current_agent_id) ? 'is-default' : ''; ?>">
                                <div class="agent-header">
                                    <h3><?php echo esc_html($agent['name'] ?? 'Unnamed Agent'); ?></h3>
                                    <span class="agent-id"><?php echo esc_html($agent['id']); ?></span>
                                    <?php if ($agent['id'] === $current_agent_id): ?>
                                        <span class="default-badge"><?php _e('Default', 'gary-ai'); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="agent-info">
                                    <p><strong><?php _e('Datastore:', 'gary-ai'); ?></strong> 
                                       <?php 
                                       $datastore_name = 'Unknown';
                                       foreach ($datastores as $ds) {
                                           if ($ds['id'] === $agent['datastore_id']) {
                                               $datastore_name = $ds['name'] ?? $ds['id'];
                                               break;
                                           }
                                       }
                                       echo esc_html($datastore_name);
                                       ?>
                                    </p>
                                    
                                    <p><strong><?php _e('Temperature:', 'gary-ai'); ?></strong> 
                                       <?php echo esc_html($agent['temperature'] ?? '0.7'); ?>
                                    </p>
                                    
                                    <p><strong><?php _e('Max Tokens:', 'gary-ai'); ?></strong> 
                                       <?php echo esc_html($agent['max_tokens'] ?? '1000'); ?>
                                    </p>
                                    
                                    <?php if (isset($agent['system_prompt']) && !empty($agent['system_prompt'])): ?>
                                        <p><strong><?php _e('System Prompt:', 'gary-ai'); ?></strong></p>
                                        <div class="system-prompt-preview">
                                            <?php echo esc_html(wp_trim_words($agent['system_prompt'], 20)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="agent-actions">
                                    <a href="<?php echo admin_url('admin.php?page=gary-ai-agents&edit=' . urlencode($agent['id'])); ?>" 
                                       class="button button-secondary">
                                        <?php _e('Edit', 'gary-ai'); ?>
                                    </a>
                                    
                                    <?php if ($agent['id'] !== $current_agent_id): ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <?php wp_nonce_field('gary_ai_agent_action', 'gary_ai_nonce'); ?>
                                            <input type="hidden" name="action" value="set_default_agent">
                                            <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent['id']); ?>">
                                            <button type="submit" class="button button-primary">
                                                <?php _e('Set as Default', 'gary-ai'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="" style="display: inline-block;" 
                                          onsubmit="return confirm('<?php _e('Are you sure you want to delete this agent? This action cannot be undone.', 'gary-ai'); ?>');">
                                        <?php wp_nonce_field('gary_ai_agent_action', 'gary_ai_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_agent">
                                        <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent['id']); ?>">
                                        <button type="submit" class="button button-link-delete">
                                            <?php _e('Delete', 'gary-ai'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<style>
.gary-ai-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.gary-ai-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.gary-ai-agents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gary-ai-agent-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #f9f9f9;
    position: relative;
}

.gary-ai-agent-card.is-default {
    border-color: #0073aa;
    background: #f0f8ff;
}

.agent-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
    position: relative;
}

.agent-header h3 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.agent-id {
    font-family: monospace;
    font-size: 12px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}

.default-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.agent-info p {
    margin: 8px 0;
    font-size: 13px;
}

.system-prompt-preview {
    background: #f8f8f8;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 8px;
    font-size: 12px;
    color: #666;
    font-style: italic;
    max-height: 60px;
    overflow: hidden;
}

.agent-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.agent-actions .button {
    margin-right: 10px;
    margin-bottom: 5px;
}

.button-link-delete {
    color: #a00;
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    padding: 4px 8px;
}

.button-link-delete:hover {
    color: #dc3232;
    background-color: #f0f0f0;
    border-radius: 3px;
}

#system_prompt {
    font-family: monospace;
    font-size: 13px;
}

#datastore_id {
    min-width: 300px;
}

.form-table th {
    width: 200px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Temperature slider visual feedback
    $('#temperature').on('input', function() {
        var value = $(this).val();
        var description = '';
        
        if (value <= 0.3) {
            description = '<?php _e('Very focused and deterministic', 'gary-ai'); ?>';
        } else if (value <= 0.7) {
            description = '<?php _e('Balanced creativity and focus', 'gary-ai'); ?>';
        } else {
            description = '<?php _e('Very creative and varied', 'gary-ai'); ?>';
        }
        
        $(this).next('.description').html('<?php _e('Controls randomness in responses (0.0 = deterministic, 1.0 = very creative).', 'gary-ai'); ?> <strong>' + description + '</strong>');
    });
    
    // Auto-save form data to localStorage
    $('form input, form textarea, form select').on('change', function() {
        var formData = {};
        $('form input, form textarea, form select').each(function() {
            if ($(this).attr('name') && $(this).attr('name') !== 'gary_ai_nonce') {
                formData[$(this).attr('name')] = $(this).val();
            }
        });
        localStorage.setItem('gary_ai_agent_form', JSON.stringify(formData));
    });
    
    // Restore form data from localStorage
    var savedData = localStorage.getItem('gary_ai_agent_form');
    if (savedData && !$('input[name="agent_id"]').length) { // Only for new agents
        try {
            var formData = JSON.parse(savedData);
            Object.keys(formData).forEach(function(key) {
                $('[name="' + key + '"]').val(formData[key]);
            });
        } catch (e) {
            // Ignore parsing errors
        }
    }
    
    // Clear saved data on successful submission
    if ($('.notice-success').length) {
        localStorage.removeItem('gary_ai_agent_form');
    }
});
</script>
