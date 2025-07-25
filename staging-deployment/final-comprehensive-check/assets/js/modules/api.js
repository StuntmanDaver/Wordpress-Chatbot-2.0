/**
 * Gary AI Chat Widget - API Module
 * Handles all communication with WordPress backend and external APIs
 */

export const API = {
    
    /**
     * Initialize API module
     */
    init(config = {}) {
        this.config = { ...this.defaultConfig, ...config };
        this.rateLimiter = new RateLimiter(this.config.rateLimit);
        console.log('Gary AI API: Module initialized');
        return this;
    },
    
    defaultConfig: {
        endpoint: garyAI?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: garyAI?.nonce || '',
        timeout: 30000,
        rateLimit: {
            maxRequests: 10,
            timeWindow: 60000, // 1 minute
            lockoutDuration: 300000 // 5 minutes
        }
    },
    
    /**
     * Send a chat message to the AI
     */
    async sendMessage(message, sessionId) {
        console.log('Gary AI API: Sending message:', message.substring(0, 50) + '...');
        
        // Check rate limiting
        if (!this.rateLimiter.canMakeRequest()) {
            throw new Error('Rate limit exceeded. Please wait before sending another message.');
        }
        
        try {
            this.rateLimiter.recordRequest();
            
            const response = await this.makeRequest({
                action: 'gary_ai_chat',
                message: message,
                session_id: sessionId,
                nonce: this.config.nonce
            });
            
            if (!response.success) {
                throw new Error(response.data?.message || 'Server error occurred');
            }
            
            return response.data;
            
        } catch (error) {
            console.error('Gary AI API: Message send failed:', error);
            throw error;
        }
    },
    
    /**
     * Test API connection
     */
    async testConnection() {
        console.log('Gary AI API: Testing connection');
        
        try {
            const response = await this.makeRequest({
                action: 'gary_ai_test_connection',
                nonce: this.config.nonce
            });
            
            return response.success;
            
        } catch (error) {
            console.error('Gary AI API: Connection test failed:', error);
            return false;
        }
    },
    
    /**
     * Get chat session ID
     */
    async getSessionId() {
        try {
            const response = await this.makeRequest({
                action: 'gary_ai_get_session',
                nonce: this.config.nonce
            });
            
            if (response.success) {
                return response.data.session_id;
            }
            
            // Fallback to client-side generation
            return this.generateSessionId();
            
        } catch (error) {
            console.error('Gary AI API: Session ID generation failed:', error);
            return this.generateSessionId();
        }
    },
    
    /**
     * Generate client-side session ID
     */
    generateSessionId() {
        return 'gary_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },
    
    /**
     * Make AJAX request to WordPress backend
     */
    async makeRequest(data) {
        const requestData = new FormData();
        
        Object.keys(data).forEach(key => {
            requestData.append(key, data[key]);
        });
        
        const response = await fetch(this.config.endpoint, {
            method: 'POST',
            body: requestData,
            credentials: 'same-origin',
            signal: AbortSignal.timeout(this.config.timeout)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result === null || result === 0) {
            throw new Error('Invalid response from server');
        }
        
        return result;
    },
    
    /**
     * Record analytics event
     */
    async recordEvent(eventType, data = {}) {
        try {
            await this.makeRequest({
                action: 'gary_ai_analytics',
                event_type: eventType,
                event_data: JSON.stringify(data),
                nonce: this.config.nonce
            });
        } catch (error) {
            console.error('Gary AI API: Analytics recording failed:', error);
            // Don't throw - analytics failure shouldn't break chat
        }
    }
};

/**
 * Rate Limiter Class
 */
class RateLimiter {
    constructor(config) {
        this.config = config;
        this.requests = [];
        this.isLocked = false;
        this.lockExpiry = 0;
    }
    
    canMakeRequest() {
        const now = Date.now();
        
        // Check if still locked out
        if (this.isLocked && now < this.lockExpiry) {
            return false;
        } else if (this.isLocked && now >= this.lockExpiry) {
            this.isLocked = false;
            this.requests = [];
        }
        
        // Clean old requests
        this.requests = this.requests.filter(timestamp => 
            now - timestamp < this.config.timeWindow
        );
        
        // Check if limit exceeded
        if (this.requests.length >= this.config.maxRequests) {
            this.isLocked = true;
            this.lockExpiry = now + this.config.lockoutDuration;
            return false;
        }
        
        return true;
    }
    
    recordRequest() {
        this.requests.push(Date.now());
    }
    
    getRemainingRequests() {
        const now = Date.now();
        this.requests = this.requests.filter(timestamp => 
            now - timestamp < this.config.timeWindow
        );
        return Math.max(0, this.config.maxRequests - this.requests.length);
    }
    
    getTimeUntilReset() {
        if (!this.isLocked) return 0;
        return Math.max(0, this.lockExpiry - Date.now());
    }
} 