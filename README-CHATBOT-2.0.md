# WordPress Chatbot 2.0 🤖

A complete Docker-based WordPress environment with the Gary AI plugin featuring a modern morphing chatbot widget.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- PowerShell (Windows) or Bash (Linux/Mac)

### Deployment Steps

1. **Start WordPress Chatbot 2.0:**
   ```powershell
   .\start-chatbot-2.0.ps1
   ```

2. **Access WordPress:**
   - URL: http://localhost:8080
   - Admin: http://localhost:8080/wp-admin
   - Username: `ketcheld`
   - Password: `Paintball1@3`

3. **Additional Services:**
   - phpMyAdmin: http://localhost:8081
   - MailHog: http://localhost:8025

## 🐳 Container Information

### Container Names
- **WordPress**: `wordpress-chatbot-2-0`
- **MySQL**: `mysql-chatbot-2-0`
- **phpMyAdmin**: `phpmyadmin-chatbot-2-0`
- **MailHog**: `mailhog-chatbot-2-0`
- **WP-CLI**: `wp-cli-chatbot-2-0`

### Port Mappings (User Requirements)
- **WordPress**: 8080 → 80
- **phpMyAdmin**: 8081 → 80
- **MailHog**: 8025 → 8025

## 🔧 Gary AI Plugin Features

### Modern Morphing Chatbot Widget
- ✅ **Morphing orb toggle** with continuous shape animation
- ✅ **Glassmorphism effects** with backdrop blur
- ✅ **Smooth scale transitions** and modern gradients
- ✅ **Inter font integration** for modern typography

### Complete Admin UI
- ✅ **Datastore Management** - Create, list, delete datastores
- ✅ **Document Upload Interface** - Upload PDFs, TXT, DOC, DOCX files
- ✅ **Agent Management** - Full CRUD for AI agents with configuration
- ✅ **Setup Wizard** - Step-by-step guided workflow

### Pre-Configured API Credentials
- **API Key**: `key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8`
- **Agent ID**: `1ef70a2a-1405-4ba5-9c27-62de4b263e20`
- **Datastore ID**: `6f01eb92-f12a-4113-a39f-3c4013303482`

## 🛠️ Manual Commands

### Start Services
```bash
docker-compose -f docker-compose-chatbot-2.0.yml up -d
```

### Stop Services
```bash
docker-compose -f docker-compose-chatbot-2.0.yml down --volumes
```

### View Logs
```bash
docker-compose -f docker-compose-chatbot-2.0.yml logs -f
```

### Access WP-CLI
```bash
docker exec -it wp-cli-chatbot-2-0 wp --info
```

## 🔍 Troubleshooting

### WordPress Not Accessible
1. Check container status: `docker-compose -f docker-compose-chatbot-2.0.yml ps`
2. View WordPress logs: `docker-compose -f docker-compose-chatbot-2.0.yml logs wordpress-chatbot-2`
3. Ensure port 8080 is not in use: `netstat -ano | findstr :8080`

### Plugin Not Working
1. Verify plugin is activated in WordPress admin
2. Check Gary AI settings page for API credentials
3. Test connection using the "Test Connection" button

### Database Issues
1. Access phpMyAdmin at http://localhost:8081
2. Check database `wordpress_chatbot_2` exists
3. Verify MySQL container is running: `docker ps | findstr mysql-chatbot-2-0`

## 📁 File Structure

```
WordPress Chatbot 2.0/
├── docker-compose-chatbot-2.0.yml    # Main Docker Compose file
├── start-chatbot-2.0.ps1             # PowerShell startup script
├── init-wordpress-chatbot-2.0.sh     # WordPress initialization script
├── README-CHATBOT-2.0.md             # This documentation
└── gary-ai/                          # Gary AI Plugin Directory
    ├── gary-ai.php                   # Main plugin file
    ├── assets/css/chat-widget.css    # Modern morphing widget CSS
    ├── includes/                     # Plugin classes
    ├── templates/                    # Admin UI templates
    └── docs/                         # Documentation
```

## 🎯 Features Verification Checklist

### ✅ Deployment
- [ ] All containers start successfully
- [ ] WordPress accessible on port 8080
- [ ] phpMyAdmin accessible on port 8081
- [ ] MailHog accessible on port 8025

### ✅ Gary AI Plugin
- [ ] Plugin activated in WordPress admin
- [ ] Settings page shows all required fields
- [ ] API credentials configured correctly
- [ ] Test connection successful

### ✅ Modern Chatbot Widget
- [ ] Morphing orb visible on frontend
- [ ] Smooth animations working
- [ ] Chat interface opens/closes properly
- [ ] Messages display correctly

### ✅ Admin UI Features
- [ ] Datastore Management UI functional
- [ ] Document Upload Interface working
- [ ] Agent Management UI operational
- [ ] Setup Wizard completes successfully

## 🚨 Important Notes

- **Port Requirements**: WordPress MUST run on 8080, phpMyAdmin on 8081, MailHog on 8025
- **Container Names**: All containers prefixed with `wordpress-chatbot-2-0` or similar
- **Network**: All services run on isolated `wordpress-chatbot-2-network`
- **Volumes**: Persistent data stored in named Docker volumes

## 🎉 Success Indicators

When deployment is successful, you should see:
1. ✅ All 4-5 containers running
2. ✅ WordPress login page at http://localhost:8080
3. ✅ Gary AI plugin listed in WordPress admin
4. ✅ Modern morphing chatbot widget on frontend
5. ✅ All admin UI features accessible

---

**WordPress Chatbot 2.0** - Powered by Gary AI Plugin v1.0.2 with Modern Morphing Widget
