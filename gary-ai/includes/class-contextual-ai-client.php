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
     * Datastore ID for knowledge base
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
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('gary_ai_contextual_api_key', '');
        $this->agent_id = get_option('gary_ai_agent_id', '');
        $this->datastore_id = get_option('gary_ai_datastore_id', '');
    }
    
    /**
     * Send a query to the AI with retry logic
     * 
     * @param string $message User message
     * @param string $session_id Session identifier
     * @param bool $stream Whether to stream response
     * @return array|WP_Error Response or error
     */
    public function query($message, $session_id, $stream = false) {
        if (empty($this->api_key) || empty($this->agent_id) || empty($this->datastore_id)) {
            return new WP_Error('missing_config', 'API configuration is incomplete');
        }
        
        $endpoint = $this->api_base_url . '/chat/completions';
        
        $body = [
            'agent_id' => $this->agent_id,
            'datastore_id' => $this->datastore_id,
            'message' => sanitize_text_field($message),
            'session_id' => sanitize_text_field($session_id),
            'stream' => $stream
        ];
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Gary-AI-WordPress-Plugin/' . GARY_AI_VERSION
            ],
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'sslverify' => true
        ];
        
        // Use enhanced HTTP request with retry logic
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('Gary AI: API returned code ' . $response_code . ' - ' . $response_body);
            return new WP_Error('api_error', 'API request failed with code: ' . $response_code);
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Gary AI: Invalid JSON response - ' . json_last_error_msg());
            return new WP_Error('invalid_response', 'Invalid JSON response from API');
        }
        
        // Return success response for test
        return [
            'success' => true,
            'message' => $data['message'] ?? 'Test connection successful',
            'citations' => $data['citations'] ?? []
        ];
    }
    
    /**
     * Send a message to the AI (alias for query)
     * 
     * @param string $message User message
     * @param array $options Options including session_id, user_id
     * @return array|WP_Error Response or error
     */
    public function sendMessage($message, $options = []) {
        $session_id = $options['session_id'] ?? uniqid('gary_ai_session_');
        return $this->query($message, $session_id);
    }
    
    /**
     * Test the connection to the API with retry logic
     * 
     * @return array|WP_Error Test result
     */
    public function testConnection() {
        if (empty($this->api_key) || empty($this->agent_id) || empty($this->datastore_id)) {
            return new WP_Error('missing_config', 'API configuration is incomplete. Please check your API key, Agent ID, and Datastore ID.');
        }
        
        // Test with a simple query
        return $this->query('Hello, this is a test message', 'test_session_' . time());
    }
    
    /**
     * Validate API credentials
     * 
     * @param string $api_key API key to validate
     * @param string $agent_id Agent ID to validate
     * @param string $datastore_id Datastore ID to validate
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function validateCredentials($api_key, $agent_id, $datastore_id) {
        // Store original credentials
        $original_api_key = $this->api_key;
        $original_agent_id = $this->agent_id;
        $original_datastore_id = $this->datastore_id;
        
        // Set new credentials for testing
        $this->api_key = $api_key;
        $this->agent_id = $agent_id;
        $this->datastore_id = $datastore_id;
        
        // Test connection
        $result = $this->testConnection();
        
        // Restore original credentials
        $this->api_key = $original_api_key;
        $this->agent_id = $original_agent_id;
        $this->datastore_id = $original_datastore_id;
        
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
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'API key is required');
        }
        
        $endpoint = $this->api_base_url . '/usage/stats';
        
        $args = [
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15,
            'sslverify' => true
        ];
        
        // Use enhanced HTTP request with retry logic
        $response = $this->makeRequestWithRetry($endpoint, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'Failed to fetch usage stats');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data ?? [];
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
     * @param string $datastore_id Datastore ID
     */
    public function setCredentials($api_key, $agent_id, $datastore_id) {
        $this->api_key = $api_key;
        $this->agent_id = $agent_id;
        $this->datastore_id = $datastore_id;
    }
} 