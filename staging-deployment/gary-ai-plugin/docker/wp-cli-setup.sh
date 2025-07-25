#!/bin/bash

# WP-CLI Setup Script for Gary AI Plugin Testing
# This script configures WordPress and activates the plugin automatically

set -e

echo "🚀 Starting WP-CLI WordPress Configuration..."

# Wait for WordPress to be fully ready
echo "⏳ Waiting for WordPress to be accessible..."
while ! wp core is-installed 2>/dev/null; do
    echo "Waiting for WordPress installation..."
    sleep 5
done

echo "✅ WordPress is installed and ready!"

# Install WordPress if not already configured
if ! wp core is-installed --allow-root 2>/dev/null; then
    echo "📦 Installing WordPress..."
    wp core install \
        --url="http://localhost:8080" \
        --title="Gary AI Plugin Test Site" \
        --admin_user="admin" \
        --admin_password="admin123" \
        --admin_email="admin@example.com" \
        --allow-root
    
    echo "✅ WordPress installed successfully!"
fi

# Activate the Gary AI plugin
echo "🔌 Activating Gary AI plugin..."
if wp plugin is-installed gary-ai --allow-root; then
    wp plugin activate gary-ai --allow-root
    echo "✅ Gary AI plugin activated!"
else
    echo "❌ Gary AI plugin not found. Make sure the plugin files are mounted correctly."
fi

# Install and activate useful testing plugins
echo "🛠️ Installing testing utilities..."

# Query Monitor for debugging
wp plugin install query-monitor --activate --allow-root 2>/dev/null || echo "Query Monitor already installed"

# User Switching for easy user testing
wp plugin install user-switching --activate --allow-root 2>/dev/null || echo "User Switching already installed"

# WP Mail SMTP for email testing with MailHog
wp plugin install wp-mail-smtp --activate --allow-root 2>/dev/null || echo "WP Mail SMTP already installed"

# Create test users for different roles
echo "👥 Creating test users..."
wp user create editor editor@example.com --role=editor --user_pass=editor123 --allow-root 2>/dev/null || echo "Editor user already exists"
wp user create author author@example.com --role=author --user_pass=author123 --allow-root 2>/dev/null || echo "Author user already exists"
wp user create subscriber subscriber@example.com --role=subscriber --user_pass=subscriber123 --allow-root 2>/dev/null || echo "Subscriber user already exists"

# Configure WP Mail SMTP to use MailHog
echo "📧 Configuring email testing..."
wp option update wp_mail_smtp '{"mail":{"from_email":"admin@example.com","from_name":"Gary AI Test Site","mailer":"smtp","return_path":true},"smtp":{"host":"mailhog","encryption":"none","port":1025,"auth":false,"user":"","pass":""}}' --format=json --allow-root 2>/dev/null || echo "Email configuration skipped"

# Set a default theme
echo "🎨 Setting up theme..."
wp theme install twentytwentyfour --activate --allow-root 2>/dev/null || echo "Theme setup skipped"

# Create some test content
echo "📝 Creating test content..."
wp post create --post_type=page --post_title="Test Page" --post_content="This is a test page for Gary AI plugin testing. The chat widget should appear on this page." --post_status=publish --allow-root 2>/dev/null || echo "Test page already exists"

wp post create --post_title="Test Post" --post_content="This is a test blog post for Gary AI plugin testing. The chat widget should appear on this post too." --post_status=publish --allow-root 2>/dev/null || echo "Test post already exists"

# Update permalink structure for better testing
echo "🔗 Setting up permalinks..."
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

# Set timezone
wp option update timezone_string 'America/New_York' --allow-root

# Configure WordPress for better development
echo "⚙️ Configuring WordPress for development..."
wp config set WP_DEBUG true --allow-root
wp config set WP_DEBUG_LOG true --allow-root
wp config set WP_DEBUG_DISPLAY false --allow-root
wp config set SCRIPT_DEBUG true --allow-root

# Flush rewrite rules
wp rewrite flush --allow-root

echo "🎉 WordPress configuration completed!"
echo ""
echo "🌐 Test Site URLs:"
echo "   WordPress Admin: http://localhost:8080/wp-admin"
echo "   Username: admin"
echo "   Password: admin123"
echo ""
echo "🔧 Development Tools:"
echo "   phpMyAdmin: http://localhost:8081"
echo "   MailHog: http://localhost:8025"
echo ""
echo "👤 Test Users:"
echo "   Editor: editor@example.com / editor123"
echo "   Author: author@example.com / author123"
echo "   Subscriber: subscriber@example.com / subscriber123"
echo ""
echo "✅ Ready to test Gary AI plugin!" 