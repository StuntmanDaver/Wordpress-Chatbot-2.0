/**
 * Gary AI Admin JavaScript
 * 
 * ⚠️  IMPORTANT: When making changes to this file, update CHANGELOG.md
 * Document all modifications under the [Unreleased] section following semantic versioning
 */

(function($) {
    'use strict';

    // Verify jQuery is available
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        console.error('Gary AI Admin: jQuery is not loaded. Admin functionality cannot initialize.');
        return;
    }

    $(document).ready(function() {
        try {
            // Settings validation
            function validateSettings() {
                let isValid = true;
                const errors = [];
                
                // Validate API Key
                const apiKey = $('#gary_ai_contextual_api_key').val().trim();
                if (!apiKey) {
                    errors.push(garyAIAdmin.strings.api_key_required || 'API Key is required');
                    isValid = false;
                } else if (!apiKey.startsWith('key-')) {
                    errors.push(garyAIAdmin.strings.api_key_format || 'API Key should start with "key-"');
                    isValid = false;
                } else if (apiKey.length < 20) {
                    errors.push(garyAIAdmin.strings.api_key_short || 'API Key appears to be too short');
                    isValid = false;
                }
                
                // Validate Agent ID
                const agentId = $('#gary_ai_agent_id').val().trim();
                if (!agentId) {
                    errors.push('Agent ID is required');
                    isValid = false;
                } else if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(agentId)) {
                    errors.push('Agent ID should be a valid UUID format');
                    isValid = false;
                }
                
                // Validate Datastore ID
                const datastoreId = $('#gary_ai_datastore_id').val().trim();
                if (datastoreId && !/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(datastoreId)) {
                    errors.push('Datastore ID should be a valid UUID format');
                    isValid = false;
                }
                
                return { isValid: isValid, errors: errors };
            }
            
            // Real-time validation
            $('#gary_ai_contextual_api_key, #gary_ai_agent_id, #gary_ai_datastore_id').on('blur', function() {
                const validation = validateSettings();
                const $result = $('.gary-ai-validation-result');
                
                if (!validation.isValid) {
                    if (!$result.length) {
                        $(this).closest('tr').after('<tr class="gary-ai-validation-result"><td colspan="2"><div class="notice notice-warning"><p>' + validation.errors.join('<br>') + '</p></div></td></tr>');
                    } else {
                        $result.find('p').html(validation.errors.join('<br>'));
                    }
                } else {
                    $result.remove();
                }
            });
            
            // Test connection button
                        $('#gary-ai-test-connection').on('click', function(e) {
                e.preventDefault();
                
                // Validate settings before testing
                const validation = validateSettings();
                if (!validation.isValid) {
                    $('.gary-ai-test-result').removeClass('success').addClass('error')
                        .html('<strong>Validation Errors:</strong><br>' + validation.errors.join('<br>')).fadeIn();
                    return;
                }
                
                const $button = $(this);
                const $result = $('.gary-ai-test-result');
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Testing...');
            $result.hide();
            
            // Get current values
            const apiKey = $('#gary_ai_contextual_api_key').val();
            const agentId = $('#gary_ai_agent_id').val();
            const datastoreId = $('#gary_ai_datastore_id').val();
            
            // Test connection
            $.ajax({
                url: garyAIAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'gary_ai_test_connection',
                    nonce: garyAIAdmin.nonce,
                    api_key: apiKey,
                    agent_id: agentId,
                    datastore_id: datastoreId
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success')
                            .text(garyAIAdmin.strings.test_success).fadeIn();
                    } else {
                        $result.removeClass('success').addClass('error')
                            .text(response.data.message || garyAIAdmin.strings.test_error).fadeIn();
                    }
                },
                error: function() {
                    $result.removeClass('success').addClass('error')
                        .text(garyAIAdmin.strings.test_error).fadeIn();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        });
        
            // Add test result div if not exists
            if (!$('.gary-ai-test-result').length) {
                $('#gary-ai-test-connection').after('<div class="gary-ai-test-result"></div>');
            }
        } catch (error) {
            console.error('Gary AI Admin: Error during initialization:', error);
        }
    });

    // Enhanced form submission handling with validation
    function handleFormSubmission() {
        $('form').on('submit', function(e) {
            const $form = $(this);
            
            // Only validate Gary AI forms
            if (!$form.find('[name^="gary_ai_"]').length) {
                return true;
            }
            
            // Clear previous validation messages
            $('.gary-ai-validation-error').remove();
            
            // Run validation
            if (!validateSettings()) {
                e.preventDefault();
                
                // Show validation errors
                const errorDiv = $('<div class="notice notice-error gary-ai-validation-error"><p>Please fix the following errors before saving:</p><ul></ul></div>');
                $('.gary-ai-validation-error').remove();
                $form.before(errorDiv);
                
                // Scroll to top to show errors
                $('html, body').animate({scrollTop: 0}, 300);
                
                return false;
            }
            
            return true;
        });
    }
    
    // Initialize enhanced form validation
    handleFormSubmission();

})(jQuery); 