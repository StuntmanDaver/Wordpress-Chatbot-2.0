# 🤝 Contributing to Gary AI Plugin

Thank you for your interest in contributing to the Gary AI WordPress plugin! This document provides comprehensive guidelines for developers who want to contribute to the project.

---

## 📋 **Quick Start**

### **Prerequisites**
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Node.js**: 16.0+
- **Composer**: Latest version
- **Git**: Latest version

### **Development Setup**
```bash
# Clone the repository
git clone https://github.com/gary-ai/wordpress-plugin.git
cd wordpress-plugin/gary-ai

# Install PHP dependencies
composer install

# Install Node.js dependencies (in build directory)
cd build
npm install

# Set up Docker development environment
docker-compose up -d

# Access WordPress
# URL: http://localhost:8080
# Admin: admin/admin
```

---

## 🏗️ **Development Workflow**

### **1. Environment Setup**

#### **Docker Development Environment**
```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f wordpress

# Stop environment
docker-compose down

# Reset environment (clean start)
docker-compose down -v && docker-compose up -d
```

#### **Plugin Development**
```bash
# Watch for changes (auto-reload)
npm run dev

# Build production assets
npm run build

# Run tests
npm test
composer test

# Code quality checks
npm run validate
composer cs
```

### **2. Code Standards**

#### **PHP Standards (WordPress + PSR-12)**
```php
<?php
/**
 * Class documentation block
 * 
 * @package GaryAI
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example class following WordPress standards
 */
class GaryAIExample {
    
    /**
     * Method documentation
     * 
     * @param string $message The message to process
     * @return array|WP_Error Result array or error object
     * @since 1.0.0
     */
    public function processMessage($message) {
        // Input validation
        if (empty($message)) {
            return new WP_Error('empty_message', 'Message cannot be empty');
        }
        
        // Sanitize input
        $message = sanitize_text_field($message);
        
        // Process and return
        return [
            'processed_message' => $message,
            'timestamp' => current_time('mysql')
        ];
    }
}
```

#### **JavaScript Standards (ES6+)**
```javascript
/**
 * Gary AI Chat Widget
 * 
 * @since 1.0.0
 */
(function($) {
    'use strict';
    
    /**
     * Chat Widget Class
     */
    class GaryAIChatWidget {
        
        /**
         * Constructor
         * 
         * @param {Object} options Widget configuration options
         */
        constructor(options = {}) {
            this.options = $.extend({
                container: '#gary-ai-widget-container',
                apiEndpoint: garyAI.ajaxUrl,
                nonce: garyAI.nonce
            }, options);
            
            this.init();
        }
        
        /**
         * Initialize widget
         */
        init() {
            this.bindEvents();
            this.loadWidget();
        }
        
        /**
         * Bind event handlers
         */
        bindEvents() {
            $(document).on('click', '.gary-ai-send-button', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        new GaryAIChatWidget();
    });
    
})(jQuery);
```

#### **CSS Standards (BEM Methodology)**
```css
/* Gary AI Chat Widget Styles */

/* Block */
.gary-ai-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Element */
.gary-ai-widget__container {
    width: 350px;
    height: 500px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.gary-ai-widget__header {
    padding: 16px;
    background: #2196F3;
    color: #ffffff;
    border-radius: 8px 8px 0 0;
}

.gary-ai-widget__title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

/* Modifier */
.gary-ai-widget--minimized {
    height: auto;
}

.gary-ai-widget--dark {
    background: #1a1a1a;
    color: #ffffff;
}

/* Responsive design */
@media (max-width: 768px) {
    .gary-ai-widget {
        bottom: 10px;
        right: 10px;
        left: 10px;
        width: auto;
    }
    
    .gary-ai-widget__container {
        width: 100%;
    }
}
```

### **3. Security Guidelines**

#### **Input Validation**
```php
// Always validate and sanitize inputs
$message = sanitize_text_field($_POST['message']);

// Validate message length
if (strlen($message) > 2000) {
    wp_send_json_error(['message' => 'Message too long']);
}

// Validate required fields
if (empty($message)) {
    wp_send_json_error(['message' => 'Message is required']);
}
```

#### **AJAX Security**
```php
// Verify nonce
if (!wp_verify_nonce($_POST['nonce'], 'gary_ai_action')) {
    wp_die('Security check failed', 'Error', ['response' => 403]);
}

// Check user capabilities
if (!current_user_can('read')) {
    wp_die('Insufficient permissions', 'Error', ['response' => 403]);
}
```

#### **Database Security**
```php
// Use prepared statements
global $wpdb;
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}gary_ai_conversations WHERE user_id = %d",
    $user_id
));

// Escape output
echo esc_html($user_message);
echo esc_attr($widget_id);
echo wp_kses($rich_content, $allowed_html);
```

---

## 🧪 **Testing**

### **PHP Testing (PHPUnit)**
```php
<?php
/**
 * Test class for Gary AI functionality
 */
class GaryAITest extends WP_UnitTestCase {
    
    /**
     * Test message validation
     */
    public function testMessageValidation() {
        $gary_ai = new GaryAI();
        
        // Test valid message
        $result = $gary_ai->validateMessage('Hello, this is a test message');
        $this->assertTrue($result);
        
        // Test empty message
        $result = $gary_ai->validateMessage('');
        $this->assertFalse($result);
        
        // Test long message
        $long_message = str_repeat('A', 2001);
        $result = $gary_ai->validateMessage($long_message);
        $this->assertFalse($result);
    }
    
    /**
     * Test API client initialization
     */
    public function testApiClientInit() {
        $client = new ContextualAIClient();
        $client->setCredentials('test_key', 'test_agent');
        $this->assertInstanceOf('ContextualAIClient', $client);
    }
}
```

### **JavaScript Testing (Jest)**
```javascript
/**
 * Gary AI Widget Tests
 */
describe('GaryAIChatWidget', () => {
    beforeEach(() => {
        // Set up DOM elements
        document.body.innerHTML = `
            <div id="gary-ai-widget-container"></div>
        `;
        
        // Mock jQuery
        global.$ = global.jQuery = require('jquery');
    });
    
    test('should initialize widget', () => {
        const widget = new GaryAIChatWidget();
        expect(widget).toBeDefined();
        expect(widget.options.container).toBe('#gary-ai-widget-container');
    });
    
    test('should validate message length', () => {
        const widget = new GaryAIChatWidget();
        
        expect(widget.validateMessage('Hello')).toBe(true);
        expect(widget.validateMessage('')).toBe(false);
        expect(widget.validateMessage('A'.repeat(2001))).toBe(false);
    });
});
```

### **Running Tests**
```bash
# Run PHP tests
composer test

# Run JavaScript tests
npm test

# Run all tests with coverage
npm run test:coverage
composer test -- --coverage-html coverage/

# Run specific test
npm test -- --grep "widget initialization"
vendor/bin/phpunit --filter testMessageValidation
```

---

## 🔧 **Build & Release Process**

### ⚠️ **IMPORTANT: ZIP Creation Policy**

**DO NOT CREATE ZIP FILES UNTIL EXPLICITLY INSTRUCTED BY USER**

All build scripts have been modified to skip ZIP creation per user preference. This policy must be maintained until the user explicitly requests ZIP file creation.

**Modified Commands:**
- `npm run package` → Shows warning instead of creating ZIP
- `npm run release` → Shows warning message  
- `npm run package-when-requested` → Use only when user explicitly requests ZIP

See `README-ZIP-CREATION.md` for detailed instructions.

### **Development Build**
```bash
# Install dependencies
npm install
composer install

# Development build (with source maps)
npm run dev

# Watch for changes
npm run watch
```

### **Production Build**
```bash
# Clean build
npm run clean

# Production build (minified, optimized)
npm run build

# Create release package
npm run package

# Validate package
npm run validate-package
```

### **Release Checklist**
- [ ] Version numbers updated in all files
- [ ] Changelog updated
- [ ] All tests passing
- [ ] Security audit completed
- [ ] Documentation updated
- [ ] Build artifacts generated
- [ ] Package validated

---

## 🐛 **Bug Reports**

### **Before Reporting**
1. **Search existing issues** to avoid duplicates
2. **Test with default theme** and no other plugins
3. **Check error logs** for relevant information
4. **Reproduce the issue** consistently

### **Bug Report Template**
```markdown
## Bug Description
Brief description of the bug

## Steps to Reproduce
1. Go to...
2. Click on...
3. Expected: ...
4. Actual: ...

## Environment
- WordPress Version: 
- Plugin Version: 
- PHP Version: 
- Theme: 
- Other Plugins: 

## Screenshots/Logs
[Attach relevant screenshots or error logs]

## Additional Context
Any other context about the problem
```

---

## ✨ **Feature Requests**

### **Feature Request Template**
```markdown
## Feature Description
Clear description of the proposed feature

## Problem It Solves
What problem does this feature address?

## Proposed Solution
How should this feature work?

## Alternatives Considered
Other solutions you considered

## Additional Context
Mock-ups, examples, or other context
```

---

## 📝 **Pull Request Process**

### **Before Submitting**
1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **Write tests** for your changes
4. **Follow coding standards**
5. **Update documentation** if needed
6. **Test thoroughly**

### **Pull Request Template**
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change)
- [ ] New feature (non-breaking change)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## How Has This Been Tested?
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed
- [ ] Cross-browser testing (if applicable)

## Checklist
- [ ] Code follows project coding standards
- [ ] Self-review completed
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No breaking changes (or clearly documented)
```

### **Review Process**
1. **Automated checks** must pass (CI/CD)
2. **Code review** by maintainers
3. **Testing** in development environment
4. **Approval** by at least one maintainer
5. **Merge** into main branch

---

## 🎯 **Project Structure**

```
gary-ai/
├── assets/                   # Frontend assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Image assets
├── includes/                # PHP classes
│   ├── class-admin-ajax.php
│   ├── class-analytics.php
│   └── class-contextual-ai-client.php
├── tests/                   # Test files
│   ├── php/                 # PHPUnit tests
│   ├── js/                  # Jest tests
│   └── fixtures/            # Test data
├── docker/                  # Docker configuration
├── docs/                    # Documentation
├── build/                   # Build scripts and tools
├── gary-ai.php             # Main plugin file
├── uninstall.php           # Uninstall script
├── composer.json           # PHP dependencies
├── package.json            # Node.js dependencies
├── README.md               # Project overview
├── CONTRIBUTING.md         # This file
├── SECURITY.md             # Security policy
└── CHANGELOG.md            # Version history
```

---

## 📚 **Resources**

### **WordPress Development**
- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security](https://developer.wordpress.org/apis/security/)

### **Testing**
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Unit Testing](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [Jest Testing Framework](https://jestjs.io/docs/getting-started)

### **API Integration**
- [Contextual AI Documentation](https://docs.contextual.ai/)
- [WordPress HTTP API](https://developer.wordpress.org/plugins/http-api/)
- [REST API Best Practices](https://restfulapi.net/)

---

## 🤝 **Community Guidelines**

### **Code of Conduct**
- **Be respectful** and inclusive
- **Focus on constructive feedback**
- **Help others learn and grow**
- **Report inappropriate behavior**

### **Communication**
- **GitHub Issues**: Bug reports and feature requests
- **Pull Requests**: Code contributions
- **Discussions**: General questions and ideas
- **Email**: security@gary-ai.com (security issues only)

### **Recognition**
Contributors will be recognized in:
- **Contributors list** in README.md
- **Release notes** for their contributions
- **Annual contributor report**

---

## 🏷️ **Versioning**

We use [Semantic Versioning](http://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

### **Branch Strategy**
- **`main`**: Production-ready code
- **`develop`**: Integration branch for features
- **`feature/*`**: Feature development branches
- **`hotfix/*`**: Critical bug fixes
- **`release/*`**: Release preparation branches

---

## 📞 **Getting Help**

### **Development Questions**
- **Documentation**: Check existing docs first
- **GitHub Discussions**: Ask questions and share ideas
- **Stack Overflow**: Tag with `gary-ai-plugin`

### **Urgent Issues**
- **Security**: security@gary-ai.com
- **Critical Bugs**: Create issue with "urgent" label
- **Build Issues**: Check CI/CD logs first

---

## 🎉 **Thank You!**

Your contributions make Gary AI better for everyone. Whether you're fixing bugs, adding features, improving documentation, or helping other users, every contribution is valued and appreciated.

**Happy coding!** 🚀

---

> **Last Updated**: January 2025  
> **Version**: 1.0.0  
> **Next Review**: April 2025  
> **Maintainers**: Gary AI Team 