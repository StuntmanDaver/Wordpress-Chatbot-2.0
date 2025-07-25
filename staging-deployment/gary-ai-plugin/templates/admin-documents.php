<?php
/**
 * Admin Documents Page Template
 * Provides UI for uploading PDFs and text files to datastores
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
$selected_datastore = isset($_GET['datastore_id']) ? sanitize_text_field($_GET['datastore_id']) : '';

if ($_POST) {
    // Verify nonce for security
    if (!isset($_POST['gary_ai_nonce']) || !wp_verify_nonce($_POST['gary_ai_nonce'], 'gary_ai_document_action')) {
        wp_die(__('Security check failed. Please try again.', 'gary-ai'));
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_document':
                if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                    $datastore_id = sanitize_text_field($_POST['datastore_id']);
                    $file = $_FILES['document_file'];
                    
                    // Validate file type
                    $allowed_types = ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $file_type = $file['type'];
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_type, $allowed_types) && !in_array($file_extension, ['pdf', 'txt', 'doc', 'docx'])) {
                        $message = 'Invalid file type. Please upload PDF, TXT, DOC, or DOCX files only.';
                        $message_type = 'error';
                    } else {
                        try {
                            $result = $contextual_client->uploadDocument($datastore_id, $file['tmp_name'], $file['name']);
                            if ($result && isset($result['id'])) {
                                $message = 'Document "' . esc_html($file['name']) . '" uploaded successfully!';
                                $message_type = 'success';
                            } else {
                                $message = 'Failed to upload document. Please try again.';
                                $message_type = 'error';
                            }
                        } catch (Exception $e) {
                            $message = 'Error uploading document: ' . $e->getMessage();
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Please select a file to upload.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete_document':
                if (isset($_POST['document_id']) && isset($_POST['datastore_id'])) {
                    try {
                        $result = $contextual_client->deleteDocument($_POST['datastore_id'], $_POST['document_id']);
                        if ($result) {
                            $message = 'Document deleted successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to delete document.';
                            $message_type = 'error';
                        }
                    } catch (Exception $e) {
                        $message = 'Error deleting document: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Get list of datastores
$datastores = [];
$documents = [];
if (!empty($api_key)) {
    try {
        $datastores = $contextual_client->listDatastores();
        
        // Get documents for selected datastore
        if (!empty($selected_datastore)) {
            $documents = $contextual_client->listDocuments($selected_datastore);
        }
    } catch (Exception $e) {
        $message = 'Error loading data: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>

<div class="wrap">
    <h1><?php _e('Document Management', 'gary-ai'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($api_key)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Please configure your Contextual AI API key in the Settings page before managing documents.', 'gary-ai'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=gary-ai'); ?>" class="button"><?php _e('Go to Settings', 'gary-ai'); ?></a></p>
        </div>
    <?php elseif (empty($datastores)): ?>
        <div class="notice notice-warning">
            <p><?php _e('No datastores found. Please create a datastore first before uploading documents.', 'gary-ai'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=gary-ai-datastores'); ?>" class="button"><?php _e('Create Datastore', 'gary-ai'); ?></a></p>
        </div>
    <?php else: ?>
        
        <!-- Datastore Selection -->
        <div class="gary-ai-section">
            <h2><?php _e('Select Datastore', 'gary-ai'); ?></h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="gary-ai-documents">
                <select name="datastore_id" onchange="this.form.submit()">
                    <option value=""><?php _e('Select a datastore...', 'gary-ai'); ?></option>
                    <?php foreach ($datastores as $datastore): ?>
                        <option value="<?php echo esc_attr($datastore['id']); ?>" 
                                <?php selected($selected_datastore, $datastore['id']); ?>>
                            <?php echo esc_html($datastore['name'] ?? $datastore['id']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <?php if (!empty($selected_datastore)): ?>
            
            <!-- Upload Document Section -->
            <div class="gary-ai-section">
                <h2><?php _e('Upload Document', 'gary-ai'); ?></h2>
                <form method="post" action="" enctype="multipart/form-data" id="document-upload-form">
                    <?php wp_nonce_field('gary_ai_document_action', 'gary_ai_nonce'); ?>
                    <input type="hidden" name="action" value="upload_document">
                    <input type="hidden" name="datastore_id" value="<?php echo esc_attr($selected_datastore); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="document_file"><?php _e('Document File', 'gary-ai'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="document_file" name="document_file" 
                                       accept=".pdf,.txt,.doc,.docx,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                                       required>
                                <p class="description">
                                    <?php _e('Supported formats: PDF, TXT, DOC, DOCX. Maximum file size: 10MB.', 'gary-ai'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="upload-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <p class="progress-text"><?php _e('Uploading...', 'gary-ai'); ?></p>
                    </div>
                    
                    <?php submit_button(__('Upload Document', 'gary-ai'), 'primary', 'submit', false, ['id' => 'upload-submit']); ?>
                </form>
            </div>
            
            <!-- Existing Documents Section -->
            <div class="gary-ai-section">
                <h2><?php _e('Documents in Datastore', 'gary-ai'); ?></h2>
                
                <?php if (empty($documents)): ?>
                    <p><?php _e('No documents found in this datastore. Upload your first document above.', 'gary-ai'); ?></p>
                <?php else: ?>
                    <div class="gary-ai-documents-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php _e('Document Name', 'gary-ai'); ?></th>
                                    <th scope="col"><?php _e('Type', 'gary-ai'); ?></th>
                                    <th scope="col"><?php _e('Size', 'gary-ai'); ?></th>
                                    <th scope="col"><?php _e('Uploaded', 'gary-ai'); ?></th>
                                    <th scope="col"><?php _e('Status', 'gary-ai'); ?></th>
                                    <th scope="col"><?php _e('Actions', 'gary-ai'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($document['name'] ?? 'Unnamed Document'); ?></strong>
                                            <?php if (isset($document['id'])): ?>
                                                <br><small class="document-id"><?php echo esc_html($document['id']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $file_extension = isset($document['name']) ? strtoupper(pathinfo($document['name'], PATHINFO_EXTENSION)) : 'Unknown';
                                            echo esc_html($file_extension);
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($document['size'])) {
                                                echo esc_html(size_format($document['size']));
                                            } else {
                                                echo __('Unknown', 'gary-ai');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($document['created_at'])) {
                                                echo esc_html(date('Y-m-d H:i:s', strtotime($document['created_at'])));
                                            } else {
                                                echo __('Unknown', 'gary-ai');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $document['status'] ?? 'processed';
                                            $status_class = $status === 'processed' ? 'status-processed' : 'status-processing';
                                            ?>
                                            <span class="document-status <?php echo $status_class; ?>">
                                                <?php echo esc_html(ucfirst($status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="" style="display: inline-block;" 
                                                  onsubmit="return confirm('<?php _e('Are you sure you want to delete this document?', 'gary-ai'); ?>');">
                                                <?php wp_nonce_field('gary_ai_document_action', 'gary_ai_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="datastore_id" value="<?php echo esc_attr($selected_datastore); ?>">
                                                <input type="hidden" name="document_id" value="<?php echo esc_attr($document['id']); ?>">
                                                <button type="submit" class="button button-link-delete">
                                                    <?php _e('Delete', 'gary-ai'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

.gary-ai-section select {
    min-width: 300px;
    padding: 8px;
}

.upload-progress {
    margin: 20px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background-color: #0073aa;
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 10px;
}

.progress-text {
    margin: 0;
    font-style: italic;
    color: #666;
}

.gary-ai-documents-table {
    margin-top: 20px;
}

.document-id {
    font-family: monospace;
    color: #666;
    background: #f0f0f0;
    padding: 2px 4px;
    border-radius: 3px;
}

.document-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-processed {
    background-color: #d4edda;
    color: #155724;
}

.status-processing {
    background-color: #fff3cd;
    color: #856404;
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

#document_file {
    width: 100%;
    max-width: 400px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle file upload with progress
    $('#document-upload-form').on('submit', function(e) {
        var fileInput = $('#document_file')[0];
        var file = fileInput.files[0];
        
        if (file) {
            // Check file size (10MB limit)
            if (file.size > 10 * 1024 * 1024) {
                alert('<?php _e('File size must be less than 10MB.', 'gary-ai'); ?>');
                e.preventDefault();
                return false;
            }
            
            // Show progress bar
            $('.upload-progress').show();
            $('#upload-submit').prop('disabled', true).text('<?php _e('Uploading...', 'gary-ai'); ?>');
            
            // Simulate progress (since we can't track real progress with standard form submission)
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                $('.progress-fill').css('width', progress + '%');
            }, 200);
            
            // Clean up on form submission
            setTimeout(function() {
                clearInterval(progressInterval);
                $('.progress-fill').css('width', '100%');
            }, 1000);
        }
    });
    
    // File input change handler
    $('#document_file').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileName = file.name;
            var fileSize = file.size;
            var fileType = file.type;
            
            // Display file info
            var fileInfo = '<p><strong>Selected file:</strong> ' + fileName + ' (' + (fileSize / 1024 / 1024).toFixed(2) + ' MB)</p>';
            
            if ($('.file-info').length) {
                $('.file-info').html(fileInfo);
            } else {
                $(this).after('<div class="file-info">' + fileInfo + '</div>');
            }
        }
    });
    
    // Auto-refresh documents list every 30 seconds
    setInterval(function() {
        if ($('.gary-ai-documents-table').length > 0) {
            // Only refresh if we're not uploading
            if (!$('#upload-submit').prop('disabled')) {
                location.reload();
            }
        }
    }, 30000);
});
</script>
