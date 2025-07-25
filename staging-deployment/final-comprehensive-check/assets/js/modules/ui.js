/**
 * Gary AI Chat Widget - UI Module
 * Handles all user interface and DOM manipulation
 */

export const UI = {
    
    /**
     * Initialize the chat widget UI
     */
    init(container, options = {}) {
        console.log('Gary AI UI: Initializing widget interface');
        
        this.container = container;
        this.options = { ...this.defaultOptions, ...options };
        
        this.createWidgetStructure();
        this.bindUIEvents();
        this.loadWidgetState();
        
        return this;
    },
    
    defaultOptions: {
        position: 'bottom-right',
        theme: 'default',
        minimized: false,
        showWelcome: true
    },
    
    /**
     * Create the main widget DOM structure
     */
    createWidgetStructure() {
        const widgetHTML = `
            <div id="gary-ai-widget" class="gary-ai-widget">
                <div class="gary-ai-toggle">
                    <span class="gary-ai-icon">💬</span>
                    <span class="gary-ai-text">Chat</span>
                </div>
                <div class="gary-ai-chat-window">
                    <div class="gary-ai-header">
                        <span class="gary-ai-title">Gary AI Assistant</span>
                        <button class="gary-ai-close" aria-label="Close chat">×</button>
                    </div>
                    <div class="gary-ai-messages"></div>
                    <div class="gary-ai-input-area">
                        <input type="text" class="gary-ai-input" placeholder="Type your message..." maxlength="2000">
                        <button class="gary-ai-send" aria-label="Send message">
                            <span class="gary-ai-send-icon">→</span>
                        </button>
                    </div>
                    <div class="gary-ai-status"></div>
                </div>
            </div>
        `;
        
        this.container.innerHTML = widgetHTML;
        this.cacheElements();
    },
    
    /**
     * Cache DOM elements for performance
     */
    cacheElements() {
        this.widget = this.container.querySelector('#gary-ai-widget');
        this.toggle = this.container.querySelector('.gary-ai-toggle');
        this.chatWindow = this.container.querySelector('.gary-ai-chat-window');
        this.closeBtn = this.container.querySelector('.gary-ai-close');
        this.messagesContainer = this.container.querySelector('.gary-ai-messages');
        this.input = this.container.querySelector('.gary-ai-input');
        this.sendBtn = this.container.querySelector('.gary-ai-send');
        this.status = this.container.querySelector('.gary-ai-status');
    },
    
    /**
     * Bind UI event handlers
     */
    bindUIEvents() {
        this.toggle.addEventListener('click', () => this.toggleWidget());
        this.closeBtn.addEventListener('click', () => this.closeWidget());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.handleSendMessage();
        });
        this.sendBtn.addEventListener('click', () => this.handleSendMessage());
    },
    
    /**
     * Toggle widget open/closed state
     */
    toggleWidget() {
        this.widget.classList.toggle('open');
        this.saveWidgetState();
        
        if (this.widget.classList.contains('open')) {
            this.input.focus();
            this.onWidgetOpen?.();
        } else {
            this.onWidgetClose?.();
        }
    },
    
    /**
     * Close the widget
     */
    closeWidget() {
        this.widget.classList.remove('open');
        this.saveWidgetState();
        this.onWidgetClose?.();
    },
    
    /**
     * Add a message to the chat
     */
    addMessage(text, type = 'bot', save = true) {
        const messageEl = document.createElement('div');
        messageEl.className = `gary-ai-message ${type}`;
        messageEl.textContent = text;
        
        this.messagesContainer.appendChild(messageEl);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        
        if (save && this.onMessageAdd) {
            this.onMessageAdd(text, type);
        }
        
        return messageEl;
    },
    
    /**
     * Show typing indicator
     */
    showTyping() {
        const typingEl = document.createElement('div');
        typingEl.className = 'gary-ai-typing';
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        typingEl.id = 'gary-ai-typing-indicator';
        
        this.messagesContainer.appendChild(typingEl);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    },
    
    /**
     * Hide typing indicator
     */
    hideTyping() {
        const typingEl = this.messagesContainer.querySelector('#gary-ai-typing-indicator');
        if (typingEl) {
            typingEl.remove();
        }
    },
    
    /**
     * Show status message
     */
    showStatus(message, type = 'info') {
        this.status.className = `gary-ai-status ${type}`;
        this.status.textContent = message;
        
        setTimeout(() => {
            this.status.textContent = '';
            this.status.className = 'gary-ai-status';
        }, 3000);
    },
    
    /**
     * Get current input value
     */
    getInputValue() {
        return this.input.value.trim();
    },
    
    /**
     * Clear input field
     */
    clearInput() {
        this.input.value = '';
    },
    
    /**
     * Disable/enable input
     */
    setInputEnabled(enabled) {
        this.input.disabled = !enabled;
        this.sendBtn.disabled = !enabled;
    },
    
    /**
     * Handle send message UI logic
     */
    handleSendMessage() {
        const message = this.getInputValue();
        if (!message) return;
        
        this.addMessage(message, 'user');
        this.clearInput();
        this.setInputEnabled(false);
        this.showTyping();
        
        this.onSendMessage?.(message);
    },
    
    /**
     * Load widget state from storage
     */
    loadWidgetState() {
        try {
            const state = localStorage.getItem('gary_ai_widget_state');
            if (state) {
                const { isOpen } = JSON.parse(state);
                if (isOpen) {
                    this.widget.classList.add('open');
                }
            }
        } catch (error) {
            console.error('Gary AI UI: Error loading widget state:', error);
        }
    },
    
    /**
     * Save widget state to storage
     */
    saveWidgetState() {
        try {
            const state = {
                isOpen: this.widget.classList.contains('open'),
                timestamp: Date.now()
            };
            localStorage.setItem('gary_ai_widget_state', JSON.stringify(state));
        } catch (error) {
            console.error('Gary AI UI: Error saving widget state:', error);
        }
    },
    
    /**
     * Clear all messages
     */
    clearMessages() {
        this.messagesContainer.innerHTML = '';
    },
    
    /**
     * Event callbacks (set by main widget)
     */
    onWidgetOpen: null,
    onWidgetClose: null,
    onSendMessage: null,
    onMessageAdd: null
}; 