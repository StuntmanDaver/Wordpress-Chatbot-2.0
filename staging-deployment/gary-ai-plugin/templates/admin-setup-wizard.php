<?php
/**
 * Admin Setup Wizard Page Template
 * Guides users through the complete Gary AI setup workflow
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the Contextual AI client
$contextual_client = new ContextualAIClient();

// Get current configuration
$api_key = get_option('gary_ai_contextual_api_key', '');
$agent_id = get_option('gary_ai_agent_id', '');
$datastore_id = get_option('gary_ai_datastore_id', '');
$chatbot_enabled = get_option('gary_ai_chatbot_enabled', 0);

// Determine current step
$current_step = 1;
$steps_completed = [];

// Step 1: API Configuration
if (!empty($api_key)) {
    $steps_completed[1] = true;
    $current_step = 2;
}

// Step 2: Datastore Creation
$datastores = [];
if (!empty($api_key)) {
    try {
        $datastores = $contextual_client->listDatastores();
        if (!empty($datastores)) {
            $steps_completed[2] = true;
            $current_step = 3;
        }
    } catch (Exception $e) {
        // API key might be invalid
    }
}

// Step 3: Document Upload
$has_documents = false;
if (!empty($datastores)) {
    foreach ($datastores as $datastore) {
        try {
            $documents = $contextual_client->listDocuments($datastore['id']);
            if (!empty($documents)) {
                $has_documents = true;
                break;
            }
        } catch (Exception $e) {
            // Continue checking other datastores
        }
    }
    if ($has_documents) {
        $steps_completed[3] = true;
        $current_step = 4;
    }
}

// Step 4: Agent Configuration
$agents = [];
if (!empty($api_key)) {
    try {
        $agents = $contextual_client->listAgents();
        if (!empty($agents)) {
            $steps_completed[4] = true;
            $current_step = 5;
        }
    } catch (Exception $e) {
        // Continue
    }
}

// Step 5: Final Configuration
if (!empty($agent_id) && $chatbot_enabled) {
    $steps_completed[5] = true;
    $current_step = 6; // Complete
}

// Handle quick actions
$message = '';
$message_type = '';

if ($_POST && isset($_POST['quick_action'])) {
    // Verify nonce for security based on the specific quick action
    $action = sanitize_text_field($_POST['quick_action']);
    $nonce_field = $action . '_nonce';
    $nonce_action = 'gary_ai_quick_action_' . $action;
    
    if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
        wp_die(__('Security check failed. Please try again.', 'gary-ai'));
    }
    
    switch ($_POST['quick_action']) {
        case 'test_connection':
            if (!empty($api_key)) {
                try {
                    $test_result = $contextual_client->testConnection();
                    if ($test_result) {
                        $message = 'API connection successful!';
                        $message_type = 'success';
                    } else {
                        $message = 'API connection failed. Please check your API key.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Connection error: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
            break;
            
        case 'enable_chatbot':
            update_option('gary_ai_chatbot_enabled', 1);
            $message = 'Chatbot enabled successfully!';
            $message_type = 'success';
            $chatbot_enabled = 1;
            break;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Gary AI Setup Wizard', 'gary-ai'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Progress Bar -->
    <div class="gary-ai-progress-container">
        <div class="gary-ai-progress-bar">
            <div class="progress-fill" style="width: <?php echo (count($steps_completed) / 5) * 100; ?>%"></div>
        </div>
        <p class="progress-text">
            <?php printf(__('Step %d of 5 completed', 'gary-ai'), count($steps_completed)); ?>
        </p>
    </div>
    
    <!-- Setup Steps -->
    <div class="gary-ai-setup-steps">
        
        <!-- Step 1: API Configuration -->
        <div class="setup-step <?php echo isset($steps_completed[1]) ? 'completed' : ($current_step === 1 ? 'active' : 'pending'); ?>">
            <div class="step-header">
                <span class="step-number">1</span>
                <h2><?php _e('API Configuration', 'gary-ai'); ?></h2>
                <?php if (isset($steps_completed[1])): ?>
                    <span class="step-status completed">✓</span>
                <?php endif; ?>
            </div>
            
            <div class="step-content">
                <?php if (isset($steps_completed[1])): ?>
                    <p class="step-success"><?php _e('API key configured successfully!', 'gary-ai'); ?></p>
                    <p><strong><?php _e('API Key:', 'gary-ai'); ?></strong> <?php echo esc_html(substr($api_key, 0, 20) . '...'); ?></p>
                    
                    <form method="post" action="" style="display: inline-block;">
                        <?php wp_nonce_field('gary_ai_quick_action_test_connection', 'test_connection_nonce'); ?>
                        <input type="hidden" name="quick_action" value="test_connection">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Test Connection', 'gary-ai'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p><?php _e('Configure your Contextual AI API key to get started.', 'gary-ai'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button button-primary">
                        <?php _e('Configure API Key', 'gary-ai'); ?>
                    </a></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Step 2: Datastore Creation -->
        <div class="setup-step <?php echo isset($steps_completed[2]) ? 'completed' : ($current_step === 2 ? 'active' : 'pending'); ?>">
            <div class="step-header">
                <span class="step-number">2</span>
                <h2><?php _e('Create Datastore', 'gary-ai'); ?></h2>
                <?php if (isset($steps_completed[2])): ?>
                    <span class="step-status completed">✓</span>
                <?php endif; ?>
            </div>
            
            <div class="step-content">
                <?php if (isset($steps_completed[2])): ?>
                    <p class="step-success"><?php printf(__('You have %d datastore(s) created.', 'gary-ai'), count($datastores)); ?></p>
                    <ul class="datastore-list">
                        <?php foreach (array_slice($datastores, 0, 3) as $datastore): ?>
                            <li><?php echo esc_html($datastore['name'] ?? $datastore['id']); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($datastores) > 3): ?>
                            <li><?php printf(__('... and %d more', 'gary-ai'), count($datastores) - 3); ?></li>
                        <?php endif; ?>
                    </ul>
                <?php elseif ($current_step >= 2): ?>
                    <p><?php _e('Create a datastore to organize your knowledge base.', 'gary-ai'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=gary-ai-datastores'); ?>" class="button button-primary">
                        <?php _e('Create Datastore', 'gary-ai'); ?>
                    </a></p>
                <?php else: ?>
                    <p class="step-pending"><?php _e('Complete API configuration first.', 'gary-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Step 3: Document Upload -->
        <div class="setup-step <?php echo isset($steps_completed[3]) ? 'completed' : ($current_step === 3 ? 'active' : 'pending'); ?>">
            <div class="step-header">
                <span class="step-number">3</span>
                <h2><?php _e('Upload Documents', 'gary-ai'); ?></h2>
                <?php if (isset($steps_completed[3])): ?>
                    <span class="step-status completed">✓</span>
                <?php endif; ?>
            </div>
            
            <div class="step-content">
                <?php if (isset($steps_completed[3])): ?>
                    <p class="step-success"><?php _e('Documents uploaded successfully!', 'gary-ai'); ?></p>
                    <p><?php _e('Your knowledge base is ready for use.', 'gary-ai'); ?></p>
                <?php elseif ($current_step >= 3): ?>
                    <p><?php _e('Upload PDFs, text files, or documents to populate your knowledge base.', 'gary-ai'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=gary-ai-documents'); ?>" class="button button-primary">
                        <?php _e('Upload Documents', 'gary-ai'); ?>
                    </a></p>
                <?php else: ?>
                    <p class="step-pending"><?php _e('Create a datastore first.', 'gary-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Step 4: Agent Configuration -->
        <div class="setup-step <?php echo isset($steps_completed[4]) ? 'completed' : ($current_step === 4 ? 'active' : 'pending'); ?>">
            <div class="step-header">
                <span class="step-number">4</span>
                <h2><?php _e('Configure Agent', 'gary-ai'); ?></h2>
                <?php if (isset($steps_completed[4])): ?>
                    <span class="step-status completed">✓</span>
                <?php endif; ?>
            </div>
            
            <div class="step-content">
                <?php if (isset($steps_completed[4])): ?>
                    <p class="step-success"><?php printf(__('You have %d agent(s) configured.', 'gary-ai'), count($agents)); ?></p>
                    <?php if (!empty($agent_id)): ?>
                        <p><strong><?php _e('Default Agent:', 'gary-ai'); ?></strong> 
                           <?php 
                           $default_agent_name = 'Unknown';
                           foreach ($agents as $agent) {
                               if ($agent['id'] === $agent_id) {
                                   $default_agent_name = $agent['name'] ?? $agent['id'];
                                   break;
                               }
                           }
                           echo esc_html($default_agent_name);
                           ?>
                        </p>
                    <?php endif; ?>
                <?php elseif ($current_step >= 4): ?>
                    <p><?php _e('Create and configure an AI agent that will interact with your users.', 'gary-ai'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=gary-ai-agents'); ?>" class="button button-primary">
                        <?php _e('Configure Agent', 'gary-ai'); ?>
                    </a></p>
                <?php else: ?>
                    <p class="step-pending"><?php _e('Upload documents first.', 'gary-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Step 5: Final Configuration -->
        <div class="setup-step <?php echo isset($steps_completed[5]) ? 'completed' : ($current_step === 5 ? 'active' : 'pending'); ?>">
            <div class="step-header">
                <span class="step-number">5</span>
                <h2><?php _e('Enable Chatbot', 'gary-ai'); ?></h2>
                <?php if (isset($steps_completed[5])): ?>
                    <span class="step-status completed">✓</span>
                <?php endif; ?>
            </div>
            
            <div class="step-content">
                <?php if (isset($steps_completed[5])): ?>
                    <p class="step-success"><?php _e('Chatbot is enabled and ready!', 'gary-ai'); ?></p>
                    <p><?php _e('Your Gary AI chatbot is now live on your website.', 'gary-ai'); ?></p>
                    <p><a href="<?php echo home_url(); ?>" class="button button-secondary" target="_blank">
                        <?php _e('View Your Website', 'gary-ai'); ?>
                    </a></p>
                <?php elseif ($current_step >= 5): ?>
                    <p><?php _e('Enable the chatbot to make it live on your website.', 'gary-ai'); ?></p>
                    
                    <?php if ($chatbot_enabled): ?>
                        <p class="step-success"><?php _e('Chatbot is enabled!', 'gary-ai'); ?></p>
                    <?php else: ?>
                        <form method="post" action="" style="display: inline-block;">
                            <?php wp_nonce_field('gary_ai_quick_action_enable_chatbot', 'enable_chatbot_nonce'); ?>
                            <input type="hidden" name="quick_action" value="enable_chatbot">
                            <button type="submit" class="button button-primary">
                                <?php _e('Enable Chatbot', 'gary-ai'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <p><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button button-secondary">
                        <?php _e('Advanced Settings', 'gary-ai'); ?>
                    </a></p>
                <?php else: ?>
                    <p class="step-pending"><?php _e('Configure an agent first.', 'gary-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <!-- Completion Message -->
    <?php if ($current_step >= 6): ?>
        <div class="gary-ai-completion">
            <div class="completion-icon">🎉</div>
            <h2><?php _e('Setup Complete!', 'gary-ai'); ?></h2>
            <p><?php _e('Congratulations! Your Gary AI chatbot is now fully configured and ready to help your website visitors.', 'gary-ai'); ?></p>
            
            <div class="completion-actions">
                <a href="<?php echo home_url(); ?>" class="button button-primary" target="_blank">
                    <?php _e('View Your Website', 'gary-ai'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button button-secondary">
                    <?php _e('Manage Settings', 'gary-ai'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=gary-ai-documents'); ?>" class="button button-secondary">
                    <?php _e('Add More Documents', 'gary-ai'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Quick Links -->
    <div class="gary-ai-quick-links">
        <h3><?php _e('Quick Links', 'gary-ai'); ?></h3>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>"><?php _e('Settings', 'gary-ai'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=gary-ai-datastores'); ?>"><?php _e('Manage Datastores', 'gary-ai'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=gary-ai-documents'); ?>"><?php _e('Upload Documents', 'gary-ai'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=gary-ai-agents'); ?>"><?php _e('Configure Agents', 'gary-ai'); ?></a></li>
        </ul>
    </div>
    
</div>

<style>
.gary-ai-progress-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.gary-ai-progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #00a0d2);
    transition: width 0.5s ease;
    border-radius: 10px;
}

.progress-text {
    margin: 0;
    text-align: center;
    font-weight: bold;
    color: #0073aa;
}

.gary-ai-setup-steps {
    margin-bottom: 30px;
}

.setup-step {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 15px;
    overflow: hidden;
}

.setup-step.active {
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
}

.setup-step.completed {
    border-color: #46b450;
}

.setup-step.pending {
    opacity: 0.6;
}

.step-header {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.setup-step.active .step-header {
    background: #e7f3ff;
}

.setup-step.completed .step-header {
    background: #ecf7ed;
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: #ddd;
    color: #666;
    border-radius: 50%;
    font-weight: bold;
    margin-right: 15px;
}

.setup-step.active .step-number {
    background: #0073aa;
    color: white;
}

.setup-step.completed .step-number {
    background: #46b450;
    color: white;
}

.step-header h2 {
    margin: 0;
    flex: 1;
    font-size: 18px;
}

.step-status {
    font-size: 20px;
    font-weight: bold;
}

.step-status.completed {
    color: #46b450;
}

.step-content {
    padding: 20px;
}

.step-success {
    color: #46b450;
    font-weight: bold;
    margin-bottom: 10px;
}

.step-pending {
    color: #999;
    font-style: italic;
}

.datastore-list {
    margin: 10px 0;
    padding-left: 20px;
}

.datastore-list li {
    margin-bottom: 5px;
}

.gary-ai-completion {
    background: #fff;
    border: 2px solid #46b450;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    margin-bottom: 30px;
}

.completion-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.gary-ai-completion h2 {
    color: #46b450;
    margin-bottom: 15px;
}

.completion-actions {
    margin-top: 20px;
}

.completion-actions .button {
    margin: 0 10px 10px 0;
}

.gary-ai-quick-links {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.gary-ai-quick-links h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.gary-ai-quick-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.gary-ai-quick-links li {
    margin: 0;
}

.gary-ai-quick-links a {
    display: block;
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #0073aa;
    transition: all 0.2s ease;
}

.gary-ai-quick-links a:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-refresh setup status every 10 seconds
    var refreshInterval = setInterval(function() {
        // Only refresh if we're not at the completion step
        if ($('.gary-ai-completion').length === 0) {
            location.reload();
        } else {
            clearInterval(refreshInterval);
        }
    }, 10000);
    
    // Smooth scroll to active step
    var activeStep = $('.setup-step.active');
    if (activeStep.length) {
        $('html, body').animate({
            scrollTop: activeStep.offset().top - 100
        }, 1000);
    }
    
    // Add loading states to buttons
    $('.button').on('click', function() {
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('<?php _e('Loading...', 'gary-ai'); ?>');
        
        // Re-enable after 5 seconds as fallback
        setTimeout(function() {
            $button.prop('disabled', false).text(originalText);
        }, 5000);
    });
});
</script>
