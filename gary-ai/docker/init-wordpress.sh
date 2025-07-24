#!/bin/bash

# WordPress Initialization Script for Gary AI Plugin Testing
# This script runs when the WordPress container starts up

set -e

echo "🚀 Starting Gary AI Plugin WordPress Setup..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
while ! mysqladmin ping -h mysql -u wordpress -pwordpress_password --silent; do
    sleep 2
done

echo "✅ MySQL is ready!"

# Wait for WordPress to be installed
echo "⏳ Waiting for WordPress to be ready..."
while [ ! -f /var/www/html/wp-config.php ]; do
    sleep 2
done

echo "✅ WordPress is ready!"

# Set proper permissions for plugin directory
echo "🔧 Setting up plugin permissions..."
chown -R www-data:www-data /var/www/html/wp-content/plugins/gary-ai
chmod -R 755 /var/www/html/wp-content/plugins/gary-ai

# Enable error logging
echo "📝 Enabling error logging..."
mkdir -p /var/www/html/wp-content/debug
touch /var/www/html/wp-content/debug.log
chown www-data:www-data /var/www/html/wp-content/debug.log
chmod 644 /var/www/html/wp-content/debug.log

# Add custom wp-config settings for debugging
echo "⚙️ Adding debugging configuration..."
cat >> /var/www/html/wp-config.php << 'EOF'

// Gary AI Plugin Testing Configuration
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);

// Increase memory limit for plugin testing
ini_set('memory_limit', '512M');

// Disable file editing in admin
define('DISALLOW_FILE_EDIT', false);

// Enable auto-updates for testing
define('WP_AUTO_UPDATE_CORE', true);

EOF

# Create a test theme directory if it doesn't exist
echo "🎨 Setting up test theme..."
mkdir -p /var/www/html/wp-content/themes/gary-ai-test
chown -R www-data:www-data /var/www/html/wp-content/themes/gary-ai-test

echo "✅ Gary AI Plugin WordPress setup completed!"
echo "WordPress is ready at: http://localhost:8080"
echo "phpMyAdmin is available at: http://localhost:8081"
echo "MailHog is available at: http://localhost:8025"