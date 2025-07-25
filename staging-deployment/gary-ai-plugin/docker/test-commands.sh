#!/bin/bash

# Gary AI Plugin Docker Testing Commands
# Collection of useful commands for testing the plugin in Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to display colored output
echo_colored() {
    echo -e "${2}${1}${NC}"
}

# Function to check if containers are running
check_containers() {
    echo_colored "🔍 Checking container status..." $BLUE
    echo ""
    echo "WordPress:   $(docker ps --filter "name=gary-ai-wordpress" --format "table {{.Status}}" | tail -n +2)"
    echo "MySQL:       $(docker ps --filter "name=gary-ai-mysql" --format "table {{.Status}}" | tail -n +2)"
    echo "phpMyAdmin:  $(docker ps --filter "name=gary-ai-phpmyadmin" --format "table {{.Status}}" | tail -n +2)"
    echo "MailHog:     $(docker ps --filter "name=gary-ai-mailhog" --format "table {{.Status}}" | tail -n +2)"
    echo ""
}

# Function to start the environment
start_environment() {
    echo_colored "🚀 Starting Gary AI Plugin testing environment..." $GREEN
    docker-compose up -d
    echo ""
    echo_colored "⏳ Waiting for services to be ready..." $YELLOW
    sleep 10
    check_containers
    echo_colored "✅ Environment started! Access URLs:" $GREEN
    echo "   WordPress: http://localhost:8080"
    echo "   Admin: http://localhost:8080/wp-admin (admin/admin123)"
    echo "   phpMyAdmin: http://localhost:8081"
    echo "   MailHog: http://localhost:8025"
}

# Function to stop the environment
stop_environment() {
    echo_colored "🛑 Stopping Gary AI Plugin testing environment..." $RED
    docker-compose down
    echo_colored "✅ Environment stopped!" $GREEN
}

# Function to restart the environment
restart_environment() {
    echo_colored "🔄 Restarting Gary AI Plugin testing environment..." $YELLOW
    docker-compose down
    docker-compose up -d
    echo_colored "✅ Environment restarted!" $GREEN
}

# Function to view logs
view_logs() {
    local service=$1
    if [ -z "$service" ]; then
        echo_colored "📋 Available services: wordpress, mysql, phpmyadmin, mailhog, redis, wp-cli" $BLUE
        echo_colored "Usage: $0 logs <service_name>" $YELLOW
        return
    fi
    
    echo_colored "📋 Viewing logs for $service..." $BLUE
    docker-compose logs -f $service
}

# Function to execute WP-CLI commands
wp_cli() {
    echo_colored "🔧 Executing WP-CLI command..." $BLUE
    docker-compose exec wordpress wp "$@" --allow-root
}

# Function to access WordPress container shell
wp_shell() {
    echo_colored "🐚 Accessing WordPress container shell..." $BLUE
    docker-compose exec wordpress bash
}

# Function to access MySQL shell
mysql_shell() {
    echo_colored "🗄️ Accessing MySQL shell..." $BLUE
    docker-compose exec mysql mysql -u root -proot_password wordpress
}

# Function to backup database
backup_db() {
    local backup_file="backup_$(date +%Y%m%d_%H%M%S).sql"
    echo_colored "💾 Creating database backup: $backup_file" $BLUE
    docker-compose exec mysql mysqldump -u root -proot_password wordpress > $backup_file
    echo_colored "✅ Database backed up to $backup_file" $GREEN
}

# Function to restore database
restore_db() {
    local backup_file=$1
    if [ -z "$backup_file" ]; then
        echo_colored "Usage: $0 restore <backup_file.sql>" $YELLOW
        return
    fi
    
    if [ ! -f "$backup_file" ]; then
        echo_colored "❌ Backup file not found: $backup_file" $RED
        return
    fi
    
    echo_colored "📥 Restoring database from: $backup_file" $BLUE
    docker-compose exec -T mysql mysql -u root -proot_password wordpress < $backup_file
    echo_colored "✅ Database restored!" $GREEN
}

# Function to reset WordPress
reset_wordpress() {
    echo_colored "🔄 Resetting WordPress installation..." $YELLOW
    echo_colored "⚠️  This will delete all data! Press Ctrl+C to cancel or Enter to continue..." $RED
    read
    
    docker-compose down
    docker volume rm gary-ai_mysql_data gary-ai_wordpress_uploads gary-ai_wordpress_themes 2>/dev/null || true
    docker-compose up -d
    echo_colored "✅ WordPress reset complete!" $GREEN
}

# Function to activate/deactivate plugin
toggle_plugin() {
    local action=$1
    if [ "$action" = "activate" ]; then
        echo_colored "🔌 Activating Gary AI plugin..." $GREEN
        wp_cli plugin activate gary-ai
    elif [ "$action" = "deactivate" ]; then
        echo_colored "🔌 Deactivating Gary AI plugin..." $YELLOW
        wp_cli plugin deactivate gary-ai
    else
        echo_colored "Usage: $0 plugin <activate|deactivate>" $YELLOW
    fi
}

# Function to check plugin status
plugin_status() {
    echo_colored "🔍 Checking Gary AI plugin status..." $BLUE
    wp_cli plugin status gary-ai
}

# Function to flush rewrite rules
flush_rewrites() {
    echo_colored "🔄 Flushing WordPress rewrite rules..." $BLUE
    wp_cli rewrite flush
    echo_colored "✅ Rewrite rules flushed!" $GREEN
}

# Function to clear all caches
clear_caches() {
    echo_colored "🧹 Clearing all caches..." $BLUE
    wp_cli cache flush
    if command -v docker-compose exec redis redis-cli flushall >/dev/null 2>&1; then
        docker-compose exec redis redis-cli flushall
        echo_colored "✅ Redis cache cleared!" $GREEN
    fi
    echo_colored "✅ WordPress cache cleared!" $GREEN
}

# Function to run plugin tests
test_plugin() {
    echo_colored "🧪 Running Gary AI plugin tests..." $BLUE
    echo ""
    echo_colored "1. Checking plugin activation..." $YELLOW
    plugin_status
    echo ""
    echo_colored "2. Checking database tables..." $YELLOW
    wp_cli db query "SHOW TABLES LIKE '%gary_ai%'"
    echo ""
    echo_colored "3. Checking plugin options..." $YELLOW
    wp_cli option list --search="gary_ai_*"
    echo ""
    echo_colored "✅ Plugin tests completed!" $GREEN
}

# Function to show help
show_help() {
    echo_colored "🚀 Gary AI Plugin Docker Testing Commands" $BLUE
    echo ""
    echo "Available commands:"
    echo "  start     - Start the testing environment"
    echo "  stop      - Stop the testing environment"
    echo "  restart   - Restart the testing environment"
    echo "  status    - Check container status"
    echo "  logs      - View logs for a service"
    echo "  wp        - Execute WP-CLI commands"
    echo "  shell     - Access WordPress container shell"
    echo "  mysql     - Access MySQL shell"
    echo "  backup    - Backup database"
    echo "  restore   - Restore database from backup"
    echo "  reset     - Reset WordPress installation"
    echo "  plugin    - Activate/deactivate plugin"
    echo "  test      - Run plugin tests"
    echo "  flush     - Flush rewrite rules"
    echo "  cache     - Clear all caches"
    echo "  help      - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start"
    echo "  $0 wp plugin list"
    echo "  $0 logs wordpress"
    echo "  $0 plugin activate"
    echo "  $0 test"
}

# Main command handler
case "$1" in
    "start")
        start_environment
        ;;
    "stop")
        stop_environment
        ;;
    "restart")
        restart_environment
        ;;
    "status")
        check_containers
        ;;
    "logs")
        view_logs $2
        ;;
    "wp")
        shift
        wp_cli "$@"
        ;;
    "shell")
        wp_shell
        ;;
    "mysql")
        mysql_shell
        ;;
    "backup")
        backup_db
        ;;
    "restore")
        restore_db $2
        ;;
    "reset")
        reset_wordpress
        ;;
    "plugin")
        toggle_plugin $2
        ;;
    "test")
        test_plugin
        ;;
    "flush")
        flush_rewrites
        ;;
    "cache")
        clear_caches
        ;;
    "help"|"")
        show_help
        ;;
    *)
        echo_colored "❌ Unknown command: $1" $RED
        show_help
        ;;
esac 