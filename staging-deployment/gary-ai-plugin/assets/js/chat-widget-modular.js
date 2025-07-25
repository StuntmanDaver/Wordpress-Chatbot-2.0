/**
 * Gary AI Chat Widget - Modular Version
 * 
 * ⚠️  IMPORTANT: This is the new modular implementation
 * Main widget coordinator that imports and manages UI, API, and Storage modules
 */

import { UI } from './modules/ui.js';
import { API } from './modules/api.js';
import { Storage } from './modules/storage.js';

class GaryAIChatWidget {
    
    constructor(container, config = {}) {
        this.container = container;
        this.config = { ...this.defaultConfig, ...config };
        this.sessionId = null;
        this.isInitialized = false;
        
        console.log('Gary AI Widget: Initializing modular chat widget');
    }
    
    defaultConfig = {
        position: 'bottom-right',
        theme: 'default',
        autoOpen: false,
        enableAnalytics: true,
        enableStorage: true,
        maxMessageLength: 2000,
        rateLimitConfig: {
            maxRequests: 10,
            timeWindow: 60000,
            lockoutDuration: 300000
        }
    };
    
    /**
     * Initialize the widget and all modules
     */
    async init() {
        try {
            console.log('Gary AI Widget: Starting initialization');
            
            // Verify dependencies
            if (!this.checkDependencies()) {
                throw new Error('Required dependencies not available');
            }
            
            // Generate or get session ID
            this.sessionId = await this.initializeSession();
            console.log('Gary AI Widget: Session ID:', this.sessionId);
            
            // Initialize modules
            await this.initializeModules();
            
            // Set up module communication
            this.setupModuleCallbacks();
            
            // Load initial state and conversation
            await this.loadInitialState();
            
            this.isInitialized = true;
            console.log('Gary AI Widget: Initialization complete');
            
            // Analytics: Record widget initialization
            if (this.config.enableAnalytics) {
                this.api.recordEvent('widget_initialized', {
                    sessionId: this.sessionId,
                    config: this.config
                });
            }
            
        } catch (error) {
            console.error('Gary AI Widget: Initialization failed:', error);
            this.handleInitializationError(error);
        }
    }
    
    /**
     * Check for required dependencies
     */
    checkDependencies() {
        if (typeof window.garyAI === 'undefined') {
            console.error('Gary AI Widget: garyAI configuration object not found');
            return false;
        }
        
        if (!window.garyAI.ajaxUrl || !window.garyAI.nonce) {
            console.error('Gary AI Widget: Required configuration missing');
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize session
     */
    async initializeSession() {
        try {
            return await this.api.getSessionId();
        } catch (error) {
            console.error('Gary AI Widget: Session initialization failed:', error);
            // Fallback to client-side generation
            return 'gary_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
    }
    
    /**
     * Initialize all modules
     */
    async initializeModules() {
        // Initialize API module first
        this.api = API.init({
            endpoint: window.garyAI.ajaxUrl,
            nonce: window.garyAI.nonce,
            rateLimit: this.config.rateLimitConfig
        });
        
        // Initialize Storage module
        if (this.config.enableStorage) {
            this.storage = Storage.init({
                sessionId: this.sessionId,
                maxMessages: 100,
                maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
            });
        }
        
        // Initialize UI module last
        this.ui = UI.init(this.container, {
            position: this.config.position,
            theme: this.config.theme,
            autoOpen: this.config.autoOpen
        });
        
        console.log('Gary AI Widget: All modules initialized');
    }
    
    /**
     * Set up communication between modules
     */
    setupModuleCallbacks() {
        // UI callbacks
        this.ui.onSendMessage = (message) => this.handleSendMessage(message);
        this.ui.onWidgetOpen = () => this.handleWidgetOpen();
        this.ui.onWidgetClose = () => this.handleWidgetClose();
        this.ui.onMessageAdd = (text, type) => this.handleMessageAdd(text, type);
        
        console.log('Gary AI Widget: Module callbacks configured');
    }
    
    /**
     * Load initial state and conversation
     */
    async loadInitialState() {
        if (!this.config.enableStorage) return;
        
        try {
            // Load conversation history
            const conversation = this.storage.getConversation();
            
            if (conversation.length > 0) {
                console.log(`Gary AI Widget: Loading ${conversation.length} messages from history`);
                this.ui.clearMessages();
                
                conversation.forEach(msg => {
                    this.ui.addMessage(msg.text, msg.type, false); // Don't save again
                });
            } else {
                // Add welcome message for new conversations
                this.ui.addMessage('Hello! How can I help you today?', 'bot', false);
            }
            
            // Load widget preferences
            const preferences = this.storage.getPreferences();
            this.applyPreferences(preferences);
            
        } catch (error) {
            console.error('Gary AI Widget: Failed to load initial state:', error);
            // Add fallback welcome message
            this.ui.addMessage('Hello! How can I help you today?', 'bot', false);
        }
    }
    
    /**
     * Handle sending a message
     */
    async handleSendMessage(message) {
        if (!message || message.length > this.config.maxMessageLength) {
            this.ui.showStatus('Message too long', 'error');
            this.ui.setInputEnabled(true);
            this.ui.hideTyping();
            return;
        }
        
        try {
            console.log('Gary AI Widget: Sending message:', message.substring(0, 50) + '...');
            
            // Send to API
            const response = await this.api.sendMessage(message, this.sessionId);
            
            // Hide typing and re-enable input
            this.ui.hideTyping();
            this.ui.setInputEnabled(true);
            
            // Add bot response
            if (response && response.response) {
                this.ui.addMessage(response.response, 'bot');
                
                // Analytics: Record successful interaction
                if (this.config.enableAnalytics) {
                    this.api.recordEvent('message_sent', {
                        sessionId: this.sessionId,
                        messageLength: message.length,
                        responseLength: response.response.length
                    });
                }
            } else {
                throw new Error('Invalid response from server');
            }
            
        } catch (error) {
            console.error('Gary AI Widget: Message send failed:', error);
            
            // Hide typing and re-enable input
            this.ui.hideTyping();
            this.ui.setInputEnabled(true);
            
            // Show error message
            let errorMessage = 'Sorry, I encountered an error. Please try again.';
            if (error.message.includes('Rate limit')) {
                errorMessage = 'You\'re sending messages too quickly. Please wait a moment.';
            }
            
            this.ui.addMessage(errorMessage, 'bot');
            this.ui.showStatus(error.message, 'error');
            
            // Analytics: Record error
            if (this.config.enableAnalytics) {
                this.api.recordEvent('message_error', {
                    sessionId: this.sessionId,
                    error: error.message
                });
            }
        }
    }
    
    /**
     * Handle widget open
     */
    handleWidgetOpen() {
        console.log('Gary AI Widget: Widget opened');
        
        if (this.config.enableAnalytics) {
            this.api.recordEvent('widget_opened', {
                sessionId: this.sessionId
            });
        }
    }
    
    /**
     * Handle widget close
     */
    handleWidgetClose() {
        console.log('Gary AI Widget: Widget closed');
        
        if (this.config.enableAnalytics) {
            this.api.recordEvent('widget_closed', {
                sessionId: this.sessionId
            });
        }
    }
    
    /**
     * Handle message addition (for storage)
     */
    handleMessageAdd(text, type) {
        if (this.config.enableStorage) {
            this.storage.saveMessage(text, type, {
                timestamp: Date.now(),
                sessionId: this.sessionId
            });
        }
    }
    
    /**
     * Apply user preferences
     */
    applyPreferences(preferences) {
        if (preferences.theme && preferences.theme !== 'default') {
            this.ui.widget.classList.add(`theme-${preferences.theme}`);
        }
        
        if (preferences.position && preferences.position !== 'bottom-right') {
            this.ui.widget.classList.add(`position-${preferences.position}`);
        }
    }
    
    /**
     * Handle initialization errors
     */
    handleInitializationError(error) {
        console.error('Gary AI Widget: Critical error during initialization:', error);
        
        // Show minimal error UI
        if (this.container) {
            this.container.innerHTML = `
                <div class="gary-ai-widget error">
                    <div class="gary-ai-toggle disabled">
                        <span class="gary-ai-icon">⚠️</span>
                        <span class="gary-ai-text">Chat Unavailable</span>
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * Public API methods
     */
    
    /**
     * Open the widget programmatically
     */
    open() {
        if (this.isInitialized && this.ui) {
            this.ui.widget.classList.add('open');
            this.ui.input.focus();
        }
    }
    
    /**
     * Close the widget programmatically
     */
    close() {
        if (this.isInitialized && this.ui) {
            this.ui.closeWidget();
        }
    }
    
    /**
     * Send a message programmatically
     */
    sendMessage(message) {
        if (this.isInitialized && this.ui) {
            this.ui.input.value = message;
            this.ui.handleSendMessage();
        }
    }
    
    /**
     * Clear conversation history
     */
    clearHistory() {
        if (this.isInitialized) {
            if (this.ui) this.ui.clearMessages();
            if (this.storage) this.storage.clearConversation();
            
            // Add welcome message
            this.ui.addMessage('Hello! How can I help you today?', 'bot', false);
        }
    }
    
    /**
     * Get widget statistics
     */
    getStats() {
        if (this.isInitialized && this.storage) {
            return this.storage.getStats();
        }
        return null;
    }
    
    /**
     * Destroy the widget and clean up
     */
    destroy() {
        if (this.isInitialized) {
            // Clean up storage
            if (this.storage) {
                this.storage.cleanup();
            }
            
            // Clear container
            if (this.container) {
                this.container.innerHTML = '';
            }
            
            this.isInitialized = false;
            console.log('Gary AI Widget: Widget destroyed');
        }
    }
}

// Initialize widget when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Gary AI Widget: DOM ready, looking for widget container');
    
    const container = document.querySelector('#gary-ai-widget-container');
    if (container) {
        console.log('Gary AI Widget: Container found, initializing modular widget');
        
        const widget = new GaryAIChatWidget(container);
        widget.init();
        
        // Make widget globally accessible for debugging
        window.garyAIWidget = widget;
    } else {
        console.error('Gary AI Widget: Container element #gary-ai-widget-container not found');
    }
});

// Export for module systems
export default GaryAIChatWidget; 