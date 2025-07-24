# 🔒 Security Policy

## 🛡️ **Gary AI Plugin Security**

The Gary AI WordPress plugin takes security seriously. This document outlines our security practices, vulnerability reporting process, and guidelines for secure usage.

---

## 📊 **Security Overview**

### **Current Security Status**
- ✅ **WordPress Security Standards**: Full compliance with WordPress.org security guidelines
- ✅ **Input Validation**: All user inputs sanitized and validated
- ✅ **SQL Injection Protection**: Prepared statements and `$wpdb->prepare()` used throughout
- ✅ **XSS Prevention**: Output escaping with `esc_html()`, `esc_attr()`, and `wp_kses()`
- ✅ **CSRF Protection**: WordPress nonces for all AJAX requests
- ✅ **Capability Checks**: User permissions verified before sensitive operations
- ✅ **File Security**: ABSPATH protection on all PHP files
- ✅ **API Security**: SSL/TLS verification for external API calls

### **Security Testing**
- **Automated Testing**: Comprehensive security test suite included
- **Manual Review**: Code reviewed for security vulnerabilities
- **Dependency Scanning**: Regular updates and vulnerability audits
- **Penetration Testing**: Security validation against common attacks

---

## 🚨 **Reporting Security Vulnerabilities**

If you discover a security vulnerability in the Gary AI plugin, please report it responsibly:

### **🔥 Critical/High Severity Issues**
- **Email**: security@gary-ai.com
- **Response Time**: Within 24 hours
- **Encryption**: Use our PGP key for sensitive details

### **📧 Standard Security Issues**
- **GitHub Issues**: Use the "Security" label
- **Email**: security@gary-ai.com
- **Response Time**: Within 72 hours

### **What to Include**
1. **Description**: Clear description of the vulnerability
2. **Impact**: Potential impact and affected versions
3. **Reproduction**: Steps to reproduce the issue
4. **Environment**: WordPress version, PHP version, plugin version
5. **Proof of Concept**: Code snippets or screenshots (if applicable)

### **What NOT to Include**
- ❌ Do not publicly disclose until we've had time to address
- ❌ Do not include actual sensitive data in reports
- ❌ Do not perform testing on production sites without permission

---

## 🛠️ **Security Features**

### **Input Validation & Sanitization**

#### **Message Validation**
```php
// Maximum message length: 2000 characters
if (strlen($message) > 2000) {
    wp_send_json_error(['message' => 'Message too long']);
}

// Sanitize all text inputs
$message = sanitize_text_field($_POST['message']);
```

#### **API Key Validation**
```php
// Validate API key format
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $api_key)) {
    return new WP_Error('invalid_api_key', 'Invalid API key format');
}
```

### **Database Security**

#### **Prepared Statements**
```php
// All database queries use prepared statements
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d AND session_id = %s",
    $user_id,
    $session_id
));
```

#### **Table Creation Security**
```php
// Tables created with proper indexes and constraints
$sql = "CREATE TABLE {$table_name} (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    message text NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY created_at (created_at)
) {$charset_collate};";
```

### **AJAX Security**

#### **Nonce Verification**
```php
// All AJAX requests require valid nonces
if (!wp_verify_nonce($_POST['nonce'], 'gary_ai_action')) {
    wp_die('Security check failed', 'Error', ['response' => 403]);
}
```

#### **Capability Checks**
```php
// User permissions verified before sensitive operations
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions', 'Error', ['response' => 403]);
}
```

### **API Communication Security**

#### **SSL/TLS Verification**
```php
// All API calls use SSL verification
$args = [
    'sslverify' => true,
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . $this->api_key,
        'User-Agent' => 'Gary-AI-WordPress-Plugin/1.0.0'
    ]
];
```

#### **API Key Protection**
- API keys stored using WordPress options API
- No API keys exposed in client-side code
- API key validation before storage
- Secure transmission over HTTPS only

---

## 🔧 **Secure Configuration**

### **WordPress Requirements**
- **WordPress Version**: 5.0 or higher
- **PHP Version**: 7.4 or higher
- **MySQL Version**: 5.6 or higher
- **SSL Certificate**: Required for production use

### **Recommended Security Settings**

#### **WordPress Configuration**
```php
// wp-config.php security settings
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);
```

#### **Server Configuration**
- **PHP Settings**:
  - `display_errors = Off`
  - `log_errors = On`
  - `session.cookie_secure = 1`
  - `session.cookie_httponly = 1`

### **Plugin-Specific Settings**

#### **Recommended Options**
```php
// Enable analytics for security monitoring
update_option('gary_ai_analytics_enabled', 1);

// Set appropriate data retention (90 days default)
update_option('gary_ai_data_retention', 90);

// Enable real-time monitoring
update_option('gary_ai_real_time_updates', 1);
```

#### **Security Headers**
The plugin automatically adds security headers:
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
```

---

## 🚪 **Access Control**

### **User Roles & Capabilities**

| Role | Capabilities | Access Level |
|------|-------------|--------------|
| **Administrator** | Full plugin management | Complete access |
| **Editor** | Chat access, basic settings | Limited admin |
| **Author** | Chat access only | Frontend only |
| **Subscriber** | Chat access only | Frontend only |
| **Visitor** | Chat access (if enabled) | Frontend only |

### **API Access Control**
- API keys tied to specific agents and datastores
- Rate limiting enforced at API level
- Session-based access for chat functionality
- Automatic logout after inactivity

---

## 🔄 **Security Maintenance**

### **Regular Updates**
- **Plugin Updates**: Applied automatically or manually
- **Dependency Updates**: Regular security audits and updates
- **WordPress Core**: Keep WordPress updated
- **PHP/Server**: Maintain server security updates

### **Security Monitoring**
```php
// Built-in security logging
error_log('Gary AI Security Event: ' . $event_type . ' - ' . $details);

// Failed authentication attempts logged
if (!wp_verify_nonce($nonce, 'gary_ai_action')) {
    error_log('Gary AI: Invalid nonce attempt from IP: ' . $_SERVER['REMOTE_ADDR']);
}
```

### **Data Protection**
- **Encryption**: Sensitive data encrypted at rest
- **Retention**: Configurable data retention periods
- **Cleanup**: Automatic cleanup of expired data
- **Anonymization**: PII anonymized where possible

---

## 🏭 **Production Security Checklist**

### **Pre-Deployment**
- [ ] Update all dependencies to latest secure versions
- [ ] Run security test suite (`tests/security-validation-test.php`)
- [ ] Verify SSL certificates are valid
- [ ] Review and audit API key security
- [ ] Check file permissions (644 for files, 755 for directories)
- [ ] Verify WordPress and PHP versions meet requirements

### **Post-Deployment**
- [ ] Monitor error logs for security events
- [ ] Verify HTTPS is enforced
- [ ] Test all authentication flows
- [ ] Confirm rate limiting is working
- [ ] Check database security settings
- [ ] Validate backup and recovery procedures

### **Ongoing Monitoring**
- [ ] Regular security scans
- [ ] Monitor failed authentication attempts
- [ ] Review access logs
- [ ] Update dependencies monthly
- [ ] Conduct quarterly security reviews

---

## 📚 **Security Resources**

### **WordPress Security**
- [WordPress Security Handbook](https://developer.wordpress.org/apis/security/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

### **General Security**
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Web Application Security](https://portswigger.net/web-security)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)

### **API Security**
- [OWASP API Security](https://owasp.org/www-project-api-security/)
- [REST API Security](https://restfulapi.net/security-essentials/)

---

## 🔐 **Compliance & Standards**

### **Standards Compliance**
- ✅ **WordPress.org Guidelines**: Full compliance
- ✅ **PHP Security Standards**: PSR-12 with security extensions
- ✅ **Web Security**: OWASP Top 10 protection
- ✅ **Data Protection**: GDPR compliance features included

### **Security Certifications**
- Regular security audits conducted
- Penetration testing completed
- Code review by security professionals
- Dependency vulnerability scanning

---

## 📞 **Security Contact**

**Security Team**: security@gary-ai.com  
**PGP Key**: [Download Public Key](https://gary-ai.com/security/pgp-key.asc)  
**Response Time**: 24-72 hours depending on severity  

For urgent security matters, include "URGENT SECURITY" in the subject line.

---

> **Last Updated**: January 2025  
> **Version**: 1.0.0  
> **Next Review**: April 2025  
> **Status**: ✅ **Current and Comprehensive** 