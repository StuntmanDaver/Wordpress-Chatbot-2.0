# 🐳 Gary AI Plugin - Docker Testing Environment

> **Quick Start**: Complete WordPress testing environment for Gary AI plugin development and testing

## 🚀 **Quick Start (TL;DR)**

```bash
# Navigate to plugin directory
cd gary-ai

# Start the environment
docker-compose up -d

# Wait 30 seconds for setup, then visit:
# WordPress: http://localhost:8080
# Admin: http://localhost:8080/wp-admin (admin/admin123)
```

---

## 📋 **What You Get**

### **🌐 Services Included:**
- **WordPress 6.4** - Full WordPress installation with debugging enabled
- **MySQL 8.0** - Database with optimized settings for development
- **phpMyAdmin** - Web-based database management
- **MailHog** - Email testing (catches all emails sent by WordPress)
- **Redis** - Caching layer for performance testing
- **WP-CLI** - Command-line WordPress management

### **🔧 Development Tools:**
- **Query Monitor** - SQL debugging and performance profiling
- **User Switching** - Easy user role testing
- **WP Mail SMTP** - Email testing with MailHog integration
- **Debug Logging** - Full WordPress debugging enabled

---

## 🏗️ **Setup Instructions**

### **Prerequisites:**
- Docker and Docker Compose installed
- Git (for cloning/managing the plugin)
- Text editor (VS Code, Cursor, etc.)

### **1. Start the Environment**

```bash
# From the gary-ai directory
docker-compose up -d
```

**What happens automatically:**
- WordPress downloads and installs
- Database gets created and configured
- Plugin gets mounted and activated
- Test users and content get created
- Debug logging gets enabled

### **2. Access Your Test Site**

| Service | URL | Credentials |
|---------|-----|-------------|
| **WordPress** | http://localhost:8080 | - |
| **WordPress Admin** | http://localhost:8080/wp-admin | admin / admin123 |
| **phpMyAdmin** | http://localhost:8081 | root / root_password |
| **MailHog** | http://localhost:8025 | - |

### **3. Test Users Created**

| Role | Email | Password |
|------|-------|----------|
| **Administrator** | admin@example.com | admin123 |
| **Editor** | editor@example.com | editor123 |
| **Author** | author@example.com | author123 |
| **Subscriber** | subscriber@example.com | subscriber123 |

---

## 🧪 **Testing the Gary AI Plugin**

### **Initial Plugin Testing Checklist:**

1. **✅ Plugin Activation**
   ```bash
   # Check if plugin is active
   docker-compose exec wordpress wp plugin status gary-ai --allow-root
   ```

2. **✅ Database Tables**
   ```bash
   # Verify tables were created
   docker-compose exec wordpress wp db query "SHOW TABLES LIKE '%gary_ai%'" --allow-root
   ```

3. **✅ Plugin Settings**
   ```bash
   # Check plugin options
   docker-compose exec wordpress wp option list --search="gary_ai_*" --allow-root
   ```

4. **✅ Frontend Widget**
   - Visit http://localhost:8080
   - Open browser DevTools (F12) → Console
   - Look for "Gary AI: Widget container found" message
   - Check if widget appears in bottom-right corner

5. **✅ Admin Interface**
   - Go to http://localhost:8080/wp-admin
   - Look for "Gary AI" in admin menu
   - Test the settings page and "Test Connection" button

### **Advanced Testing Scenarios:**

#### **API Testing**
```bash
# Test with sample API credentials
docker-compose exec wordpress wp option update gary_ai_contextual_ai_api_key "test-api-key" --allow-root
docker-compose exec wordpress wp option update gary_ai_agent_id "test-agent-id" --allow-root
docker-compose exec wordpress wp option update gary_ai_datastore_id "test-datastore-id" --allow-root

# Enable the chatbot
docker-compose exec wordpress wp option update gary_ai_chatbot_enabled 1 --allow-root
```

#### **User Role Testing**
```bash
# Test with different user roles using User Switching plugin
# Go to Users → All Users → "Switch To" link next to any user
```

#### **Email Testing**
```bash
# All emails sent by WordPress will appear in MailHog
# Visit http://localhost:8025 to see captured emails
```

---

## 🛠️ **Utility Commands**

### **Using the Test Commands Script**

```bash
# Make the script executable (first time only)
chmod +x docker/test-commands.sh

# Available commands:
./docker/test-commands.sh start       # Start environment
./docker/test-commands.sh stop        # Stop environment
./docker/test-commands.sh restart     # Restart environment
./docker/test-commands.sh status      # Check container status
./docker/test-commands.sh test        # Run plugin tests
./docker/test-commands.sh logs wordpress  # View WordPress logs
./docker/test-commands.sh wp plugin list  # List all plugins
./docker/test-commands.sh shell       # Access WordPress shell
./docker/test-commands.sh mysql       # Access MySQL shell
./docker/test-commands.sh backup      # Backup database
./docker/test-commands.sh reset       # Reset entire WordPress
```

### **Direct Docker Commands**

```bash
# View logs
docker-compose logs -f wordpress
docker-compose logs -f mysql

# Execute WP-CLI commands
docker-compose exec wordpress wp --info --allow-root
docker-compose exec wordpress wp plugin list --allow-root
docker-compose exec wordpress wp theme list --allow-root

# Access container shells
docker-compose exec wordpress bash
docker-compose exec mysql bash

# Stop everything
docker-compose down

# Clean up (removes all data)
docker-compose down -v
docker system prune -f
```

---

## 🔍 **Debugging & Troubleshooting**

### **Check Plugin Status**
```bash
# Is the plugin active?
docker-compose exec wordpress wp plugin status gary-ai --allow-root

# Any plugin errors?
docker-compose exec wordpress wp plugin status --all --allow-root
```

### **Check Database**
```bash
# Are Gary AI tables created?
docker-compose exec wordpress wp db query "SHOW TABLES LIKE '%gary_ai%'" --allow-root

# Check plugin options
docker-compose exec wordpress wp option list --search="gary_ai" --allow-root

# Check WordPress options
docker-compose exec wordpress wp option get blogname --allow-root
```

### **Check Logs**
```bash
# WordPress debug log
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log

# PHP error log
docker-compose logs wordpress | grep -i error

# MySQL logs
docker-compose logs mysql | grep -i error
```

### **Common Issues & Solutions**

#### **Plugin Not Appearing in Admin**
```bash
# Check if plugin files are mounted correctly
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/gary-ai/

# Check for PHP syntax errors
docker-compose exec wordpress php -l /var/www/html/wp-content/plugins/gary-ai/gary-ai.php
```

#### **Widget Not Showing on Frontend**
```bash
# Check if chatbot is enabled
docker-compose exec wordpress wp option get gary_ai_chatbot_enabled --allow-root

# Enable debugging and check browser console
# Visit http://localhost:8080 and open DevTools → Console
```

#### **Database Connection Issues**
```bash
# Check if MySQL is running
docker ps | grep gary-ai-mysql

# Test database connection
docker-compose exec wordpress wp db check --allow-root
```

---

## 📁 **File Structure in Docker**

```
Container Path: /var/www/html/wp-content/plugins/gary-ai/
├── gary-ai.php                 # Main plugin file
├── uninstall.php              # Cleanup script
├── assets/                    # CSS/JS files
├── includes/                  # PHP classes
├── docker/                    # Docker configuration
└── ...                        # Other plugin files

WordPress Files: /var/www/html/
├── wp-config.php              # WordPress configuration
├── wp-content/
│   ├── plugins/gary-ai/       # Your plugin (mounted)
│   ├── themes/                # WordPress themes
│   ├── uploads/               # File uploads
│   └── debug.log              # Debug log file
```

---

## 🔄 **Development Workflow**

### **Typical Testing Session:**

1. **Start Environment**
   ```bash
   docker-compose up -d
   ```

2. **Make Code Changes**
   - Edit plugin files on your host machine
   - Changes are immediately reflected in the container

3. **Test Changes**
   ```bash
   # Deactivate and reactivate plugin
   docker-compose exec wordpress wp plugin deactivate gary-ai --allow-root
   docker-compose exec wordpress wp plugin activate gary-ai --allow-root
   
   # Clear caches
   docker-compose exec wordpress wp cache flush --allow-root
   ```

4. **Check Results**
   - Visit http://localhost:8080 to test frontend
   - Check http://localhost:8080/wp-admin for admin changes
   - Monitor logs for errors

5. **Backup Progress** (optional)
   ```bash
   ./docker/test-commands.sh backup
   ```

### **Reset for Clean Testing**
```bash
# Complete reset (deletes all data)
./docker/test-commands.sh reset

# Or manual reset
docker-compose down -v
docker-compose up -d
```

---

## 🎯 **Testing Checklist for Gary AI Plugin**

### **✅ Core Functionality Tests**

- [ ] **Plugin Activation**: No errors during activation
- [ ] **Database Tables**: All 4 tables created (conversations, analytics, performance, sessions)
- [ ] **Options Management**: Settings save and retrieve correctly
- [ ] **Widget Display**: Container appears on frontend
- [ ] **JavaScript Loading**: No console errors, widget initializes
- [ ] **AJAX Requests**: Test connection works, chat functionality operational
- [ ] **User Roles**: Different roles can access appropriate features
- [ ] **Email Testing**: Notifications sent to MailHog
- [ ] **Deactivation**: Clean deactivation without errors
- [ ] **Uninstall**: Complete cleanup of data

### **✅ Performance Tests**

- [ ] **Page Load Speed**: Widget doesn't slow down page loading
- [ ] **Database Queries**: Efficient queries (check Query Monitor)
- [ ] **Memory Usage**: Plugin doesn't exceed memory limits
- [ ] **Cache Compatibility**: Works with Redis caching

### **✅ Security Tests**

- [ ] **AJAX Security**: Nonce verification working
- [ ] **Capability Checks**: Only authorized users can access admin features
- [ ] **Input Sanitization**: All inputs properly sanitized
- [ ] **SQL Injection**: No direct database queries without preparation

---

## 🔧 **Customization Options**

### **Environment Variables**
Edit `.env` file to customize:
- Port numbers
- Database credentials
- WordPress admin credentials
- Plugin testing settings

### **Docker Compose Overrides**
Create `docker-compose.override.yml` for custom configurations:
```yaml
version: '3.8'

services:
  wordpress:
    ports:
      - "8090:80"  # Use different port
    environment:
      WORDPRESS_DEBUG_DISPLAY: 1  # Show errors on screen
```

### **Plugin Development**
- Live code editing (changes reflect immediately)
- Built-in debugging tools
- Database management via phpMyAdmin
- Email testing via MailHog
- Redis caching for performance testing

---

## 🚀 **Ready to Test!**

Your Gary AI plugin Docker testing environment is now complete! 

**Start testing with:**
```bash
docker-compose up -d
```

**Visit your test site:**
- http://localhost:8080

**Monitor your plugin:**
- Check browser console for JavaScript
- Use Query Monitor for database queries
- Watch MailHog for email notifications
- Use phpMyAdmin for database inspection

Happy testing! 🎉

---

> **💡 Pro Tip**: Keep the environment running during development for faster testing cycles. Only restart containers when you need to test plugin activation/deactivation hooks. 