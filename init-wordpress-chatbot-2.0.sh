#!/bin/bash
# WordPress Chatbot 2.0 Initialization Script
# Automatically configures WordPress with Gary AI plugin

echo "🚀 Initializing WordPress Chatbot 2.0..."

# Wait for WordPress to be ready
echo "⏳ Waiting for WordPress to be ready..."
sleep 30

# Install WordPress
echo "📦 Installing WordPress..."
wp core install \
  --url="http://localhost:8080" \
  --title="WordPress Chatbot 2.0" \
  --admin_user="ketcheld" \
  --admin_password="Paintball1@3" \
  --admin_email="admin@chatbot2.local" \
  --allow-root

# Activate Gary AI plugin
echo "🔌 Activating Gary AI plugin..."
wp plugin activate gary-ai --allow-root

# Configure Gary AI plugin with Contextual AI credentials
echo "🔧 Configuring Gary AI plugin..."
wp option update gary_ai_contextual_api_key "key-tBsgtQap8nle4u-D6QOoJZ6nOhHULw49S9DtX96JvS4_yr5O8" --allow-root
wp option update gary_ai_agent_id "1ef70a2a-1405-4ba5-9c27-62de4b263e20" --allow-root
wp option update gary_ai_datastore_id "6f01eb92-f12a-4113-a39f-3c4013303482" --allow-root
wp option update gary_ai_chatbot_enabled "1" --allow-root

# Set up basic WordPress settings
echo "⚙️ Configuring WordPress settings..."
wp option update blogname "WordPress Chatbot 2.0" --allow-root
wp option update blogdescription "AI-Powered WordPress Chatbot with Gary AI" --allow-root
wp option update permalink_structure "/%postname%/" --allow-root

# Install and activate a modern theme
echo "🎨 Setting up theme..."
wp theme install twentytwentyfour --activate --allow-root

# Create a test page for chatbot testing
echo "📄 Creating test page..."
wp post create \
  --post_type=page \
  --post_title="Chatbot Test Page" \
  --post_content="<h1>Welcome to WordPress Chatbot 2.0</h1><p>This page is for testing the Gary AI chatbot widget. The modern morphing chatbot should appear in the bottom right corner.</p>" \
  --post_status=publish \
  --allow-root

echo "✅ WordPress Chatbot 2.0 initialization complete!"
echo "🌐 Access your site at: http://localhost:8080"
echo "🔐 Admin login: ketcheld / Paintball1@3"
echo "🤖 Gary AI plugin is activated and configured!"
