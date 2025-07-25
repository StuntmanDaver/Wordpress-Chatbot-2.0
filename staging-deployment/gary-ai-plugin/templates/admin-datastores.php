<?php
/**
 * Admin Datastores Page Template
 * Provides UI for creating, listing, and deleting datastores
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
    if (!isset($_POST['gary_ai_nonce']) || !wp_verify_nonce($_POST['gary_ai_nonce'], 'gary_ai_datastore_action')) {
        wp_die(__('Security check failed. Please try again.', 'gary-ai'));
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_datastore':
                if (isset($_POST['datastore_name']) && !empty($_POST['datastore_name'])) {
                    try {
                        $result = $contextual_client->createDatastore($_POST['datastore_name']);
                        if ($result && isset($result['id'])) {
                            $message = 'Datastore "' . esc_html($_POST['datastore_name']) . '" created successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to create datastore. Please check your API configuration.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error creating datastore: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete_datastore':
                if (isset($_POST['datastore_id']) && !empty($_POST['datastore_id'])) {
                    try {
                        $result = $contextual_client->deleteDatastore($_POST['datastore_id']);
                        if ($result) {
                            $message = 'Datastore deleted successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to delete datastore.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error deleting datastore: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Get list of datastores
$datastores = [];
if (!empty($api_key)) {
    try {
        $datastores = $contextual_client->listDatastores();
    } catch (Exception $e) {
        $message = 'Error loading datastores: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>

<div class="wrap">
    <h1><?php _e('Datastore Management', 'gary-ai'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($api_key)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Please configure your Contextual AI API key in the Settings page before managing datastores.', 'gary-ai'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button"><?php _e('Go to Settings', 'gary-ai'); ?></a></p>
        </div>
    <?php else: ?>
        
        <!-- Create New Datastore Section -->
        <div class="gary-ai-section">
            <h2><?php _e('Create New Datastore', 'gary-ai'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('gary_ai_datastore_action', 'gary_ai_nonce'); ?>
                <input type="hidden" name="action" value="create_datastore">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="datastore_name"><?php _e('Datastore Name', 'gary-ai'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="datastore_name" name="datastore_name" class="regular-text" required>
                            <p class="description"><?php _e('Enter a descriptive name for your datastore.', 'gary-ai'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Datastore', 'gary-ai'), 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <!-- Existing Datastores Section -->
        <div class="gary-ai-section">
            <h2><?php _e('Existing Datastores', 'gary-ai'); ?></h2>
            
            <?php if (empty($datastores)): ?>
                <p><?php _e('No datastores found. Create your first datastore above.', 'gary-ai'); ?></p>
            <?php else: ?>
                <div class="gary-ai-datastores-grid">
                    <?php foreach ($datastores as $datastore): ?>
                        <div class="gary-ai-datastore-card">
                            <div class="datastore-header">
                                <h3><?php echo esc_html($datastore['name'] ?? 'Unnamed Datastore'); ?></h3>
                                <span class="datastore-id"><?php echo esc_html($datastore['id']); ?></span>
                            </div>
                            
                            <div class="datastore-info">
                                <p><strong><?php _e('Created:', 'gary-ai'); ?></strong> 
                                   <?php echo isset($datastore['created_at']) ? esc_html(date('Y-m-d H:i:s', strtotime($datastore['created_at']))) : __('Unknown', 'gary-ai'); ?>
                                </p>
                                
                                <p><strong><?php _e('Documents:', 'gary-ai'); ?></strong> 
                                   <?php echo isset($datastore['document_count']) ? intval($datastore['document_count']) : 0; ?>
                                </p>
                                
                                <?php if (isset($datastore['description']) && !empty($datastore['description'])): ?>
                                    <p><strong><?php _e('Description:', 'gary-ai'); ?></strong> 
                                       <?php echo esc_html($datastore['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="datastore-actions">
                                <a href="<?php echo admin_url('admin.php?page=gary-ai-documents&datastore_id=' . urlencode($datastore['id'])); ?>" 
                                   class="button button-secondary">
                                    <?php _e('Manage Documents', 'gary-ai'); ?>
                                </a>
                                
                                <form method="post" action="" style="display: inline-block;" 
                                      onsubmit="return confirm('<?php _e('Are you sure you want to delete this datastore? This action cannot be undone.', 'gary-ai'); ?>');">
                                    <?php wp_nonce_field('gary_ai_datastore_action', 'gary_ai_nonce'); ?>
                                    <input type="hidden" name="action" value="delete_datastore">
                                    <input type="hidden" name="datastore_id" value="<?php echo esc_attr($datastore['id']); ?>">
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

.gary-ai-datastores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gary-ai-datastore-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #f9f9f9;
}

.datastore-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.datastore-header h3 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.datastore-id {
    font-family: monospace;
    font-size: 12px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}

.datastore-info p {
    margin: 8px 0;
    font-size: 13px;
}

.datastore-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.datastore-actions .button {
    margin-right: 10px;
}

.button-link-delete {
    color: #a00;
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    padding: 0;
}

.button-link-delete:hover {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-refresh datastores list every 30 seconds
    setInterval(function() {
        if ($('.gary-ai-datastores-grid').length > 0) {
            // Only refresh if we're not in the middle of an action
            if (!$('form').hasClass('submitting')) {
                location.reload();
            }
        }
    }, 30000);
    
    // Add submitting class to forms to prevent auto-refresh during submission
    $('form').on('submit', function() {
        $(this).addClass('submitting');
    });
});
</script>
