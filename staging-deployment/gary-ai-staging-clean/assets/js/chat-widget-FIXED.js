/**
 * Gary AI Chat Widget JavaScript - FIXED VERSION
 * 
 * This version fixes the "Undefined constant 'garyAI'" error by:
 * 1. Adding proper fallback checks for undefined garyAI object
 * 2. Better error handling and validation
 * 3. Safe DOM manipulation with element checks
 */

(function($) {
    'use strict';

    // Verify jQuery is available
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        console.error('Gary AI: jQuery is not loaded. Widget cannot initialize.');
        return;
    }

    // Wait for DOM ready
    $(document).ready(function() {
        console.log('Gary AI: DOM ready, initializing widget');
        
        // Check if configuration object is available
        if (typeof window.garyAI === 'undefined') {
            console.error('Gary AI: Configuration object not found. Plugin may not be properly loaded.');
            return;
        }
        
        console.log('Gary AI: Configuration loaded successfully');
        
        // Check if container already exists
        if (document.querySelector('#gary-ai-widget-container')) {
            console.log('Gary AI: Widget container found, initializing');
            initGaryAIChatWidget();
        } else {
            console.log('Gary AI: Widget container not found, setting up observer');
            waitForElement('#gary-ai-widget-container', initGaryAIChatWidget);
        }
    });

    /**
     * Wait for element to appear in DOM
     */
    function waitForElement(selector, callback) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const element = document.querySelector(selector);
                    if (element) {
                        observer.disconnect();
                        callback();
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Fallback timeout
        setTimeout(function() {
            const element = document.querySelector(selector);
            if (element) {
                observer.disconnect();
                callback();
            }
        }, 5000);
    }

    /**
     * Initialize Gary AI Chat Widget
     */
    function initGaryAIChatWidget() {
        console.log('Gary AI: Initializing chat widget');

        // Verify configuration object exists
        if (typeof window.garyAI === 'undefined') {
            console.error('Gary AI: Configuration object not available');
            return;
        }

        const config = window.garyAI;
        
        // Verify required configuration properties
        if (!config.ajaxUrl || !config.nonce) {
            console.error('Gary AI: Missing required configuration properties');
            return;
        }

        const container = document.getElementById('gary-ai-widget-container');
        if (!container) {
            console.error('Gary AI: Widget container not found');
            return;
        }

        // Create widget HTML
        const widgetHTML = `
            <div id="gary-ai-chat-widget" class="gary-ai-widget-closed">
                <div class="gary-ai-toggle" id="gary-ai-toggle">
                    <div class="gary-ai-orb">
                        <div class="gary-ai-orb-inner"></div>
                    </div>
                </div>
                <div class="gary-ai-chat-container" id="gary-ai-chat-container">
                    <div class="gary-ai-chat-header">
                        <h3>${config.strings?.agent_name || 'Gary AI'}</h3>
                        <button class="gary-ai-close" id="gary-ai-close">&times;</button>
                    </div>
                    <div class="gary-ai-chat-messages" id="gary-ai-chat-messages">
                        <div class="gary-ai-message gary-ai-bot-message">
                            <div class="gary-ai-message-content">
                                ${config.strings?.welcome_message || 'Hello! How can I help you today?'}
                            </div>
                        </div>
                    </div>
                    <div class="gary-ai-chat-input-container">
                        <input type="text" id="gary-ai-chat-input" placeholder="${config.strings?.placeholder || 'Type your message...'}" />
                        <button id="gary-ai-send-button">${config.strings?.send || 'Send'}</button>
                    </div>
                </div>
            </div>
        `;

        // Insert widget HTML
        container.innerHTML = widgetHTML;

        // Initialize event listeners
        initializeEventListeners(config);

        console.log('Gary AI: Widget initialized successfully');
    }

    /**
     * Initialize event listeners
     */
    function initializeEventListeners(config) {
        const toggle = document.getElementById('gary-ai-toggle');
        const closeBtn = document.getElementById('gary-ai-close');
        const sendBtn = document.getElementById('gary-ai-send-button');
        const input = document.getElementById('gary-ai-chat-input');
        const widget = document.getElementById('gary-ai-chat-widget');

        if (!toggle || !closeBtn || !sendBtn || !input || !widget) {
            console.error('Gary AI: Required elements not found');
            return;
        }

        // Toggle widget
        toggle.addEventListener('click', function() {
            widget.classList.toggle('gary-ai-widget-closed');
            widget.classList.toggle('gary-ai-widget-open');
        });

        // Close widget
        closeBtn.addEventListener('click', function() {
            widget.classList.add('gary-ai-widget-closed');
            widget.classList.remove('gary-ai-widget-open');
        });

        // Send message
        sendBtn.addEventListener('click', function() {
            sendMessage(config);
        });

        // Enter key to send
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage(config);
            }
        });
    }

    /**
     * Send message
     */
    function sendMessage(config) {
        const input = document.getElementById('gary-ai-chat-input');
        const messagesContainer = document.getElementById('gary-ai-chat-messages');
        
        if (!input || !messagesContainer) {
            console.error('Gary AI: Required elements not found for sending message');
            return;
        }

        const message = input.value.trim();
        if (!message) {
            return;
        }

        // Add user message to chat
        addMessage(message, 'user', messagesContainer);
        input.value = '';

        // Add thinking indicator
        const thinkingMsg = addMessage(config.strings?.thinking || 'Thinking...', 'bot', messagesContainer);

        // Send AJAX request
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gary_ai_chat',
                message: message,
                nonce: config.nonce
            },
            success: function(response) {
                // Remove thinking indicator
                if (thinkingMsg) {
                    thinkingMsg.remove();
                }

                if (response.success && response.data && response.data.message) {
                    addMessage(response.data.message, 'bot', messagesContainer);
                } else {
                    addMessage(config.strings?.error || 'Sorry, I encountered an error. Please try again.', 'bot', messagesContainer);
                }
            },
            error: function() {
                // Remove thinking indicator
                if (thinkingMsg) {
                    thinkingMsg.remove();
                }
                addMessage(config.strings?.error || 'Sorry, I encountered an error. Please try again.', 'bot', messagesContainer);
            }
        });
    }

    /**
     * Add message to chat
     */
    function addMessage(message, type, container) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `gary-ai-message gary-ai-${type}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'gary-ai-message-content';
        contentDiv.textContent = message;
        
        messageDiv.appendChild(contentDiv);
        container.appendChild(messageDiv);
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
        
        return messageDiv;
    }

})(jQuery);
