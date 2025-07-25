/**
 * Gary AI Chat Widget - Storage Module
 * Handles conversation persistence and state management
 */

export const Storage = {
    
    /**
     * Initialize storage module
     */
    init(config = {}) {
        this.config = { ...this.defaultConfig, ...config };
        this.sessionId = this.config.sessionId;
        console.log('Gary AI Storage: Module initialized for session', this.sessionId);
        return this;
    },
    
    defaultConfig: {
        sessionId: null,
        maxMessages: 100,
        maxAge: 7 * 24 * 60 * 60 * 1000, // 7 days
        storagePrefix: 'gary_ai_'
    },
    
    /**
     * Save conversation message
     */
    saveMessage(text, type, metadata = {}) {
        try {
            const messages = this.getConversation();
            const message = {
                id: this.generateMessageId(),
                text: text,
                type: type,
                timestamp: Date.now(),
                session_id: this.sessionId,
                metadata: metadata
            };
            
            messages.push(message);
            
            // Trim to max messages
            if (messages.length > this.config.maxMessages) {
                messages.splice(0, messages.length - this.config.maxMessages);
            }
            
            this.setItem(`conversation_${this.sessionId}`, messages);
            return message.id;
            
        } catch (error) {
            console.error('Gary AI Storage: Failed to save message:', error);
            return null;
        }
    },
    
    /**
     * Get conversation history
     */
    getConversation() {
        try {
            const messages = this.getItem(`conversation_${this.sessionId}`) || [];
            
            // Filter out old messages
            const cutoff = Date.now() - this.config.maxAge;
            return messages.filter(msg => msg.timestamp > cutoff);
            
        } catch (error) {
            console.error('Gary AI Storage: Failed to load conversation:', error);
            return [];
        }
    },
    
    /**
     * Clear conversation history
     */
    clearConversation() {
        try {
            this.removeItem(`conversation_${this.sessionId}`);
            console.log('Gary AI Storage: Conversation cleared');
            return true;
        } catch (error) {
            console.error('Gary AI Storage: Failed to clear conversation:', error);
            return false;
        }
    },
    
    /**
     * Save widget preferences
     */
    savePreferences(preferences) {
        try {
            const current = this.getPreferences();
            const updated = { ...current, ...preferences, updated: Date.now() };
            this.setItem('preferences', updated);
            console.log('Gary AI Storage: Preferences saved');
            return true;
        } catch (error) {
            console.error('Gary AI Storage: Failed to save preferences:', error);
            return false;
        }
    },
    
    /**
     * Get widget preferences
     */
    getPreferences() {
        try {
            return this.getItem('preferences') || {
                theme: 'default',
                position: 'bottom-right',
                minimized: false,
                soundEnabled: true,
                notificationsEnabled: true
            };
        } catch (error) {
            console.error('Gary AI Storage: Failed to load preferences:', error);
            return {};
        }
    },
    
    /**
     * Save widget state
     */
    saveState(state) {
        try {
            const stateData = {
                ...state,
                sessionId: this.sessionId,
                timestamp: Date.now()
            };
            this.setItem('widget_state', stateData);
            return true;
        } catch (error) {
            console.error('Gary AI Storage: Failed to save state:', error);
            return false;
        }
    },
    
    /**
     * Get widget state
     */
    getState() {
        try {
            const state = this.getItem('widget_state');
            if (!state) return null;
            
            // Check if state is too old (older than session)
            const maxAge = 24 * 60 * 60 * 1000; // 24 hours
            if (Date.now() - state.timestamp > maxAge) {
                this.removeItem('widget_state');
                return null;
            }
            
            return state;
        } catch (error) {
            console.error('Gary AI Storage: Failed to load state:', error);
            return null;
        }
    },
    
    /**
     * Get conversation statistics
     */
    getStats() {
        try {
            const messages = this.getConversation();
            const userMessages = messages.filter(msg => msg.type === 'user');
            const botMessages = messages.filter(msg => msg.type === 'bot');
            
            return {
                totalMessages: messages.length,
                userMessages: userMessages.length,
                botMessages: botMessages.length,
                sessionStart: messages.length > 0 ? messages[0].timestamp : null,
                lastActivity: messages.length > 0 ? messages[messages.length - 1].timestamp : null,
                sessionDuration: messages.length > 0 ? 
                    messages[messages.length - 1].timestamp - messages[0].timestamp : 0
            };
        } catch (error) {
            console.error('Gary AI Storage: Failed to get stats:', error);
            return null;
        }
    },
    
    /**
     * Clean up old data
     */
    cleanup() {
        try {
            const keys = this.getAllKeys();
            const cutoff = Date.now() - this.config.maxAge;
            let cleaned = 0;
            
            keys.forEach(key => {
                if (key.startsWith(`${this.config.storagePrefix}conversation_`)) {
                    const messages = this.getItem(key.replace(this.config.storagePrefix, ''));
                    if (messages && messages.length > 0) {
                        const lastMessage = messages[messages.length - 1];
                        if (lastMessage.timestamp < cutoff) {
                            this.removeItem(key.replace(this.config.storagePrefix, ''));
                            cleaned++;
                        }
                    }
                }
            });
            
            console.log(`Gary AI Storage: Cleaned up ${cleaned} old conversations`);
            return cleaned;
        } catch (error) {
            console.error('Gary AI Storage: Cleanup failed:', error);
            return 0;
        }
    },
    
    /**
     * Generate unique message ID
     */
    generateMessageId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },
    
    /**
     * Low-level storage operations
     */
    setItem(key, value) {
        const fullKey = this.config.storagePrefix + key;
        localStorage.setItem(fullKey, JSON.stringify(value));
    },
    
    getItem(key) {
        const fullKey = this.config.storagePrefix + key;
        const item = localStorage.getItem(fullKey);
        return item ? JSON.parse(item) : null;
    },
    
    removeItem(key) {
        const fullKey = this.config.storagePrefix + key;
        localStorage.removeItem(fullKey);
    },
    
    getAllKeys() {
        const keys = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith(this.config.storagePrefix)) {
                keys.push(key);
            }
        }
        return keys;
    },
    
    /**
     * Check if localStorage is available
     */
    isAvailable() {
        try {
            const test = '__gary_ai_test__';
            localStorage.setItem(test, 'test');
            localStorage.removeItem(test);
            return true;
        } catch (error) {
            return false;
        }
    },
    
    /**
     * Get storage usage info
     */
    getUsageInfo() {
        try {
            const keys = this.getAllKeys();
            let totalSize = 0;
            
            keys.forEach(key => {
                const item = localStorage.getItem(key);
                totalSize += key.length + (item ? item.length : 0);
            });
            
            return {
                keys: keys.length,
                sizeBytes: totalSize,
                sizeKB: Math.round(totalSize / 1024 * 100) / 100,
                available: this.isAvailable()
            };
        } catch (error) {
            console.error('Gary AI Storage: Failed to get usage info:', error);
            return null;
        }
    }
}; 