# 🐳 Gary AI Plugin - Docker Testing Quick Start

> **One-command setup for complete WordPress testing environment**

## 🚀 **Quick Start (30 seconds)**

```bash
# 1. Navigate to plugin directory
cd gary-ai

# 2. Start everything
docker-compose up -d

# 3. Wait 30 seconds, then visit:
# → WordPress: http://localhost:8080
# → Admin: http://localhost:8080/wp-admin (admin/admin123)
```

## 📋 **What Gets Created Automatically**

- **WordPress 6.4** with Gary AI plugin pre-installed and activated
- **MySQL 8.0** database with all plugin tables
- **phpMyAdmin** at http://localhost:8081 (root/root_password)
- **MailHog** email testing at http://localhost:8025
- **Test users** with different roles (editor/author/subscriber)
- **Debug logging** enabled for troubleshooting
- **Development tools** (Query Monitor, User Switching)

## 🧪 **Testing Your Plugin**

### **1. Check Plugin Status**
```bash
# Is plugin active?
docker-compose exec wordpress wp plugin status gary-ai --allow-root

# Check database tables
docker-compose exec wordpress wp db query "SHOW TABLES LIKE '%gary_ai%'" --allow-root

# Check plugin options
docker-compose exec wordpress wp option list --search="gary_ai_*" --allow-root
```

### **2. Test Frontend Widget**
- Visit: http://localhost:8080
- Open DevTools (F12) → Console
- Look for: "Gary AI: Widget container found"
- Widget should appear in bottom-right corner

### **3. Test Admin Interface**
- Visit: http://localhost:8080/wp-admin
- Login: admin / admin123
- Find "Gary AI" in admin menu
- Test settings and "Test Connection" button

### **4. Test API Configuration**
```bash
# Set test credentials
docker-compose exec wordpress wp option update gary_ai_contextual_ai_api_key "test-api-key" --allow-root
docker-compose exec wordpress wp option update gary_ai_agent_id "test-agent-id" --allow-root
docker-compose exec wordpress wp option update gary_ai_datastore_id "test-datastore-id" --allow-root

# Enable chatbot
docker-compose exec wordpress wp option update gary_ai_chatbot_enabled 1 --allow-root
```

## 🛠️ **Useful Commands**

### **Docker Operations**
```bash
# Start environment
docker-compose up -d

# Stop environment
docker-compose down

# View logs
docker-compose logs -f wordpress

# Reset everything (deletes all data)
docker-compose down -v && docker-compose up -d
```

### **WordPress Commands**
```bash
# Execute any WP-CLI command
docker-compose exec wordpress wp <command> --allow-root

# Examples:
docker-compose exec wordpress wp plugin list --allow-root
docker-compose exec wordpress wp user list --allow-root
docker-compose exec wordpress wp cache flush --allow-root
```

### **Plugin Development**
```bash
# Deactivate/reactivate plugin (to test hooks)
docker-compose exec wordpress wp plugin deactivate gary-ai --allow-root
docker-compose exec wordpress wp plugin activate gary-ai --allow-root

# Check for PHP errors
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

## 🔍 **Debugging**

### **Common Issues**

**Plugin not visible?**
```bash
# Check if mounted correctly
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/gary-ai/

# Check for syntax errors
docker-compose exec wordpress php -l /var/www/html/wp-content/plugins/gary-ai/gary-ai.php
```

**Widget not showing?**
```bash
# Check if enabled
docker-compose exec wordpress wp option get gary_ai_chatbot_enabled --allow-root

# Check browser console for JavaScript errors
```

**Database issues?**
```bash
# Test database connection
docker-compose exec wordpress wp db check --allow-root

# Check if tables exist
docker-compose exec wordpress wp db query "SHOW TABLES" --allow-root
```

## 📁 **Test Users & URLs**

| Service | URL | Credentials |
|---------|-----|-------------|
| **WordPress** | http://localhost:8080 | - |
| **Admin Panel** | http://localhost:8080/wp-admin | admin / admin123 |
| **phpMyAdmin** | http://localhost:8081 | root / root_password |
| **Email Testing** | http://localhost:8025 | - |

| User Role | Email | Password |
|-----------|-------|----------|
| Administrator | admin@example.com | admin123 |
| Editor | editor@example.com | editor123 |
| Author | author@example.com | author123 |
| Subscriber | subscriber@example.com | subscriber123 |

## 🎯 **Testing Checklist**

- [ ] Plugin activates without errors
- [ ] Database tables created (4 tables)
- [ ] Widget container appears on frontend
- [ ] Admin menu item "Gary AI" visible
- [ ] Settings page loads and saves
- [ ] Test connection button works
- [ ] JavaScript loads without console errors
- [ ] Plugin deactivates cleanly
- [ ] Uninstall removes all data

## 🔄 **Development Workflow**

1. **Start environment**: `docker-compose up -d`
2. **Edit plugin files** on your host machine (changes are live)
3. **Test changes** in browser at http://localhost:8080
4. **Check logs**: `docker-compose logs -f wordpress`
5. **Debug issues**: Use phpMyAdmin and browser DevTools

## 🧹 **Cleanup**

```bash
# Stop containers
docker-compose down

# Remove all data (complete reset)
docker-compose down -v

# Remove Docker images (free up space)
docker system prune -f
```

---

## 📖 **Full Documentation**

For detailed documentation, see:
- **[DOCKER-TESTING-GUIDE.md](DOCKER-TESTING-GUIDE.md)** - Complete testing guide
- **[FINAL-DEPLOYMENT-STATUS.md](FINAL-DEPLOYMENT-STATUS.md)** - Production deployment info

---

> **💡 Pro Tip**: Keep the environment running during development for faster testing. The plugin files are live-mounted, so changes are immediate! 