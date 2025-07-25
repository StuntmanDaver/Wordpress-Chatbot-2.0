<?php
/**
 * Contextual AI Client
 * 
 * Handles communication with the Contextual AI API for chat functionality
 * with enhanced network resilience and retry logic
 * 
 * @package GaryAI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ContextualAIClient {
    
    /**
     * API key for authentication
     * @var string
     */
    private $api_key;
    
    /**
     * Agent ID for the AI agent
     * @var string
     */
    private $agent_id;
    
    /**
     * Datastore ID for the knowledge base
     * @var string
     */
    private $datastore_id;
    
    /**
     * API base URL
     * @var string
     */
    private $api_base_url = 'https://api.contextual.ai/v1';
    
    /**
     * Maximum number of retry attempts
     * @var int
     */
    private $max_retries = 3;
    
    /**
     * Base delay for exponential backoff (in seconds)
     * @var float
     */
    private $base_delay = 1.0;
    
    /**
     * Whether multi-turn is enabled for this agent
     * @var bool
     */
    private $multi_turn_enabled = true;
    
    /**
     * Default conversation ID for multi-turn conversations
     * @var string|null
     */
    private $default_conversation_id = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('gary_ai_contextual_api_key', '');
        $this->agent_id = get_option('gary_ai_agent_id', '');
        $this->datastore_id = get_option('gary_ai_datastore_id', '');
    }
    
    /**
     * Validate API key format for Contextual AI
     * 
     * @param string $api_key API key to validate
     * @return bool True if valid format
     */
    public function validateApiKey($api_key = null) {
        $key = $api_key ?? $this->api_key;
        
        if (empty($key)) {
            return false;
        }
        
        // Contextual AI API keys start with 'key-' followed by base64-like characters
        if (!preg_match('/^key-[A-Za-z0-9_-]{20,}$/', $key)) {
            error_log('Gary AI: Invalid API key format. Contextual AI keys should start with "key-"');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate agent ID format
     * 
     * @param string $agent_id Agent ID to validate
     * @return bool True if valid format
     */
    public function validateAgentId($agent_id = null) {
        $id = $agent_id ?? $this->agent_id;
        
        if (empty($id)) {
            return false;
        }
        
        // Agent IDs should be valid UUIDs
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            error_log('Gary AI: Invalid agent ID format. Should be a valid UUID');
            return false;
        }
        
        return true;
    }
    
    /**
     * Send a query to the AI with advanced features support
     * 
     * @param string $message User message
     * @param array $options Query options including session_id, conversation_id, structured_output, extra_body, stream
     * @return array|WP_Error Response or error
     */
    public function query($message, $options = []) {
        // Enhanced validation using proper format checking
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key. Contextual AI keys should start with "key-".');
        }
        
        if (!$this->validateAgentId()) {
            return new WP_Error('invalid_agent_id', 'Invalid or missing Agent ID. Should be a valid UUID.');
        }

        $endpoint = $this->api_base_url . '/chat/completions';

        // Build request body according to Contextual AI v1 API specification
        $body = [
            'agent_id' => $this->agent_id,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => sanitize_text_field($message)
                ]
            ]
        ];

        // Add datastore_id if available
        if (!empty($this->datastore_id)) {
            $body['datastore_id'] = $this->datastore_id;
        }

        // Add conversation_id for multi-turn conversations
        if (!empty($options['conversation_id'])) {
            $body['conversation_id'] = sanitize_text_field($options['conversation_id']);
        } elseif ($this->multi_turn_enabled && !empty($this->default_conversation_id)) {
            $body['conversation_id'] = $this->default_conversation_id;
        }

        // Add session_id if provided (backward compatibility)
        if (!empty($options['session_id'])) {
            $body['session_id'] = sanitize_text_field($options['session_id']);
        }

        // Add streaming support
        if (!empty($options['stream'])) {
            $body['stream'] = (bool) $options['stream'];
        }

        // Add structured output support (Beta feature)
        if (!empty($options['structured_output'])) {
            $body['structured_output'] = $options['structured_output'];
        }

        // Merge extra_body parameters for advanced configurations
        if (!empty($options['extra_body']) && is_array($options['extra_body'])) {
            $body = array_merge($body, $options['extra_body']);
        }

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => wp_json_encode($body),
            'timeout'   => 30,
            'sslverify' => true,
        ];

        $response = $this->makeRequestWithRetry($endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Enhanced error handling for different HTTP status codes
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = 'API request failed';
            
            if ($error_data && isset($error_data['error'])) {
                $error_message = $error_data['error']['message'] ?? $error_data['error'] ?? $error_message;
            }
            
            error_log('Gary AI: API returned code ' . $response_code . ' - ' . $response_body);
            
            // Handle specific error codes
            switch ($response_code) {
                case 400:
                    return new WP_Error('bad_request', 'Bad request: ' . $error_message, ['response_code' => $response_code]);
                case 401:
                    return new WP_Error('unauthorized', 'Unauthorized: Invalid API key', ['response_code' => $response_code]);
                case 403:
                    return new WP_Error('forbidden', 'Forbidden: Access denied', ['response_code' => $response_code]);
                case 404:
                    return new WP_Error('not_found', 'Not found: Invalid endpoint or agent', ['response_code' => $response_code]);
                case 429:
                    return new WP_Error('rate_limited', 'Rate limited: Too many requests', ['response_code' => $response_code]);
                case 500:
                    return new WP_Error('server_error', 'Server error: ' . $error_message, ['response_code' => $response_code]);
                default:
                    return new WP_Error('api_error', 'API error: ' . $error_message, ['response_code' => $response_code]);
            }
        }

        // Parse JSON response with enhanced error checking
        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Gary AI: Invalid JSON response - ' . json_last_error_msg() . ' - Body: ' . $response_body);
            return new WP_Error('invalid_response', 'Invalid JSON response from API: ' . json_last_error_msg());
        }

        // Validate response structure according to Contextual AI v1 API
        if (!is_array($data)) {
            error_log('Gary AI: Response is not an array - ' . $response_body);
            return new WP_Error('invalid_response', 'Invalid response format from API');
        }

        // Extract response content from choices array (OpenAI-compatible format)
        $response_text = '';
        if (isset($data['choices']) && is_array($data['choices']) && !empty($data['choices'])) {
            $response_text = $data['choices'][0]['message']['content'] ?? '';
        } elseif (isset($data['response'])) {
            // Fallback to direct response field
            $response_text = $data['response'];
        }

        if (empty($response_text)) {
            error_log('Gary AI: No response content found in API response - ' . $response_body);
            return new WP_Error('empty_response', 'No response content received from API');
        }

        return [
            'success'         => true,
            'response'        => $response_text,
            'message_id'      => $data['id'] ?? $data['message_id'] ?? null,
            'session_id'      => $data['session_id'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
            'usage'           => $data['usage'] ?? [],
            'retrieval_info'  => $data['retrieval_info'] ?? $data['citations'] ?? [],
            'structured_data' => $data['structured_data'] ?? null,
            'model'           => $data['model'] ?? null,
            'created'         => $data['created'] ?? null,
        ];
    }
    
    /**
     * Send a message to the AI (backward compatibility method)
     * 
     * @param string $message User message
     * @param array $options Options including session_id, conversation_id, etc.
     * @return array|WP_Error Response or error
     */
    public function sendMessage($message, $options = []) {
        // Ensure session_id for backward compatibility
        if (empty($options['session_id'])) {
            $options['session_id'] = uniqid('gary_ai_session_');
        }
        return $this->query($message, $options);
    }
    
    /**
     * Test the connection to the API with retry logic
     * 
     * @return array|WP_Error Test result
     */
    public function testConnection() {
        if (empty($this->api_key) || empty($this->agent_id)) {
            return new WP_Error('missing_config', 'API configuration is incomplete. Please check your API key and Agent ID.');
        }
        
        // Test with a simple query using new options format
        return $this->query('Hello, this is a test message', [
            'session_id' => 'test_session_' . time()
        ]);
    }
    
    /**
     * Validate API credentials
     * 
     * @param string $api_key API key to validate
     * @param string $agent_id Agent ID to validate
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function validateCredentials($api_key, $agent_id) {
        // Store original credentials
        $original_api_key = $this->api_key;
        $original_agent_id = $this->agent_id;
        
        // Set new credentials for testing
        $this->api_key = $api_key;
        $this->agent_id = $agent_id;
        
        // Test connection
        $result = $this->testConnection();
        
        // Restore original credentials
        $this->api_key = $original_api_key;
        $this->agent_id = $original_agent_id;
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Get usage statistics with retry logic
     * 
     * @return array|WP_Error Usage data or error
     */
    public function getUsageStats() {
        if (empty($this->api_key) || empty($this->agent_id)) {
            return new WP_Error('missing_config', 'API key and Agent ID are required.');
        }

        $endpoint = $this->api_base_url . '/agents/' . $this->agent_id . '/query/metrics';

        $args = [
            'method'  => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 15,
            'sslverify' => true,
        ];

        $response = $this->makeRequestWithRetry($endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API request failed with code: ' . $response_code, ['response_body' => $response_body]);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_response', 'Invalid JSON response from API');
        }

        return $data; // The new endpoint returns a rich metrics object
    }
    
    /**
     * Make HTTP request with retry logic and exponential backoff
     * 
     * This method implements a robust retry mechanism for API calls with:
     * - Exponential backoff to avoid overwhelming the API
     * - Jitter to prevent thundering herd problems
     * - Intelligent error classification for retry decisions
     * - Comprehensive logging for debugging
     * 
     * @param string $endpoint API endpoint URL
     * @param array $args Request arguments (headers, body, method, etc.)
     * @return array|WP_Error Response or error
     */
    private function makeRequestWithRetry($endpoint, $args) {
        $attempt = 0;
        
        // Retry loop: attempt initial request + configured number of retries
        while ($attempt <= $this->max_retries) {
            // Execute the HTTP request using WordPress HTTP API
            $response = wp_remote_request($endpoint, $args);
            
            // Handle successful responses and non-retryable errors
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                // HTTP 2xx status codes indicate success
                if ($response_code >= 200 && $response_code < 300) {
                    return $response;
                }
                
                // For HTTP errors, check if they warrant a retry
                // (e.g., 500, 502, 503, 504 are retryable; 400, 401, 404 are not)
                if (!$this->isRetryableError($response_code)) {
                    return $response;
                }
            } else {
                // Handle WordPress HTTP errors (timeouts, connection failures, etc.)
                if (!$this->isRetryableWpError($response)) {
                    error_log('Gary AI: Non-retryable error - ' . $response->get_error_message());
                    return $response;
                }
            }
            
            $attempt++;
            
            // If we've exhausted all retry attempts, return the final result
            if ($attempt > $this->max_retries) {
                if (is_wp_error($response)) {
                    error_log('Gary AI: Max retries exceeded - ' . $response->get_error_message());
                } else {
                    $response_code = wp_remote_retrieve_response_code($response);
                    error_log('Gary AI: Max retries exceeded - HTTP ' . $response_code);
                }
                return $response;
            }
            
            // Calculate exponential backoff delay: base_delay * 2^(attempt-1)
            // This creates delays like: 1s, 2s, 4s for attempts 1, 2, 3
            $delay = $this->base_delay * pow(2, $attempt - 1);
            
            // Add random jitter (up to 10% of delay) to prevent thundering herd
            // This prevents multiple clients from retrying at exactly the same time
            $jitter = $delay * 0.1 * (mt_rand() / mt_getrandmax());
            $delay = $delay + $jitter;
            
            error_log(sprintf(
                'Gary AI: Retry attempt %d/%d after %.2f seconds',
                $attempt,
                $this->max_retries,
                $delay
            ));
            
            // Sleep for the calculated delay
            sleep((int) $delay);
            if ($delay - floor($delay) > 0) {
                usleep((int) (($delay - floor($delay)) * 1000000));
            }
        }
        
        // This should never be reached, but just in case
        return new WP_Error('max_retries_exceeded', 'Maximum retry attempts exceeded');
    }
    
    /**
     * Check if HTTP response code indicates a retryable error
     * 
     * @param int $response_code HTTP response code
     * @return bool True if error is retryable
     */
    private function isRetryableError($response_code) {
        // Retryable HTTP status codes
        $retryable_codes = [
            429, // Too Many Requests
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
            520, // Unknown Error (Cloudflare)
            521, // Web Server Is Down (Cloudflare)
            522, // Connection Timed Out (Cloudflare)
            523, // Origin Is Unreachable (Cloudflare)
            524, // A Timeout Occurred (Cloudflare)
        ];
        
        return in_array($response_code, $retryable_codes, true);
    }
    
    /**
     * Check if WP_Error indicates a retryable condition
     * 
     * @param WP_Error $error WordPress error object
     * @return bool True if error is retryable
     */
    private function isRetryableWpError($error) {
        if (!is_wp_error($error)) {
            return false;
        }
        
        $error_code = $error->get_error_code();
        $error_message = strtolower($error->get_error_message());
        
        // Retryable error codes/messages
        $retryable_errors = [
            'http_request_timeout',
            'http_request_failed',
            'connect_timeout',
            'resolve_timeout',
        ];
        
        // Check error code
        if (in_array($error_code, $retryable_errors, true)) {
            return true;
        }
        
        // Check error message for timeout/connection issues
        $retryable_messages = [
            'timeout',
            'connection',
            'network',
            'temporary',
            'unavailable',
            'reset',
        ];
        
        foreach ($retryable_messages as $retryable_message) {
            if (strpos($error_message, $retryable_message) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Set retry configuration
     * 
     * @param int $max_retries Maximum number of retries (default: 3)
     * @param float $base_delay Base delay in seconds (default: 1.0)
     */
    public function setRetryConfig($max_retries = 3, $base_delay = 1.0) {
        $this->max_retries = max(0, (int) $max_retries);
        $this->base_delay = max(0.1, (float) $base_delay);
    }
    
    /**
     * Set API credentials (for testing purposes)
     * 
     * @param string $api_key API key
     * @param string $agent_id Agent ID
     */
    public function setCredentials($api_key, $agent_id) {
        $this->api_key = $api_key;
        $this->agent_id = $agent_id;
    }
    
    /**
     * Enable or disable multi-turn behavior for the agent
     * 
     * @param bool $enable Whether to enable multi-turn
     * @return array|WP_Error Response or error
     */
    public function setMultiTurnBehavior($enable = true) {
        if (empty($this->api_key) || empty($this->agent_id)) {
            return new WP_Error('missing_config', 'API key and Agent ID are required.');
        }

        $endpoint = $this->api_base_url . '/agents/' . $this->agent_id;

        $body = [
            'agent_config' => [
                'global_config' => [
                    'enable_multi_turn' => (bool) $enable
                ]
            ]
        ];

        $args = [
            'method'  => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => wp_json_encode($body),
            'timeout'   => 30,
            'sslverify' => true,
        ];

        $response = $this->makeRequestWithRetry($endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            error_log('Gary AI: Agent update failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('agent_update_failed', 'Failed to update agent configuration', ['response_body' => $response_body]);
        }

        $this->multi_turn_enabled = $enable;
        return ['success' => true, 'multi_turn_enabled' => $enable];
    }
    
    /**
     * Set default conversation ID for multi-turn conversations
     * 
     * @param string|null $conversation_id Conversation ID or null to generate new
     * @return string The conversation ID being used
     */
    public function setConversationId($conversation_id = null) {
        if (empty($conversation_id)) {
            $conversation_id = 'gary_ai_conv_' . uniqid() . '_' . time();
        }
        
        $this->default_conversation_id = sanitize_text_field($conversation_id);
        return $this->default_conversation_id;
    }
    
    /**
     * Get current conversation ID
     * 
     * @return string|null Current conversation ID
     */
    public function getConversationId() {
        return $this->default_conversation_id;
    }
    
    /**
     * Create a new conversation and set it as default
     * 
     * @return string New conversation ID
     */
    public function startNewConversation() {
        return $this->setConversationId();
    }
    
    /**
     * Query with structured output support
     * 
     * @param string $message User message
     * @param array $schema JSON schema for structured output
     * @param array $options Additional query options
     * @return array|WP_Error Response or error
     */
    public function queryWithStructuredOutput($message, $schema, $options = []) {
        $options['structured_output'] = $schema;
        return $this->query($message, $options);
    }
    
    /**
     * Create a simple JSON schema for structured outputs
     * 
     * @param array $properties Schema properties definition
     * @param array $required Required field names
     * @return array JSON schema
     */
    public function createJsonSchema($properties, $required = []) {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required
        ];
    }
    
    /**
     * Query with conversation context (multi-turn)
     * 
     * @param string $message User message
     * @param string|null $conversation_id Conversation ID (uses default if null)
     * @param array $options Additional options
     * @return array|WP_Error Response or error
     */
    public function queryInConversation($message, $conversation_id = null, $options = []) {
        if (!empty($conversation_id)) {
            $options['conversation_id'] = $conversation_id;
        }
        
        return $this->query($message, $options);
    }
    
    /**
     * Get multi-turn status
     * 
     * @return bool Whether multi-turn is enabled
     */
    public function isMultiTurnEnabled() {
        return $this->multi_turn_enabled;
    }
    
    /**
     * Create a new datastore
     * 
     * @param string $name Datastore name
     * @param string $description Optional description
     * @return array|WP_Error Response or error
     */
    public function createDatastore($name, $description = '') {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/datastores';
        
        $body = [
            'name' => sanitize_text_field($name)
        ];
        
        if (!empty($description)) {
            $body['description'] = sanitize_textarea_field($description);
        }
        
        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => wp_json_encode($body),
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 201) {
            error_log('Gary AI: Datastore creation failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('datastore_creation_failed', 'Failed to create datastore', ['response_body' => $response_body]);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * List all datastores
     * 
     * @return array|WP_Error List of datastores or error
     */
    public function listDatastores() {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/datastores';
        
        $args = [
            'method'  => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Gary AI: Datastore listing failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('datastore_listing_failed', 'Failed to list datastores', ['response_body' => $response_body]);
        }
        
        $data = json_decode($response_body, true);
        return $data['datastores'] ?? [];
    }
    
    /**
     * Delete a datastore
     * 
     * @param string $datastore_id Datastore ID to delete
     * @return bool|WP_Error True on success or error
     */
    public function deleteDatastore($datastore_id) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/datastores/' . urlencode($datastore_id);
        
        $args = [
            'method'  => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 204 && $response_code !== 200) {
            $response_body = wp_remote_retrieve_body($response);
            error_log('Gary AI: Datastore deletion failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('datastore_deletion_failed', 'Failed to delete datastore', ['response_body' => $response_body]);
        }
        
        return true;
    }
    
    /**
     * Upload a document to a datastore
     * 
     * @param string $datastore_id Datastore ID
     * @param string $file_path Path to the file
     * @param string $file_name Original file name
     * @return array|WP_Error Response or error
     */
    public function uploadDocument($datastore_id, $file_path, $file_name) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found: ' . $file_path);
        }
        
        $endpoint = $this->api_base_url . '/datastores/' . urlencode($datastore_id) . '/documents';
        
        // Create multipart form data with proper security and encoding
        $boundary = 'gary_ai_upload_' . wp_generate_uuid4();
        $file_content = file_get_contents($file_path);
        $mime_type = mime_content_type($file_path) ?: 'application/octet-stream';
        
        // Sanitize filename to prevent header injection
        $safe_filename = sanitize_file_name(basename($file_name));
        $safe_filename = preg_replace('/[^\w\-_\.]/', '_', $safe_filename);
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . $safe_filename . "\"\r\n";
        $body .= "Content-Type: {$mime_type}\r\n";
        $body .= "Content-Length: " . strlen($file_content) . "\r\n\r\n";
        $body .= $file_content;
        $body .= "\r\n--{$boundary}--\r\n";
        
        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => $body,
            'timeout'   => 60, // Longer timeout for file uploads
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 201) {
            error_log('Gary AI: Document upload failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('document_upload_failed', 'Failed to upload document', ['response_body' => $response_body]);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * List documents in a datastore
     * 
     * @param string $datastore_id Datastore ID
     * @return array|WP_Error List of documents or error
     */
    public function listDocuments($datastore_id) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/datastores/' . urlencode($datastore_id) . '/documents';
        
        $args = [
            'method'  => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Gary AI: Document listing failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('document_listing_failed', 'Failed to list documents', ['response_body' => $response_body]);
        }
        
        $data = json_decode($response_body, true);
        return $data['documents'] ?? [];
    }
    
    /**
     * Delete a document from a datastore
     * 
     * @param string $datastore_id Datastore ID
     * @param string $document_id Document ID to delete
     * @return bool|WP_Error True on success or error
     */
    public function deleteDocument($datastore_id, $document_id) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/datastores/' . urlencode($datastore_id) . '/documents/' . urlencode($document_id);
        
        $args = [
            'method'  => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 204 && $response_code !== 200) {
            $response_body = wp_remote_retrieve_body($response);
            error_log('Gary AI: Document deletion failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('document_deletion_failed', 'Failed to delete document', ['response_body' => $response_body]);
        }
        
        return true;
    }
    
    /**
     * Create a new agent
     * 
     * @param array $agent_data Agent configuration data
     * @return array|WP_Error Response or error
     */
    public function createAgent($agent_data) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/agents';
        
        $body = [
            'name' => sanitize_text_field($agent_data['name']),
            'datastore_id' => sanitize_text_field($agent_data['datastore_id']),
            'system_prompt' => sanitize_textarea_field($agent_data['system_prompt']),
            'temperature' => floatval($agent_data['temperature']),
            'max_tokens' => intval($agent_data['max_tokens'])
        ];
        
        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => wp_json_encode($body),
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 201) {
            error_log('Gary AI: Agent creation failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('agent_creation_failed', 'Failed to create agent', ['response_body' => $response_body]);
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * List all agents
     * 
     * @return array|WP_Error List of agents or error
     */
    public function listAgents() {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/agents';
        
        $args = [
            'method'  => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Gary AI: Agent listing failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('agent_listing_failed', 'Failed to list agents', ['response_body' => $response_body]);
        }
        
        $data = json_decode($response_body, true);
        return $data['agents'] ?? [];
    }
    
    /**
     * Update an existing agent
     * 
     * @param string $agent_id Agent ID to update
     * @param array $agent_data Updated agent configuration data
     * @return bool|WP_Error True on success or error
     */
    public function updateAgent($agent_id, $agent_data) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/agents/' . urlencode($agent_id);
        
        $body = [
            'name' => sanitize_text_field($agent_data['name']),
            'datastore_id' => sanitize_text_field($agent_data['datastore_id']),
            'system_prompt' => sanitize_textarea_field($agent_data['system_prompt']),
            'temperature' => floatval($agent_data['temperature']),
            'max_tokens' => intval($agent_data['max_tokens'])
        ];
        
        $args = [
            'method'  => 'PUT',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'body'      => wp_json_encode($body),
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $response_body = wp_remote_retrieve_body($response);
            error_log('Gary AI: Agent update failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('agent_update_failed', 'Failed to update agent', ['response_body' => $response_body]);
        }
        
        return true;
    }
    
    /**
     * Delete an agent
     * 
     * @param string $agent_id Agent ID to delete
     * @return bool|WP_Error True on success or error
     */
    public function deleteAgent($agent_id) {
        if (!$this->validateApiKey()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing API key.');
        }
        
        $endpoint = $this->api_base_url . '/agents/' . urlencode($agent_id);
        
        $args = [
            'method'  => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent'    => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION,
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ];
        
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 204 && $response_code !== 200) {
            $response_body = wp_remote_retrieve_body($response);
            error_log('Gary AI: Agent deletion failed with code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('agent_deletion_failed', 'Failed to delete agent', ['response_body' => $response_body]);
        }
        
        return true;
    }
} 