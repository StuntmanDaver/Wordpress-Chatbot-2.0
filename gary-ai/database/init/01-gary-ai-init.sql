-- Gary AI WordPress Plugin Database Initialization

USE gary_ai_wordpress;

-- Create additional tables if needed
-- These will be created by the plugin activation, but can be pre-created here

-- Grant additional permissions if needed
GRANT ALL PRIVILEGES ON gary_ai_wordpress.* TO 'gary_ai_user'@'%';
FLUSH PRIVILEGES;

-- Insert sample data for testing (optional)
-- This can be uncommented for development with sample data
-- INSERT INTO wp_options (option_name, option_value) VALUES ('gary_ai_sample_data', 'true');
