/**
 * Gary AI Chat Widget JavaScript
 * 
 * ⚠️  IMPORTANT: When making changes to this file, update CHANGELOG.md
 * Document all modifications under the [Unreleased] section following semantic versioning
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
        console.log('Gary AI: DOM ready, waiting for widget container');
        console.log('Gary AI: garyAI object available:', typeof window.garyAI !== 'undefined');
        console.log('Gary AI: jQuery version:', $.fn.jquery);
        
        // Check if container already exists
        if (document.querySelector('#gary-ai-widget-container')) {
            console.log('Gary AI: Widget container found immediately, initializing');
            initGaryAIChatWidget();
        } else {
            console.log('Gary AI: Widget container not found, setting up observer');
            waitForElement('#gary-ai-widget-container', initGaryAIChatWidget);
        }
    });

    function waitForElement(selector, callback) {
        console.log('Gary AI: Setting up mutation observer for:', selector);
        const observer = new MutationObserver(() => {
            if (document.querySelector(selector)) {
                console.log('Gary AI: Element found by observer:', selector);
                callback();
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Add timeout fallback
        setTimeout(() => {
            if (document.querySelector(selector)) {
                console.log('Gary AI: Element found by timeout fallback:', selector);
                callback();
                observer.disconnect();
            } else {
                console.error('Gary AI: Element not found after 5 seconds:', selector);
            }
        }, 5000);
    }

    function initGaryAIChatWidget() {
        console.log('Gary AI: initGaryAIChatWidget called');
        try {
            // Check if widget container exists
            const container = $('#gary-ai-widget-container');
            console.log('Gary AI: Container jQuery object length:', container.length);
            console.log('Gary AI: Container DOM element:', container[0]);
            
            if (!container.length) {
                console.error('Gary AI: Widget container not found in jQuery');
                return;
            }

        // Widget state with localStorage persistence
        let isOpen = false;
        let isMinimized = false;
        let sessionId = getOrCreateSessionId();

        // Rate limiting state
        const rateLimitConfig = {
            maxRequests: 10,        // Max requests per time window
            timeWindow: 60000,      // Time window in milliseconds (1 minute)
            lockoutDuration: 300000 // Lockout duration in milliseconds (5 minutes)
        };
        let requestHistory = [];
        let isRateLimited = false;
        let isOnline = navigator.onLine;

        // Load saved widget state
        function loadWidgetState() {
            try {
                if (typeof localStorage !== 'undefined') {
                    const savedState = localStorage.getItem('gary_ai_widget_state');
                    if (savedState) {
                        const state = JSON.parse(savedState);
                        isOpen = state.isOpen || false;
                        isMinimized = state.isMinimized || false;
                        // Restore visual state if widget was open
                        if (isOpen && !isMinimized) {
                            widget.show();
                            toggle.hide();
                        } else if (isMinimized) {
                            widget.hide();
                            toggle.show();
                        }
                    }
                }
            } catch (error) {
                console.error('Gary AI: Error loading widget state:', error);
            }
        }

        // Load conversation history
        function loadConversationHistory() {
            try {
                if (typeof localStorage !== 'undefined') {
                    const conversationKey = 'gary_ai_conversation_' + sessionId;
                    const savedConversation = localStorage.getItem(conversationKey);
                    
                    if (savedConversation) {
                        const messages = JSON.parse(savedConversation);
                        messagesContainer.empty();
                        
                        messages.forEach(function(msg) {
                            addMessage(msg.text, msg.type, false); // false = don't save again
                        });
                        
                        console.log('Gary AI: Loaded conversation history with', messages.length, 'messages');
                    } else {
                        // Add welcome message for new conversations
                        addMessage('Hello! How can I help you today?', 'bot', false);
                    }
                }
            } catch (error) {
                console.error('Gary AI: Error loading conversation history:', error);
                // Fallback welcome message
                addMessage('Hello! How can I help you today?', 'bot', false);
            }
        }

        // Save conversation history
        function saveConversationHistory() {
            try {
                if (typeof localStorage !== 'undefined') {
                    const conversationKey = 'gary_ai_conversation_' + sessionId;
                    const messages = [];
                    
                    messagesContainer.children('.gary-ai-message').each(function() {
                        const $msg = $(this);
                        messages.push({
                            text: $msg.text(),
                            type: $msg.hasClass('bot') ? 'bot' : 'user',
                            timestamp: Date.now(),
                            locale: navigator.language || 'en-US'
                        });
                    });
                    
                    // Keep only last 50 messages to prevent storage bloat
                    if (messages.length > 50) {
                        messages = messages.slice(-50);
                    }
                    
                    localStorage.setItem(conversationKey, JSON.stringify(messages));
                }
            } catch (error) {
                console.error('Gary AI: Error saving conversation history:', error);
            }
        }

        // Save widget state
        function saveWidgetState() {
            try {
                if (typeof localStorage !== 'undefined') {
                    const state = {
                        isOpen: isOpen,
                        isMinimized: isMinimized,
                        timestamp: Date.now()
                    };
                    localStorage.setItem('gary_ai_widget_state', JSON.stringify(state));
                }
            } catch (error) {
                console.error('Gary AI: Error saving widget state:', error);
            }
        }

        // Cache DOM elements
        const toggle = $('.gary-ai-toggle');
        const widget = $('#gary-ai-widget');
        const closeBtn = $('.gary-ai-close');
        const minimizeBtn = $('.gary-ai-minimize');
        const exportBtn = $('.gary-ai-export');
        const input = $('#gary-ai-input');
        const sendBtn = $('#gary-ai-send');
        const messagesContainer = $('#gary-ai-messages');

        // Toggle widget
        toggle.on('click', function() {
            if (!isOpen) {
                openWidget();
            } else if (isMinimized) {
                maximizeWidget();
            }
        });

        // Close widget
        closeBtn.on('click', function() {
            closeWidget();
        });

        // Minimize widget
        minimizeBtn.on('click', function() {
            minimizeWidget();
        });

        // Export conversation
        exportBtn.on('click', function() {
            exportConversation();
        });

        // Send message on button click
        sendBtn.on('click', function() {
            sendMessage();
        });

        // Send message on Enter key
        input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Keyboard navigation support
        $(document).on('keydown', function(e) {
            // ESC key to close widget
            if (e.key === 'Escape' && isOpen && !isMinimized) {
                closeWidget();
                e.preventDefault();
            }
            
            // Ctrl+M to toggle widget (accessibility shortcut)
            if (e.ctrlKey && e.key === 'm') {
                if (isOpen && !isMinimized) {
                    minimizeWidget();
                } else if (isMinimized) {
                    maximizeWidget();
                } else {
                    openWidget();
                }
                e.preventDefault();
            }
        });

        // Enhance focus management for buttons
        toggle.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                $(this).click();
                e.preventDefault();
            }
        });

        closeBtn.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                $(this).click();
                e.preventDefault();
            }
        });

        minimizeBtn.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                $(this).click();
                e.preventDefault();
            }
        });

        // Open widget
        function openWidget() {
            widget.fadeIn(300);
            toggle.fadeOut(300);
            isOpen = true;
            isMinimized = false;
            saveWidgetState();
            input.focus();
            
            // Load conversation history if first time opening
            if (messagesContainer.children().length === 0) {
                loadConversationHistory();
            }
        }

        // Close widget
        function closeWidget() {
            widget.fadeOut(300);
            toggle.fadeIn(300);
            isOpen = false;
            isMinimized = false;
            saveWidgetState();
        }

        // Minimize widget
        function minimizeWidget() {
            widget.fadeOut(300);
            toggle.fadeIn(300);
            isMinimized = true;
            saveWidgetState();
        }

        // Maximize widget
        function maximizeWidget() {
            widget.fadeIn(300);
            toggle.fadeOut(300);
            isMinimized = false;
            saveWidgetState();
            input.focus();
        }

        // Export conversation to text file
        function exportConversation() {
            try {
                const messages = [];
                const agentName = garyAI.strings.agent_name || 'Gary AI';
                const timestamp = new Date().toLocaleString();
                
                messagesContainer.children('.gary-ai-message').each(function() {
                    const $msg = $(this);
                    const isBot = $msg.hasClass('bot');
                    const sender = isBot ? agentName : 'User';
                    const content = $msg.text().trim();
                    
                    if (content && !$msg.hasClass('gary-ai-typing')) {
                        messages.push(`${sender}: ${content}`);
                    }
                });
                
                if (messages.length === 0) {
                    addMessage('No conversation to export.', 'bot');
                    return;
                }
                
                // Use locale-aware formatting
                const locale = navigator.language || 'en-US';
                const formattedDate = new Date().toLocaleDateString(locale, {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                const exportContent = [
                    `Chat Conversation Export`,
                    `Generated: ${formattedDate}`,
                    `Session: ${sessionId}`,
                    `Locale: ${locale}`,
                    ``,
                    ...messages
                ].join('\n');
                
                // Create and download file
                const blob = new Blob([exportContent], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `gary-ai-chat-${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                console.log('Gary AI: Conversation exported successfully');
                
            } catch (error) {
                console.error('Gary AI: Error exporting conversation:', error);
                addMessage('Error exporting conversation. Please try again.', 'bot');
            }
        }

        // Send message
        function sendMessage() {
            try {
                const message = input.val().trim();
                
                if (!message) {
                    return;
                }
                
                // Validate message length and content
                if (message.length > 500) {
                    addMessage('Error: Message too long (max 500 characters)', 'bot');
                    return;
                }
                
                // Check if online
                if (!isOnline) {
                    addMessage(garyAI.strings.offline || 'You appear to be offline. Please check your internet connection.', 'bot');
                    return;
                }
                
                // Check rate limiting
                if (!checkRateLimit()) {
                    addMessage('Error: Too many requests. Please wait a few minutes before trying again.', 'bot');
                    return;
                }
                
                // Record this request
                recordRequest();

            // Disable input while processing
            input.prop('disabled', true);
            sendBtn.prop('disabled', true);

            // Add user message
            addMessage(message, 'user');
            
            // Add typing indicator
            const typingId = addTypingIndicator();
            
            // Clear input
            input.val('');

            // Remove old thinking indicator (now using typing indicator above)

            // Send AJAX request
            $.ajax({
                url: garyAI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gary_ai_chat',
                    nonce: garyAI.nonce,
                    message: message,
                    session_id: sessionId
                },
                success: function(response) {
                    // Remove typing indicator
                    removeTypingIndicator(typingId);
                    
                    if (response.success && response.data.message) {
                        addMessage(response.data.message, 'bot');
                    } else {
                        addMessage(garyAI.strings.error, 'bot');
                    }
                },
                error: function() {
                    // Remove typing indicator
                    removeTypingIndicator(typingId);
                    
                    addMessage(garyAI.strings.error, 'bot');
                },
                complete: function() {
                    // Re-enable input
                    input.prop('disabled', false);
                    sendBtn.prop('disabled', false);
                    input.focus();
                }
            });
            } catch (error) {
                console.error('Gary AI: Error in sendMessage:', error);
                // Re-enable input on error
                input.prop('disabled', false);
                sendBtn.prop('disabled', false);
                addMessage('Error: Unable to send message. Please try again.', 'bot');
            }
        }

        // Add message to chat
        function addMessage(text, type, saveToHistory = true) {
            const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const sanitizedText = sanitizeMessage(text);
            const messageHtml = `<div id="${messageId}" class="gary-ai-message ${type}">${sanitizedText}</div>`;
            
            messagesContainer.append(messageHtml);
            
            // Scroll to bottom
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            
            // Save conversation history unless explicitly disabled
            if (saveToHistory) {
                saveConversationHistory();
            }
            
            return messageId;
        }

        // Add typing indicator with animation
        function addTypingIndicator() {
            const typingId = 'typing-' + Date.now();
            const typingHtml = `
                <div id="${typingId}" class="gary-ai-message bot gary-ai-typing">
                    <div class="gary-ai-typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span class="gary-ai-typing-text">${garyAI.strings.thinking}</span>
                </div>`;
            
            messagesContainer.append(typingHtml);
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            
            return typingId;
        }

        // Remove typing indicator
        function removeTypingIndicator(typingId) {
            if (typingId) {
                $('#' + typingId).fadeOut(200, function() {
                    $(this).remove();
                });
            }
        }

        // Generate session ID with fallbacks for older browsers
        function generateSessionId() {
            try {
                // Modern browsers
                if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
                    var array = new Uint32Array(1);
                    crypto.getRandomValues(array);
                    return 'session-' + Date.now() + '-' + array[0].toString(36);
                }
                // Fallback for older browsers
                return 'session-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            } catch (error) {
                // Ultimate fallback
                return 'session-' + Date.now() + '-' + (Math.random() * 1000000).toString(36);
            }
        }

        // Get or create persistent session ID
        function getOrCreateSessionId() {
            try {
                if (typeof localStorage !== 'undefined') {
                    let savedSessionId = localStorage.getItem('gary_ai_session_id');
                    let sessionTimestamp = localStorage.getItem('gary_ai_session_timestamp');
                    
                    // Check if session is still valid (24 hours)
                    const sessionMaxAge = 24 * 60 * 60 * 1000; // 24 hours
                    const now = Date.now();
                    
                    if (savedSessionId && sessionTimestamp) {
                        const age = now - parseInt(sessionTimestamp);
                        if (age < sessionMaxAge) {
                            console.log('Gary AI: Using existing session:', savedSessionId);
                            return savedSessionId;
                        }
                    }
                    
                    // Create new session
                    const newSessionId = generateSessionId();
                    localStorage.setItem('gary_ai_session_id', newSessionId);
                    localStorage.setItem('gary_ai_session_timestamp', now.toString());
                    console.log('Gary AI: Created new session:', newSessionId);
                    return newSessionId;
                }
                
                // Fallback if localStorage not available
                return generateSessionId();
            } catch (error) {
                console.error('Gary AI: Error managing session ID:', error);
                return generateSessionId();
            }
        }

        // Enhanced HTML escaping for XSS protection
        function escapeHtml(text) {
            if (typeof text !== 'string') {
                text = String(text);
            }
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#x27;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            };
            
            return text.replace(/[&<>"'`=\/]/g, function(m) {
                return map[m];
            });
        }

        // Sanitize message content with additional security
        function sanitizeMessage(text) {
            // Escape HTML first
            text = escapeHtml(text);
            
            // Remove any potentially dangerous patterns
            text = text.replace(/javascript:/gi, '');
            text = text.replace(/on\w+\s*=/gi, '');
            text = text.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            text = text.replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '');
            
            // Limit message length to prevent DoS
            if (text.length > 2000) {
                text = text.substring(0, 2000) + '... (message truncated)';
            }
            
            return text;
        }

        // Rate limiting functions
        function checkRateLimit() {
            const now = Date.now();
            
            // Clean old requests outside time window
            requestHistory = requestHistory.filter(timestamp => 
                now - timestamp < rateLimitConfig.timeWindow
            );
            
            // Check if currently rate limited
            if (isRateLimited) {
                const lockoutKey = 'gary_ai_lockout_' + sessionId;
                try {
                    const lockoutTime = localStorage.getItem(lockoutKey);
                    if (lockoutTime && now - parseInt(lockoutTime) < rateLimitConfig.lockoutDuration) {
                        return false; // Still locked out
                    } else {
                        // Lockout expired
                        isRateLimited = false;
                        localStorage.removeItem(lockoutKey);
                    }
                } catch (error) {
                    console.error('Gary AI: Error checking lockout:', error);
                }
            }
            
            // Check current request count
            if (requestHistory.length >= rateLimitConfig.maxRequests) {
                isRateLimited = true;
                try {
                    const lockoutKey = 'gary_ai_lockout_' + sessionId;
                    localStorage.setItem(lockoutKey, now.toString());
                } catch (error) {
                    console.error('Gary AI: Error setting lockout:', error);
                }
                return false;
            }
            
            return true;
        }
        
        function recordRequest() {
            requestHistory.push(Date.now());
        }

        // Cleanup function for event listeners
        function cleanup() {
            try {
                toggle.off('click');
                closeBtn.off('click');
                minimizeBtn.off('click');
                sendBtn.off('click');
                input.off('keypress');
                console.log('Gary AI: Event listeners cleaned up');
            } catch (error) {
                console.error('Gary AI: Error during cleanup:', error);
            }
        }

        // Expose widget API
        window.garyAIChatWidget = {
            open: openWidget,
            close: closeWidget,
            toggle: function() {
                if (isOpen) {
                    closeWidget();
                } else {
                    openWidget();
                }
            },
            sendMessage: function(message) {
                input.val(message);
                sendMessage();
            },
            isOpen: function() {
                return isOpen;
            },
            cleanup: cleanup
        };

        // Cleanup on page unload
        $(window).on('beforeunload', cleanup);

        // Load saved state after initialization
        loadWidgetState();

        // Online/offline status monitoring
        $(window).on('online', function() {
            isOnline = true;
            console.log('Gary AI: Internet connection restored');
            // Update status indicator if visible
            if (isOpen) {
                $('.gary-ai-status').text('Online').removeClass('offline');
            }
        });

        $(window).on('offline', function() {
            isOnline = false;
            console.log('Gary AI: Internet connection lost');
            // Update status indicator if visible
            if (isOpen) {
                $('.gary-ai-status').text('Offline').addClass('offline');
            }
            // Show offline message if user tries to chat
            if (isOpen && !isMinimized) {
                addMessage(garyAI.strings.offline || 'You appear to be offline. Please check your internet connection.', 'bot');
            }
        });

        } catch (error) {
            console.error('Gary AI: Error initializing chat widget:', error);
        }
    }

})(jQuery); 