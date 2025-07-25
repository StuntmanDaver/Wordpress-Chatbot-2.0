<?php
/**
 * JWT Authentication Handler for Gary AI Chatbot
 * 
 * Handles JWT token generation, validation, and user authentication
 * for secure API access with 30-minute TTL and silent refresh capability.
 * 
 * @package GaryAIChatbot
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GaryAIJWTAuth {
    
    /**
     * JWT secret key
     */
    private $jwt_secret;
    
    /**
     * Encryption key for additional security
     */
    private $encryption_key;
    
    /**
     * Token expiration time (30 minutes)
     */
    private const TOKEN_EXPIRY = 1800; // 30 minutes in seconds
    
    /**
     * Refresh threshold (5 minutes before expiry)
     */
    private const REFRESH_THRESHOLD = 300; // 5 minutes in seconds
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->loadConfiguration();
    }
    
    /**
     * Load JWT configuration from environment or WordPress constants
     */
    private function loadConfiguration() {
        // Try to load from .env file first
        $env_file = GARY_AI_PLUGIN_PATH . '.env';
        if (file_exists($env_file)) {
            $this->loadFromEnvFile($env_file);
        }
        
        // Override with WordPress constants if available
        if (defined('GARY_AI_JWT_SECRET')) {
            $this->jwt_secret = GARY_AI_JWT_SECRET;
        }
        
        if (defined('GARY_AI_ENCRYPTION_KEY')) {
            $this->encryption_key = GARY_AI_ENCRYPTION_KEY;
        }
        
        // Validate required configuration
        if (empty($this->jwt_secret) || empty($this->encryption_key)) {
            error_log('Gary AI Chatbot: Missing JWT configuration. Falling back to nonce authentication.');
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
                case 'JWT_SECRET':
                    $this->jwt_secret = $value;
                    break;
                case 'ENCRYPTION_KEY':
                    $this->encryption_key = $value;
                    break;
            }
        }
    }
    
    /**
     * Check if JWT authentication is properly configured
     */
    public function isConfigured() {
        return !empty($this->jwt_secret) && !empty($this->encryption_key);
    }
    
    /**
     * Generate JWT token for authenticated user
     */
    public function generateToken($user_id = null, $session_id = null) {
        if (!$this->isConfigured()) {
            return false;
        }
        
        $current_time = time();
        $user_id = $user_id ?: get_current_user_id();
        $session_id = $session_id ?: wp_generate_uuid4();
        
        // Create JWT header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        
        // Create JWT payload
        $payload = json_encode([
            'iss' => get_site_url(), // Issuer
            'aud' => 'gary-ai-chatbot', // Audience
            'iat' => $current_time, // Issued at
            'exp' => $current_time + self::TOKEN_EXPIRY, // Expiration
            'user_id' => $user_id,
            'session_id' => $session_id,
            'capabilities' => $this->getUserCapabilities($user_id)
        ]);
        
        // Encode header and payload
        $header_encoded = $this->base64UrlEncode($header);
        $payload_encoded = $this->base64UrlEncode($payload);
        
        // Create signature
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->jwt_secret, true);
        $signature_encoded = $this->base64UrlEncode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken($token) {
        if (!$this->isConfigured() || empty($token)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->jwt_secret, true);
        $signature_check = $this->base64UrlEncode($signature);
        
        if (!hash_equals($signature_encoded, $signature_check)) {
            return false;
        }
        
        // Decode and validate payload
        $payload = json_decode($this->base64UrlDecode($payload_encoded), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Check issuer
        if (isset($payload['iss']) && $payload['iss'] !== get_site_url()) {
            return false;
        }
        
        // Check audience
        if (isset($payload['aud']) && $payload['aud'] !== 'gary-ai-chatbot') {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Check if token needs refresh
     */
    public function needsRefresh($token) {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return true; // Invalid token needs refresh
        }
        
        $expires_at = $payload['exp'] ?? 0;
        $refresh_time = $expires_at - self::REFRESH_THRESHOLD;
        
        return time() >= $refresh_time;
    }
    
    /**
     * Refresh JWT token
     */
    public function refreshToken($token) {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return false;
        }
        
        // Generate new token with same user and session
        return $this->generateToken($payload['user_id'], $payload['session_id']);
    }
    
    /**
     * Get user capabilities for JWT payload
     */
    private function getUserCapabilities($user_id) {
        if (!$user_id) {
            return ['guest'];
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return ['guest'];
        }
        
        $capabilities = [];
        
        // Add relevant capabilities for chatbot access
        if ($user->has_cap('read')) {
            $capabilities[] = 'read';
        }
        
        if ($user->has_cap('edit_posts')) {
            $capabilities[] = 'edit_posts';
        }
        
        if ($user->has_cap('manage_options')) {
            $capabilities[] = 'admin';
        }
        
        return empty($capabilities) ? ['authenticated'] : $capabilities;
    }
    
    /**
     * Base64 URL-safe encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL-safe decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Generate JWT token for API response
     */
    public function generateApiToken($session_id = null) {
        $token = $this->generateToken(null, $session_id);
        
        if (!$token) {
            return null;
        }
        
        return [
            'token' => $token,
            'expires_in' => self::TOKEN_EXPIRY,
            'token_type' => 'Bearer'
        ];
    }
    
    /**
     * Validate authorization header
     */
    public function validateAuthorizationHeader($auth_header) {
        if (empty($auth_header)) {
            return false;
        }
        
        // Check for Bearer token format
        if (strpos($auth_header, 'Bearer ') !== 0) {
            return false;
        }
        
        $token = substr($auth_header, 7); // Remove 'Bearer ' prefix
        return $this->validateToken($token);
    }
}
