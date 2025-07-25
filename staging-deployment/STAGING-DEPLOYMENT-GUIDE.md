# Gary AI Plugin - Staging Deployment Guide 🚀

Deploy the Gary AI plugin v1.0.2 with modern morphing chatbot widget to your staging site.

## 📦 Deployment Package

**File**: `gary-ai-staging-v1.0.2.zip`  
**Target Site**: https://staging.imisolutions.com/  
**Plugin Version**: 1.0.2 (Latest with modern morphing widget)

## 🎯 What's Included

### ✅ Modern Morphing Chatbot Widget
- **Morphing orb toggle** with continuous shape animation (8s cycle)
- **Glassmorphism effects** with backdrop blur and gradients
- **Smooth scale transitions** with cubic-bezier easing
- **Inter font integration** for modern typography
- **Responsive design** for all devices

### ✅ Complete Admin UI Features
- **Datastore Management** - Create, list, delete datastores with card interface
- **Document Upload Interface** - Upload PDFs, TXT, DOC, DOCX with progress tracking
- **Agent Management** - Full CRUD for AI agents with temperature/token controls
- **Setup Wizard** - 5-step guided workflow with progress tracking

### ✅ Production-Ready Security
- **CSRF protection** with WordPress nonces on all forms
- **Input sanitization** and validation throughout
- **Capability checks** (`manage_options`) on all admin actions
- **XSS prevention** with proper output escaping

### ✅ Enhanced Plugin Lifecycle
- **Proper deactivation** with cleanup of hooks, transients, cache
- **Complete uninstall** with database table and option removal
- **Error handling** with comprehensive logging

## 🚀 Deployment Steps

### Step 1: Upload Plugin
1. Download `gary-ai-staging-v1.0.2.zip` to your local machine
2. Access your staging site WordPress admin: https://staging.imisolutions.com/wp-admin
3. Go to **Plugins → Add New → Upload Plugin**
4. Choose `gary-ai-staging-v1.0.2.zip` and click **Install Now**
5. Click **Activate Plugin**

### Step 2: Configure API Credentials
1. Go to **Gary AI → Settings** in WordPress admin
2. Enter the following Contextual AI credentials:

```
API Key: key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8
Agent ID: 1ef70a2a-1405-4ba5-9c27-62de4b263e20
Datastore ID: 6f01eb92-f12a-4113-a39f-3c4013303482
```

3. Enable the chatbot: Check **"Enable Chatbot"**
4. Click **"Test Connection"** to verify API connectivity
5. Save settings

### Step 3: Verify Modern Widget
1. Visit your staging site frontend: https://staging.imisolutions.com/
2. Look for the **morphing orb** in the bottom-right corner
3. Verify the orb continuously morphs shapes (8-second cycle)
4. Click the orb to open the chat interface
5. Test sending a message to verify AI responses

## 🔧 Admin UI Testing Checklist

### ✅ Datastore Management
- [ ] Navigate to **Gary AI → Datastores**
- [ ] Verify datastore list displays with card interface
- [ ] Test creating a new datastore
- [ ] Test deleting a datastore

### ✅ Document Upload
- [ ] Navigate to **Gary AI → Documents**
- [ ] Test uploading a PDF file
- [ ] Test uploading a TXT file
- [ ] Verify upload progress and file listing

### ✅ Agent Management
- [ ] Navigate to **Gary AI → Agents**
- [ ] Test creating a new agent
- [ ] Configure temperature and max tokens
- [ ] Test agent CRUD operations

### ✅ Setup Wizard
- [ ] Navigate to **Gary AI → Setup Wizard**
- [ ] Complete all 5 steps of the guided setup
- [ ] Verify progress tracking and completion celebration

## 🎨 Modern Widget Features to Test

### Visual Elements
- [ ] **Morphing orb** continuously changes shape
- [ ] **Gradient background** (teal to dark blue)
- [ ] **Glassmorphism effect** with backdrop blur
- [ ] **Smooth animations** on hover and click
- [ ] **Pulsing effect** every 3 seconds

### Functionality
- [ ] **Click to open/close** chat interface
- [ ] **Message sending** works properly
- [ ] **AI responses** display correctly
- [ ] **Scroll behavior** in message area
- [ ] **Responsive design** on mobile devices

## 🔍 Troubleshooting

### Plugin Not Visible
- Check if plugin is activated in **Plugins** page
- Verify no PHP errors in **Tools → Site Health**
- Check server error logs for any issues

### API Connection Issues
- Verify API credentials are entered correctly
- Use **"Test Connection"** button in settings
- Check if staging server can make external HTTPS requests

### Widget Not Appearing
- Clear any caching plugins (WP Rocket, W3 Total Cache, etc.)
- Check browser console for JavaScript errors
- Verify theme doesn't conflict with widget CSS

### Admin UI Issues
- Ensure user has `manage_options` capability
- Check for JavaScript conflicts with other plugins
- Verify WordPress version compatibility (5.0+)

## 📊 Success Metrics

When deployment is successful, you should see:

### ✅ Frontend
- Modern morphing orb chatbot widget visible
- Smooth animations and glassmorphism effects
- Functional chat interface with AI responses

### ✅ Admin
- All 4 admin UI pages accessible and functional
- Settings page with all required fields
- Successful API connection test

### ✅ Security
- No PHP warnings or errors
- All forms protected with nonces
- Proper capability checks enforced

## 🎯 Post-Deployment Testing

1. **Frontend Testing**: Test chatbot on multiple pages and devices
2. **Admin Testing**: Verify all admin UI features work correctly
3. **Performance**: Check page load times with widget enabled
4. **Security**: Verify no security warnings in WordPress admin
5. **Compatibility**: Test with your existing theme and plugins

## 📞 Support Information

- **Plugin Version**: 1.0.2
- **WordPress Compatibility**: 5.0+
- **PHP Compatibility**: 7.4+
- **Tested Environment**: WordPress 6.x with modern themes

---

**Ready for Production**: This staging deployment includes all modern features, security enhancements, and production-ready code for the Gary AI plugin with morphing chatbot widget.

🎉 **Happy Testing on https://staging.imisolutions.com/!**
