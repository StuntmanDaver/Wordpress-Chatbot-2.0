<?php
/**
 * Contextual AI API Client
 * 
 * Handles communication with Contextual AI API for chat responses
 * 
 * @package GaryAIChatbot
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ContextualAIClient {
    
    /**
     * API base URL
     */
    private const API_BASE_URL = 'https://api.contextual.ai';
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Datastore ID
     */
    private $datastore_id;
    
    /**
     * Agent ID
     */
    private $agent_id;
    
    /**
     * Agent name
     */
    private $agent_name;
    
    /**
     * HTTP timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * Simple in-memory cache for API responses
     */
    private static $response_cache = array();
    
    /**
     * Cache duration in seconds (10 minutes)
     */
    private $cache_duration = 600;
    
    /**
     * Expected API version
     */
    private $expected_api_version = 'v1';
    
    /**
     * Proxy configuration
     */
    private $proxy_host = null;
    private $proxy_port = null;
    private $proxy_username = null;
    private $proxy_password = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->loadConfiguration();
        $this->loadProxyConfiguration();
    }
    
    /**
     * Load configuration from environment or WordPress options
     * 
     * MISTAKE PREVENTION COMMENTS:
     * - WordPress options MUST override env file values
     * - Empty strings count as "configured" in WordPress, so check for actual content
     * - Log configuration issues for debugging but don't expose sensitive data
     */
    private function loadConfiguration() {
        // Try to load from .env file first
        $env_file = GARY_AI_PLUGIN_PATH . '.env';
        if (file_exists($env_file)) {
            $this->loadFromEnvFile($env_file);
        }
        
        // CRITICAL: Override with WordPress options if available (these take priority)
        $wp_api_key = get_option('gary_ai_contextual_api_key', '');
        $wp_datastore_id = get_option('gary_ai_datastore_id', '');
        $wp_agent_id = get_option('gary_ai_agent_id', '');
        $wp_agent_name = get_option('gary_ai_agent_name', '');
        
        // Handle false/null returns from get_option
        if ($wp_api_key === false) $wp_api_key = '';
        if ($wp_datastore_id === false) $wp_datastore_id = '';
        if ($wp_agent_id === false) $wp_agent_id = '';
        if ($wp_agent_name === false) $wp_agent_name = '';
        
        // Only use WordPress options if they have actual content (not just empty strings)
        if (!empty(trim($wp_api_key))) {
            $this->api_key = trim($wp_api_key);
        }
        if (!empty(trim($wp_datastore_id))) {
            $this->datastore_id = trim($wp_datastore_id);
        }
        if (!empty(trim($wp_agent_id))) {
            $this->agent_id = trim($wp_agent_id);
        }
        if (!empty(trim($wp_agent_name))) {
            $this->agent_name = trim($wp_agent_name);
        } else if (empty($this->agent_name)) {
            $this->agent_name = 'Gary AI';
        }
        
        // Enhanced validation and debugging
        $missing_configs = [];
        if (empty($this->api_key)) {
            $missing_configs[] = 'api_key';
        }
        if (empty($this->datastore_id)) {
            $missing_configs[] = 'datastore_id';
        }
        if (empty($this->agent_id)) {
            $missing_configs[] = 'agent_id';
        }
        
        if (!empty($missing_configs)) {
            $message = 'Gary AI Chatbot: Missing required configuration: ' . implode(', ', $missing_configs);
            error_log($message);
            
            // Log safe debugging info (without exposing sensitive data)
            error_log('Gary AI Debug: API Key configured=' . (!empty($this->api_key) ? 'YES' : 'NO'));
            error_log('Gary AI Debug: Agent ID configured=' . (!empty($this->agent_id) ? 'YES' : 'NO'));
            error_log('Gary AI Debug: Datastore ID configured=' . (!empty($this->datastore_id) ? 'YES' : 'NO'));
        }
    }
    
    /**
     * Load configuration from .env file
     */
    private function loadFromEnvFile($env_file) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            switch ($key) {
                case 'CONTEXTUAL_API_KEY':
                    $this->api_key = $value;
                    break;
                case 'DATASTORE_ID':
                    $this->datastore_id = $value;
                    break;
                case 'AGENT_ID':
                    $this->agent_id = $value;
                    break;
                case 'AGENT_NAME':
                    $this->agent_name = $value;
                    break;
            }
        }
    }
    
    /**
     * Check if client is properly configured
     */
    public function isConfigured() {
        return !empty($this->api_key) && !empty($this->datastore_id) && !empty($this->agent_id);
    }
    
    /**
     * Send query to Contextual AI
     * 
     * MISTAKE PREVENTION COMMENTS:
     * - MUST use OpenAI-style messages format: {"messages": [{"role": "user", "content": "..."}]}
     * - DO NOT add datastore_id, query, or retrieval_info to payload - these cause 422 errors
     * - API endpoint is: https://api.contextual.ai/v1/agents/{agent_id}/query
     * - Authorization header MUST be: "Bearer {api_key}"
     * - API returns: {message: {content: "..."}, conversation_id: "...", attributions: [...]}
     */
    public function query($message, $session_id = null, $stream = false) {
        // Generate cache key for non-streaming requests
        $cache_key = null;
        if (!$stream) {
            $cache_key = 'gary_ai_' . md5($this->agent_id . '|' . $message . '|' . $session_id);
            
            // Check cache first
            $cached_response = $this->getCachedResponse($cache_key);
            if ($cached_response !== null) {
                error_log('Gary AI: Returning cached response for query');
                return $cached_response;
            }
        }
        // Enhanced configuration check with debugging
        if (!$this->isConfigured()) {
            $debug_info = [
                'api_key_set' => !empty($this->api_key),
                'agent_id_set' => !empty($this->agent_id),
                'datastore_id_set' => !empty($this->datastore_id),
                'api_key_prefix' => !empty($this->api_key) ? substr($this->api_key, 0, 10) . '...' : 'EMPTY',
                'agent_id' => $this->agent_id ?: 'EMPTY',
                'datastore_id' => $this->datastore_id ?: 'EMPTY'
            ];
            
            error_log('Gary AI Configuration Debug: ' . json_encode($debug_info));
            throw new Exception('Contextual AI client is not properly configured. Check WordPress error log for details.');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $this->agent_id . '/query';
        
        // CRITICAL: Use EXACT format that works in testing - OpenAI-style messages only
        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ];
        
        // Add stream flag if requested
        if ($stream) {
            $payload['stream'] = true;
        }
        
        // CRITICAL: Use EXACT headers that work in testing
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'GaryAI-WordPress-Chatbot/1.0.0'
        ];
        
        // Log request details for debugging (without exposing sensitive data)
        error_log('Gary AI Request Debug: ' . json_encode([
            'endpoint' => $endpoint,
            'payload' => $payload,
            'headers_count' => count($headers),
            'has_auth_header' => isset($headers['Authorization']),
            'api_key_prefix' => substr($this->api_key, 0, 10) . '...'
        ]));
        
        if ($stream) {
            return $this->streamQuery($endpoint, $payload, $headers);
        } else {
            $response = $this->standardQuery($endpoint, $payload, $headers);
            
            // Cache successful responses
            if ($cache_key && $response && isset($response['success']) && $response['success']) {
                $this->setCachedResponse($cache_key, $response);
            }
            
                    return $response;
    }
    
    /**
     * Get cached response
     */
    private function getCachedResponse($cache_key) {
        if (!isset(self::$response_cache[$cache_key])) {
            return null;
        }
        
        $cached_item = self::$response_cache[$cache_key];
        
        // Check if cache is expired
        if (time() - $cached_item['timestamp'] > $this->cache_duration) {
            unset(self::$response_cache[$cache_key]);
            return null;
        }
        
        return $cached_item['response'];
    }
    
    /**
     * Set cached response
     */
    private function setCachedResponse($cache_key, $response) {
        try {
            // Limit cache size to prevent memory issues
            if (count(self::$response_cache) > 100) {
                // Remove oldest entries
                $oldest_keys = array_keys(array_slice(self::$response_cache, 0, 50, true));
                foreach ($oldest_keys as $old_key) {
                    unset(self::$response_cache[$old_key]);
                }
            }
            
            self::$response_cache[$cache_key] = array(
                'response' => $response,
                'timestamp' => time()
            );
            
            error_log('Gary AI: Cached response for key: ' . substr($cache_key, 0, 20) . '...');
        } catch (Exception $e) {
            error_log('Gary AI: Error caching response: ' . $e->getMessage());
        }
    }
    
    /**
     * Check network connectivity
     */
    private function checkNetworkConnectivity() {
        try {
            // Quick connectivity test to a reliable endpoint
            $test_response = wp_remote_get('https://api.contextual.ai/health', array(
                'timeout' => 5,
                'sslverify' => true,
                'redirection' => 0
            ));
            
            if (is_wp_error($test_response)) {
                error_log('Gary AI: Network connectivity test failed: ' . $test_response->get_error_message());
                return false;
            }
            
            $status_code = wp_remote_retrieve_response_code($test_response);
            return ($status_code >= 200 && $status_code < 400);
            
        } catch (Exception $e) {
            error_log('Gary AI: Network connectivity test exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check API version compatibility
     */
    private function checkApiVersionCompatibility() {
        static $version_checked = false;
        
        if ($version_checked) {
            return; // Only check once per request
        }
        
        try {
            // Check if we have a cached version check
            $version_cache_key = 'gary_ai_api_version_check';
            $cached_check = get_transient($version_cache_key);
            
            if ($cached_check !== false) {
                $version_checked = true;
                return;
            }
            
            // Quick version check to API info endpoint
            $version_response = wp_remote_get('https://api.contextual.ai/version', array(
                'timeout' => 3,
                'sslverify' => true,
                'redirection' => 0
            ));
            
            if (!is_wp_error($version_response)) {
                $version_data = json_decode(wp_remote_retrieve_body($version_response), true);
                
                if (isset($version_data['version'])) {
                    $api_version = $version_data['version'];
                    
                    // Check if API version is compatible
                    if (strpos($api_version, $this->expected_api_version) !== 0) {
                        error_log("Gary AI: API version mismatch. Expected: {$this->expected_api_version}, Got: {$api_version}");
                        
                        // Store warning in transient for admin notice
                        set_transient('gary_ai_api_version_warning', array(
                            'expected' => $this->expected_api_version,
                            'actual' => $api_version
                        ), DAY_IN_SECONDS);
                    } else {
                        error_log("Gary AI: API version compatible: {$api_version}");
                    }
                }
            }
            
            // Cache the check for 1 hour
            set_transient($version_cache_key, time(), HOUR_IN_SECONDS);
            $version_checked = true;
            
        } catch (Exception $e) {
            error_log('Gary AI: API version check failed: ' . $e->getMessage());
            // Don't fail the request on version check errors
        }
    }
    
    /**
     * Load proxy configuration
     */
    private function loadProxyConfiguration() {
        $this->proxy_host = get_option('gary_ai_proxy_host', '');
        $this->proxy_port = get_option('gary_ai_proxy_port', '');
        $this->proxy_username = get_option('gary_ai_proxy_username', '');
        $this->proxy_password = get_option('gary_ai_proxy_password', '');
        
        if (!empty($this->proxy_host)) {
            error_log('Gary AI: Proxy configuration loaded - Host: ' . $this->proxy_host . ':' . $this->proxy_port);
        }
    }
    
    /**
     * Get proxy settings for wp_remote_request
     */
    private function getProxySettings() {
        $proxy_settings = array();
        
        if (!empty($this->proxy_host) && !empty($this->proxy_port)) {
            $proxy_settings['proxy'] = array(
                'host' => $this->proxy_host,
                'port' => $this->proxy_port
            );
            
            if (!empty($this->proxy_username) && !empty($this->proxy_password)) {
                $proxy_settings['proxy']['username'] = $this->proxy_username;
                $proxy_settings['proxy']['password'] = $this->proxy_password;
            }
            
            error_log('Gary AI: Using proxy settings for API request');
        }
        
        return $proxy_settings;
    }
}
    
    /**
     * Send standard (non-streaming) query
     * 
     * MISTAKE PREVENTION COMMENTS:
     * - WordPress wp_remote_request is reliable, keep using it
     * - API returns HTTP 200 on success with JSON body
     * - Response format: {message: {content: "..."}, conversation_id: "...", message_id: "...", attributions: [...], retrieval_contents: [...]}
     * - ALWAYS log full response on errors for debugging
     * - Handle WordPress-specific HTTP error responses
     */
    private function standardQuery($endpoint, $payload, $headers) {
        // Build WordPress HTTP request args
        // Enhanced SSL handling for problematic hosting environments
        $ssl_verify = true;
        
        // Allow SSL verification bypass in debug mode for problematic hosts
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $ssl_verify = apply_filters('gary_ai_ssl_verify', true);
            if (!$ssl_verify) {
                error_log('Gary AI: SSL verification disabled via filter');
            }
        }
        
        $args = array_merge([
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => $this->timeout,
            'sslverify' => $ssl_verify,
            'redirection' => 0,  // Disable redirects for API calls
            'user-agent' => 'Gary AI WordPress Plugin/' . (defined('GARY_AI_VERSION') ? GARY_AI_VERSION : '1.0.0')
        ], $this->getProxySettings());
        
        // Pre-flight network connectivity check
        if (!$this->checkNetworkConnectivity()) {
            throw new Exception('Network connectivity check failed. Please check your internet connection.');
        }
        
        // Check API version compatibility
        $this->checkApiVersionCompatibility();
        
        // Log request attempt (for debugging)
        error_log('Gary AI: Sending request to ' . $endpoint);
        
        $response = wp_remote_request($endpoint, $args);
        
        // Handle WordPress HTTP errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Gary AI WordPress HTTP Error: ' . $error_message);
            throw new Exception('Contextual AI API request failed: ' . $error_message);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers_received = wp_remote_retrieve_headers($response);
        
        // Enhanced logging for debugging
        error_log('Gary AI Response Debug: ' . json_encode([
            'status_code' => $status_code,
            'content_type' => isset($headers_received['content-type']) ? $headers_received['content-type'] : 'unknown',
            'body_length' => strlen($body),
            'body_preview' => substr($body, 0, 200) . '...'
        ]));
        
        // Handle non-200 responses with detailed error information and rate limiting
        if ($status_code !== 200) {
            // Always log the full response for debugging
            error_log("Gary AI API Response (HTTP $status_code): " . $body);
            
            $error_data = json_decode($body, true);
            $error_message = 'Unknown API error';
            
            // Handle rate limiting specifically
            if ($status_code === 429) {
                $retry_after = isset($headers_received['retry-after']) ? $headers_received['retry-after'] : 60;
                error_log('Gary AI: Rate limit exceeded (429). Retry after: ' . $retry_after . ' seconds');
                throw new Exception('API rate limit exceeded. Please wait ' . $retry_after . ' seconds before trying again.');
            }
            
            // Handle server errors
            if ($status_code >= 500) {
                error_log('Gary AI: Server Error (' . $status_code . '): ' . $body);
                throw new Exception('Contextual AI service is temporarily unavailable. Please try again later.');
            }
            
            // Parse error message from response
            if (json_last_error() === JSON_ERROR_NONE && is_array($error_data)) {
                if (isset($error_data['error'])) {
                    $error_message = $error_data['error'];
                } elseif (isset($error_data['detail'])) {
                    $error_message = $error_data['detail'];
                } elseif (isset($error_data['message'])) {
                    $error_message = $error_data['message'];
                }
            } else {
                $error_message = "HTTP $status_code - Invalid response format";
            }
            
            throw new Exception("Contextual AI API error (HTTP $status_code): $error_message");
        }
        
        // Parse successful response
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Gary AI JSON Parse Error: ' . json_last_error_msg() . ' - Body: ' . $body);
            throw new Exception('Invalid JSON response from Contextual AI API: ' . json_last_error_msg());
        }
        
        // Log successful response structure for debugging
        error_log('Gary AI Success Response Keys: ' . implode(', ', array_keys($data)));
        
        return $this->formatResponse($data);
    }
    
    /**
     * Send streaming query (for SSE)
     */
    private function streamQuery($endpoint, $payload, $headers) {
        // For streaming, we'll use cURL for better control
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $this->formatCurlHeaders($headers),
            CURLOPT_WRITEFUNCTION => [$this, 'handleStreamChunk'],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'GaryAI-WordPress-Chatbot/1.0.0'
        ]);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Contextual AI streaming request failed: $error");
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("Contextual AI streaming API error (HTTP $http_code)");
        }
        
        return true; // Streaming handled by callback
    }
    
    /**
     * Handle streaming response chunks
     */
    public function handleStreamChunk($ch, $chunk) {
        // Parse Server-Sent Events format
        $lines = explode("\n", $chunk);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) continue;
            
            if (strpos($line, 'data: ') === 0) {
                $data = substr($line, 6);
                
                if ($data === '[DONE]') {
                    echo "event: complete\n";
                    echo "data: {\"status\": \"complete\"}\n\n";
                    flush();
                    break;
                }
                
                $json_data = json_decode($data, true);
                if ($json_data && isset($json_data['content'])) {
                    echo "event: chunk\n";
                    echo "data: " . json_encode([
                        'chunk' => $json_data['content'],
                        'citations' => $json_data['citations'] ?? []
                    ]) . "\n\n";
                    flush();
                }
            }
        }
        
        return strlen($chunk);
    }
    
    /**
     * Format headers for cURL
     */
    private function formatCurlHeaders($headers) {
        $curl_headers = [];
        foreach ($headers as $key => $value) {
            $curl_headers[] = "$key: $value";
        }
        return $curl_headers;
    }
    
    /**
     * Format API response for WordPress
     * 
     * MISTAKE PREVENTION COMMENTS:
     * - API returns: {message: {content: "...", role: "assistant"}, conversation_id: "...", message_id: "...", attributions: [...], retrieval_contents: [...]}
     * - ALWAYS extract from data.message.content (this is the confirmed format)
     * - Map conversation_id to session_id for WordPress compatibility
     * - Map attributions to citations for WordPress compatibility  
     * - Map retrieval_contents to retrieval_info for WordPress compatibility
     * - Log the response structure for debugging when content extraction fails
     */
    private function formatResponse($data) {
        // Log response structure for debugging
        error_log('Gary AI Formatting Response Keys: ' . implode(', ', array_keys($data)));
        
        // CRITICAL: Extract message content using multiple format attempts
        $message_content = '';
        
        // Try OpenAI/Standard format first: response.choices[0].message.content
        if (isset($data['choices']) && is_array($data['choices']) && 
            isset($data['choices'][0]['message']['content'])) {
            $message_content = $data['choices'][0]['message']['content'];
            error_log('Gary AI: Used OpenAI choices format');
        }
        // Try Contextual AI format: response.message.content  
        elseif (isset($data['message']) && isset($data['message']['content'])) {
            $message_content = $data['message']['content'];
            error_log('Gary AI: Used Contextual AI message.content format');
        }
        // Try direct content field
        elseif (isset($data['content'])) {
            $message_content = $data['content'];
            error_log('Gary AI: Used direct content format');
        }
        // Try response field
        elseif (isset($data['response'])) {
            $message_content = $data['response'];
            error_log('Gary AI: Used response field format');
        }
        // Try message as string
        elseif (isset($data['message']) && is_string($data['message'])) {
            $message_content = $data['message'];
            error_log('Gary AI: Used string message format');
        }
        // Try direct text field (some APIs use this)
        elseif (isset($data['text'])) {
            $message_content = $data['text'];
            error_log('Gary AI: Used text field format');
        }
        else {
            // Log detailed structure when extraction fails
            error_log('Gary AI: Failed to extract message content. Response structure: ' . json_encode($data, JSON_PRETTY_PRINT));
            $message_content = 'Error: Unable to extract response content. API response format not recognized. Check WordPress error log for details.';
        }
        
        // Build response using confirmed field mappings from successful test
        $response = [
            'success' => true,
            'message' => $message_content,
            'agent_name' => $this->agent_name,
            // Map API fields to WordPress expected fields (based on working test)
            'citations' => $data['attributions'] ?? $data['citations'] ?? [],  // attributions -> citations
            'retrieval_info' => $data['retrieval_contents'] ?? $data['retrieval_info'] ?? [],  // retrieval_contents -> retrieval_info
            'session_id' => $data['conversation_id'] ?? $data['session_id'] ?? null,  // conversation_id -> session_id
            'message_id' => $data['message_id'] ?? null,
            'timestamp' => current_time('mysql')
        ];
        
        // Log successful mapping for debugging
        error_log('Gary AI Response Mapped: ' . json_encode([
            'has_message' => !empty($response['message']),
            'message_length' => strlen($response['message']),
            'has_session_id' => !empty($response['session_id']),
            'citations_count' => count($response['citations']),
            'retrieval_info_count' => count($response['retrieval_info'])
        ]));
        
        // Process citations if available
        if (!empty($response['citations'])) {
            $response['citations'] = $this->processCitations($response['citations']);
        }
        
        return $response;
    }
    
    /**
     * Process and format citations
     */
    private function processCitations($citations) {
        $processed = [];
        
        foreach ($citations as $citation) {
            $processed[] = [
                'id' => $citation['id'] ?? uniqid(),
                'title' => $citation['title'] ?? 'Untitled',
                'url' => $citation['url'] ?? '',
                'snippet' => $citation['snippet'] ?? '',
                'relevance' => $citation['relevance'] ?? 0.0
            ];
        }
        
        return $processed;
    }
    
    /**
     * List Datastores
     * GET /datastores
     */
    public function listDatastores($agent_id = null, $limit = 1000, $cursor = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores';
        $params = ['limit' => $limit];
        
        if ($agent_id) {
            $params['agent_id'] = $agent_id;
        }
        
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }
    
    /**
     * Create Datastore
     * POST /datastores
     */
    public function createDatastore($name, $configuration = []) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores';
        
        $payload = [
            'name' => $name,
            'configuration' => $configuration ?: new stdClass()
        ];
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }
    
    /**
     * Reset Datastore
     * PUT /datastores/{datastore_id}/reset
     */
    public function resetDatastore($datastore_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/reset';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $headers);
    }
    
    /**
     * Edit Datastore Configuration
     * PUT /datastores/{datastore_id}
     */
    public function editDatastore($datastore_id, $name = null, $configuration = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id;
        
        $payload = [];
        if ($name !== null) {
            $payload['name'] = $name;
        }
        if ($configuration !== null) {
            $payload['configuration'] = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $headers, $payload);
    }
    
    /**
     * Delete Datastore
     * DELETE /datastores/{datastore_id}
     */
    public function deleteDatastore($datastore_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers);
    }
    
        /**
     * Get Datastore Details
     * GET /datastores/{datastore_id}
     */
    public function getDatastore($datastore_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }

        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id;

        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];

        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get Datastore Metadata
     * GET /datastores/{datastore_id}/metadata
     */
    public function getDatastoreMetadata($datastore_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }

        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/metadata';

        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];

        return $this->makeRequest('GET', $endpoint, $headers);
    }
    
    /**
     * List Documents
     * GET /datastores/{datastore_id}/documents
     */
    public function listDocuments($datastore_id, $limit = 1000, $cursor = null, $ingestion_job_status = null, $uploaded_after = null, $uploaded_before = null, $document_name_prefix = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/documents';
        $params = ['limit' => $limit];
        
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        
        if ($ingestion_job_status) {
            $params['ingestion_job_status'] = is_array($ingestion_job_status) ? $ingestion_job_status : [$ingestion_job_status];
        }
        
        if ($uploaded_after) {
            $params['uploaded_after'] = $uploaded_after;
        }
        
        if ($uploaded_before) {
            $params['uploaded_before'] = $uploaded_before;
        }
        
        if ($document_name_prefix) {
            $params['document_name_prefix'] = $document_name_prefix;
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }
    
    /**
     * Ingest Document
     * POST /datastores/{datastore_id}/documents
     */
    public function ingestDocument($datastore_id, $file_path, $metadata = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        if (!file_exists($file_path)) {
            throw new Exception('File does not exist: ' . $file_path);
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/documents';
        
        // Use cURL for multipart/form-data
        $ch = curl_init();
        
        $post_fields = [
            'file' => new CURLFile($file_path)
        ];
        
        if ($metadata) {
            $post_fields['metadata'] = json_encode($metadata);
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'User-Agent: GaryAI-WordPress-Chatbot/1.0.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('Document ingestion request failed: ' . $curl_error);
        }
        
        if ($http_code !== 200 && $http_code !== 201) {
            $error_data = json_decode($response, true);
            $error_message = isset($error_data['error']) ? $error_data['error'] : 'Unknown API error';
            throw new Exception("Document ingestion API error (HTTP $http_code): $error_message");
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Document ingestion API');
        }
        
        return $data;
    }
    
    /**
     * Get Document Metadata
     * GET /datastores/{datastore_id}/documents/{document_id}/metadata
     */
    public function getDocumentMetadata($datastore_id, $document_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/documents/' . $document_id . '/metadata';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }
    
    /**
     * Update Document Metadata
     * POST /datastores/{datastore_id}/documents/{document_id}/metadata
     */
    public function updateDocumentMetadata($datastore_id, $document_id, $custom_metadata = null, $custom_metadata_config = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/documents/' . $document_id . '/metadata';
        
        $payload = [];
        if ($custom_metadata !== null) {
            $payload['custom_metadata'] = $custom_metadata;
        }
        if ($custom_metadata_config !== null) {
            $payload['custom_metadata_config'] = $custom_metadata_config;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }
    
    /**
     * Delete Document
     * DELETE /datastores/{datastore_id}/documents/{document_id}
     */
    public function deleteDocument($datastore_id, $document_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/datastores/' . $datastore_id . '/documents/' . $document_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers);
    }
    
    /**
     * Generic request method
     */
    private function makeRequest($method, $endpoint, $headers, $payload = null) {
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true
        ];
        
        if ($payload !== null) {
            $args['body'] = json_encode($payload);
        }
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Contextual AI API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Handle different success codes for different endpoints
        $success_codes = [200, 201, 204];
        if (!in_array($status_code, $success_codes)) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']) ? $error_data['error'] : 'Unknown API error';
            error_log("Contextual AI API Response (HTTP $status_code): " . $body);
            throw new Exception("Contextual AI API error (HTTP $status_code): $error_message");
        }
        
        // Handle empty response for DELETE operations
        if ($status_code === 204 || empty($body)) {
            return ['success' => true];
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Contextual AI API');
        }
        
        return $data;
    }
    
    /**
     * Test API connection
     * 
     * MISTAKE PREVENTION COMMENTS:
     * - Use the exact test message that worked in PowerShell testing
     * - Return detailed debugging information on failure
     * - Log test attempts for troubleshooting
     * - Include configuration status in response
     */
    public function testConnection() {
        error_log('Gary AI: Starting connection test');
        
        try {
            // Use the exact test message that worked in PowerShell testing
            $test_message = 'Hello, this is a connection test from WordPress.';
            
            // Log configuration status before test
            error_log('Gary AI Test Config: ' . json_encode([
                'api_key_configured' => !empty($this->api_key),
                'agent_id_configured' => !empty($this->agent_id),
                'datastore_id_configured' => !empty($this->datastore_id),
                'api_key_prefix' => !empty($this->api_key) ? substr($this->api_key, 0, 15) . '...' : 'EMPTY'
            ]));
            
            $response = $this->query($test_message);
            
            error_log('Gary AI: Connection test successful');
            
            return [
                'success' => true,
                'message' => 'Connection successful! AI responded: ' . substr($response['message'], 0, 100) . '...',
                'agent_name' => $this->agent_name,
                'response' => $response['message'],
                'session_id' => $response['session_id'] ?? null,
                'citations_count' => count($response['citations'] ?? []),
                'debug_info' => [
                    'endpoint_used' => self::API_BASE_URL . '/v1/agents/' . $this->agent_id . '/query',
                    'api_key_length' => strlen($this->api_key ?? ''),
                    'agent_id' => $this->agent_id
                ]
            ];
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log('Gary AI: Connection test failed - ' . $error_message);
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $error_message,
                'debug_info' => [
                    'error_type' => get_class($e),
                    'configuration_status' => [
                        'api_key_set' => !empty($this->api_key),
                        'agent_id_set' => !empty($this->agent_id),
                        'datastore_id_set' => !empty($this->datastore_id)
                    ],
                    'endpoint' => self::API_BASE_URL . '/v1/agents/' . ($this->agent_id ?: 'MISSING') . '/query'
                ]
            ];
        }
    }
    
    /**
     * Get client configuration (safe for display)
     */
    public function getConfiguration() {
        return [
            'api_key_configured' => !empty($this->api_key),
            'datastore_id' => $this->datastore_id,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->agent_name,
            'is_configured' => $this->isConfigured()
        ];
    }

    // ============================================
    // AGENT MANAGEMENT ENDPOINTS  
    // ============================================

    /**
     * List Agents
     * GET /agents
     */
    public function listAgents($limit = 1000, $cursor = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents';
        
        $query_params = [
            'limit' => $limit
        ];
        
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $url = $endpoint . '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key
        ];
        
        return $this->makeRequest('GET', $url, null, $headers);
    }

    /**
     * Create Agent  
     * POST /agents
     */
    public function createAgent($name, $description = '', $system_prompt = '', $no_retrieval_system_prompt = '', $filter_prompt = '', $suggested_queries = [], $agent_configs = [], $datastore_ids = []) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents';
        
        $payload = [
            'name' => $name,
            'description' => $description,
            'system_prompt' => $system_prompt,
            'no_retrieval_system_prompt' => $no_retrieval_system_prompt,
            'filter_prompt' => $filter_prompt,
            'suggested_queries' => $suggested_queries,
            'agent_configs' => $agent_configs,
            'datastore_ids' => $datastore_ids
        ];
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Edit Agent
     * PUT /agents/{agent_id}
     */
    public function editAgent($agent_id, $name = null, $description = null, $system_prompt = null, $no_retrieval_system_prompt = null, $filter_prompt = null, $suggested_queries = null, $agent_configs = null, $datastore_ids = null, $llm_model_id = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id;
        
        $payload = [];
        if ($name !== null) $payload['name'] = $name;
        if ($description !== null) $payload['description'] = $description;
        if ($system_prompt !== null) $payload['system_prompt'] = $system_prompt;
        if ($no_retrieval_system_prompt !== null) $payload['no_retrieval_system_prompt'] = $no_retrieval_system_prompt;
        if ($filter_prompt !== null) $payload['filter_prompt'] = $filter_prompt;
        if ($suggested_queries !== null) $payload['suggested_queries'] = $suggested_queries;
        if ($agent_configs !== null) $payload['agent_configs'] = $agent_configs;
        if ($datastore_ids !== null) $payload['datastore_ids'] = $datastore_ids;
        if ($llm_model_id !== null) $payload['llm_model_id'] = $llm_model_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $payload, $headers);
    }

    /**
     * Delete Agent
     * DELETE /agents/{agent_id}
     */
    public function deleteAgent($agent_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key
        ];
        
        return $this->makeRequest('DELETE', $endpoint, null, $headers);
    }

    /**
     * Get Agent Details
     * GET /agents/{agent_id}
     */
    public function getAgent($agent_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get Agent Metadata
     * GET /agents/{agent_id}/metadata
     */
    public function getAgentMetadata($agent_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/metadata';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key
        ];
        
        return $this->makeRequest('GET', $endpoint, null, $headers);
    }

    /**
     * Reset Agent
     * PUT /agents/{agent_id}/reset
     */
    public function resetAgent($agent_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/reset';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, [], $headers);
    }

    /**
     * Get Retrieval Info
     * GET /agents/{agent_id}/query/{message_id}/retrieval/info
     */
    public function getRetrievalInfo($agent_id, $message_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/query/' . $message_id . '/retrieval/info';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get Agent Metrics
     * GET /agents/{agent_id}/metrics
     */
    public function getAgentMetrics($agent_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/metrics';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Start Agent Evaluation
     * POST /agents/{agent_id}/evaluate
     */
    public function startAgentEvaluation($agent_id, $dataset_id, $evaluation_config = []) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/evaluate';
        
        $payload = [
            'dataset_id' => $dataset_id
        ];
        
        if (!empty($evaluation_config)) {
            $payload['evaluation_config'] = $evaluation_config;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * List Agent Evaluations
     * GET /agents/{agent_id}/evaluate
     */
    public function listAgentEvaluations($agent_id, $limit = 1000, $cursor = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/evaluate';
        $params = ['limit' => $limit];
        
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get Agent Evaluation Results
     * GET /agents/{agent_id}/evaluate/{evaluation_id}
     */
    public function getAgentEvaluationResults($agent_id, $evaluation_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/evaluate/' . $evaluation_id;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Cancel Agent Evaluation
     * POST /agents/{agent_id}/evaluate/{evaluation_id}/cancel
     */
    public function cancelAgentEvaluation($agent_id, $evaluation_id) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/agents/' . $agent_id . '/evaluate/' . $evaluation_id . '/cancel';
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, []);
    }

    /**
     * Generate Text
     * POST /generate
     */
    public function generateText($prompt, $max_tokens = 1000, $temperature = 0.7, $model = null, $system_prompt = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/generate';
        
        $payload = [
            'prompt' => $prompt,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        
        if ($model) {
            $payload['model'] = $model;
        }
        
        if ($system_prompt) {
            $payload['system_prompt'] = $system_prompt;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Rerank Documents
     * POST /rerank
     */
    public function rerank($query, $documents, $top_k = 10, $model = null) {
        if (!$this->isConfigured()) {
            throw new Exception('Contextual AI client is not properly configured');
        }
        
        $endpoint = self::API_BASE_URL . '/v1/rerank';
        
        $payload = [
            'query' => $query,
            'documents' => $documents,
            'top_k' => $top_k
        ];
        
        if ($model) {
            $payload['model'] = $model;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * List evaluation datasets for an agent
     */
    public function listEvaluationDatasets($agent_id, $limit = 1000, $cursor = null) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate";
        
        $query_params = ['limit' => $limit];
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Create evaluation dataset for an agent
     */
    public function createEvaluationDataset($agent_id, $name, $configuration = []) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate";
        
        $payload = [
            'name' => $name
        ];
        
        if (!empty($configuration)) {
            $payload['configuration'] = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Get specific evaluation dataset details
     */
    public function getEvaluationDataset($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate/{$dataset_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Update evaluation dataset configuration
     */
    public function updateEvaluationDataset($agent_id, $dataset_id, $name = null, $configuration = null) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate/{$dataset_id}";
        
        $payload = [];
        if ($name) {
            $payload['name'] = $name;
        }
        if ($configuration) {
            $payload['configuration'] = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $headers, $payload);
    }

    /**
     * Delete evaluation dataset
     */
    public function deleteEvaluationDataset($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate/{$dataset_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers);
    }

    /**
     * Get evaluation dataset metadata
     */
    public function getEvaluationDatasetMetadata($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/evaluate/{$dataset_id}/metadata";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * List tuning datasets for an agent
     */
    public function listTuningDatasets($agent_id, $limit = 1000, $cursor = null) {
        $endpoint = "agents/{$agent_id}/datasets/tune";
        
        $query_params = ['limit' => $limit];
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Create tuning dataset for an agent
     */
    public function createTuningDataset($agent_id, $name, $configuration = []) {
        $endpoint = "agents/{$agent_id}/datasets/tune";
        
        $payload = [
            'name' => $name
        ];
        
        if (!empty($configuration)) {
            $payload['configuration'] = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Get specific tuning dataset details
     */
    public function getTuningDataset($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/tune/{$dataset_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Update tuning dataset configuration
     */
    public function updateTuningDataset($agent_id, $dataset_id, $name = null, $configuration = null) {
        $endpoint = "agents/{$agent_id}/datasets/tune/{$dataset_id}";
        
        $payload = [];
        if ($name) {
            $payload['name'] = $name;
        }
        if ($configuration) {
            $payload['configuration'] = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $headers, $payload);
    }

    /**
     * Delete tuning dataset
     */
    public function deleteTuningDataset($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/tune/{$dataset_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers);
    }

    /**
     * Get tuning dataset metadata
     */
    public function getTuningDatasetMetadata($agent_id, $dataset_id) {
        $endpoint = "agents/{$agent_id}/datasets/tune/{$dataset_id}/metadata";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Start agent tuning job
     */
    public function startAgentTuning($agent_id, $configuration = []) {
        $endpoint = "agents/{$agent_id}/tune";
        
        $payload = [];
        if (!empty($configuration)) {
            $payload = $configuration;
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * List agent tuning jobs
     */
    public function listAgentTuningJobs($agent_id, $limit = 1000, $cursor = null) {
        $endpoint = "agents/{$agent_id}/tune";
        
        $query_params = ['limit' => $limit];
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get specific tuning job details
     */
    public function getAgentTuningJob($agent_id, $job_id) {
        $endpoint = "agents/{$agent_id}/tune/{$job_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Cancel/delete tuning job
     */
    public function cancelAgentTuningJob($agent_id, $job_id) {
        $endpoint = "agents/{$agent_id}/tune/{$job_id}";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers);
    }

    /**
     * List available tuning models
     */
    public function listAgentTuningModels($agent_id) {
        $endpoint = "agents/{$agent_id}/tune/models";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Execute language model unit operation
     */
    public function executeLMUnit($operation_config) {
        $endpoint = "lmunit";
        
        $payload = $operation_config;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * List users
     */
    public function listUsers($limit = 1000, $cursor = null) {
        $endpoint = "users";
        
        $query_params = ['limit' => $limit];
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Update user information
     */
    public function updateUser($user_data) {
        $endpoint = "users";
        
        $payload = $user_data;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('PUT', $endpoint, $headers, $payload);
    }

    /**
     * Invite new user
     */
    public function inviteUser($email, $user_config = []) {
        $endpoint = "users";
        
        $payload = [
            'email' => $email
        ];
        
        if (!empty($user_config)) {
            $payload = array_merge($payload, $user_config);
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Remove user
     */
    public function removeUser($user_id) {
        $endpoint = "users";
        
        $payload = [
            'user_id' => $user_id
        ];
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('DELETE', $endpoint, $headers, $payload);
    }

    /**
     * Submit document for parsing
     */
    public function submitDocumentParsing($document, $config = []) {
        $endpoint = "parse";
        
        $payload = [
            'document' => $document
        ];
        
        if (!empty($config)) {
            $payload = array_merge($payload, $config);
        }
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('POST', $endpoint, $headers, $payload);
    }

    /**
     * Get parsing job status
     */
    public function getParsingJobStatus($job_id) {
        $endpoint = "parse/jobs/{$job_id}/status";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * Get parsing job results
     */
    public function getParsingJobResults($job_id) {
        $endpoint = "parse/jobs/{$job_id}/results";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }

    /**
     * List all parsing jobs
     */
    public function listParsingJobs($limit = 1000, $cursor = null) {
        $endpoint = "parse/jobs";
        
        $query_params = ['limit' => $limit];
        if ($cursor) {
            $query_params['cursor'] = $cursor;
        }
        
        $endpoint .= '?' . http_build_query($query_params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        return $this->makeRequest('GET', $endpoint, $headers);
    }
    
    /**
     * Send a message to the AI and get a response
     * This is a simplified method for the basic chat functionality
     */
    public function sendMessage($message, $options = array()) {
        // Use the query method for agent communication
        if (!empty($this->agent_id)) {
            $response = $this->query($message, isset($options['session_id']) ? $options['session_id'] : null);
            
            if ($response && isset($response['message'])) {
                return array(
                    'message' => $response['message'],
                    'citations' => isset($response['citations']) ? $response['citations'] : array()
                );
            }
        }
        
        // Fallback to a simple response if agent query fails
        return array(
            'message' => 'I apologize, but I am unable to process your request at the moment. Please ensure the Contextual AI API is properly configured.',
            'citations' => array()
        );
    }
}
