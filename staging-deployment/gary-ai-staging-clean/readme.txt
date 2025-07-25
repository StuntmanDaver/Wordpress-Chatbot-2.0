=== Gary AI ===
Contributors: garyai
Tags: chatbot, ai, artificial intelligence, customer support, contextual ai
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered chatbot widget for WordPress using Contextual AI technology. Provides intelligent customer support and engagement through advanced conversational AI.

== Description ==

Gary AI is a powerful WordPress plugin that integrates advanced AI chatbot capabilities into your website using Contextual AI technology. The plugin provides a complete administrative interface for managing AI agents, datastores, and documents to create intelligent customer support experiences.

= Key Features =

* **Datastore Management**: Create and manage knowledge bases for your AI agents
* **Document Upload**: Upload PDFs, text files, Word documents to train your AI
* **Agent Configuration**: Create and customize AI agents with specific parameters
* **Setup Wizard**: Guided workflow for easy plugin configuration
* **Security First**: Built with WordPress security best practices
* **Modern UI**: Clean, responsive admin interface

= Admin Features =

* **Datastore Management UI**: Create, list, and delete datastores with intuitive card-based interface
* **Document Upload Interface**: Secure file upload supporting PDF, TXT, DOC, DOCX formats
* **Agent Management UI**: Full CRUD operations for AI agents with temperature and token controls
* **Setup Wizard**: Step-by-step guided configuration process
* **Real-time Status**: Live updates and progress tracking

= Security Features =

* CSRF protection with nonce validation
* Input sanitization and validation
* Secure file upload handling
* Capability-based access control
* XSS prevention measures

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Contextual AI API account
* SSL certificate recommended

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gary-ai` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Gary AI->Settings screen to configure the plugin.
4. Follow the Setup Wizard for guided configuration.

== Frequently Asked Questions ==

= Do I need a Contextual AI account? =

Yes, you need a Contextual AI API account to use this plugin. You can sign up at the Contextual AI website.

= What file types can I upload? =

The plugin supports PDF, TXT, DOC, and DOCX file formats for document upload.

= Is the plugin secure? =

Yes, the plugin is built with WordPress security best practices including CSRF protection, input sanitization, and secure file handling.

= Can I customize the AI agent behavior? =

Yes, you can configure agent parameters including temperature, maximum tokens, and system prompts through the Agent Management interface.

== Screenshots ==

1. Main Gary AI admin dashboard
2. Datastore management interface
3. Document upload interface
4. Agent configuration panel
5. Setup wizard workflow

== Changelog ==

= 1.0.0 =
* Initial release
* Datastore management UI
* Document upload interface
* Agent management UI
* Setup wizard
* Complete admin interface
* Security features implementation
* Contextual AI integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Gary AI plugin with complete admin interface and Contextual AI integration.

== Technical Details ==

= API Integration =
The plugin integrates with Contextual AI's REST API to provide:
* Datastore creation and management
* Document upload and processing
* Agent creation and configuration
* Real-time status monitoring

= File Structure =
* `/includes/` - Core plugin classes
* `/templates/` - Admin interface templates
* `/assets/` - CSS, JavaScript, and image files
* `/languages/` - Translation files
* `/docs/` - Documentation files

= Hooks and Filters =
The plugin provides various WordPress hooks and filters for developers to extend functionality.

== Support ==

For support, please visit the plugin settings page in your WordPress admin or contact the Gary AI team.
