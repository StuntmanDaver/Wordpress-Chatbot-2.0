#!/usr/bin/env node

// ⚠️  IMPORTANT: DO NOT PRODUCE ZIP FILE UNTIL EXPLICITLY INSTRUCTED BY USER
// ⚠️  This script will create release files but should NOT generate ZIP automatically
// ⚠️  User preference: Only create ZIP when specifically requested

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const PLUGIN_NAME = 'gary-ai';
const VERSION = '1.0.0';
const BUILD_DIR = path.join(__dirname, 'dist');
const RELEASE_DIR = path.join(BUILD_DIR, PLUGIN_NAME);

console.log(`🚀 Gary AI Plugin - Creating release package v${VERSION}`);
console.log('⚠️  WARNING: ZIP creation disabled until user explicitly requests it');
console.log('========================================================');

// Clean and create build directory
console.log('🧹 Cleaning build directory...');
if (fs.existsSync(BUILD_DIR)) {
    fs.rmSync(BUILD_DIR, { recursive: true, force: true });
}
fs.mkdirSync(BUILD_DIR, { recursive: true });
fs.mkdirSync(RELEASE_DIR, { recursive: true });

// Copy main plugin file
console.log('📄 Copying main plugin file...');
if (fs.existsSync('gary-ai.php')) {
    fs.copyFileSync('gary-ai.php', path.join(RELEASE_DIR, 'gary-ai.php'));
    console.log('   ✅ gary-ai.php copied');
} else {
    console.error('   ❌ gary-ai.php not found!');
    process.exit(1);
}

// Copy uninstall script
console.log('🗑️ Copying uninstall script...');
if (fs.existsSync('uninstall.php')) {
    fs.copyFileSync('uninstall.php', path.join(RELEASE_DIR, 'uninstall.php'));
    console.log('   ✅ uninstall.php copied');
}

// Copy includes directory
console.log('📁 Copying includes directory...');
if (fs.existsSync('includes')) {
    copyDirectory('includes', path.join(RELEASE_DIR, 'includes'));
    console.log('   ✅ includes directory copied');
} else {
    console.error('   ❌ includes directory not found!');
    process.exit(1);
}

// Copy assets directory
console.log('🎨 Copying assets directory...');
if (fs.existsSync('assets')) {
    copyDirectory('assets', path.join(RELEASE_DIR, 'assets'));
    console.log('   ✅ assets directory copied');
} else {
    console.error('   ❌ assets directory not found!');
    process.exit(1);
}

// Create languages directory (empty but for future use)
console.log('🌍 Creating languages directory...');
fs.mkdirSync(path.join(RELEASE_DIR, 'languages'), { recursive: true });
console.log('   ✅ languages directory created');

// Create readme.txt for WordPress.org compatibility
console.log('📝 Creating readme.txt...');
createReadmeTxt();
console.log('   ✅ readme.txt created');

// Create LICENSE file
console.log('⚖️ Creating LICENSE file...');
createLicenseFile();
console.log('   ✅ LICENSE file created');

// Validate release package
console.log('🔍 Validating release package...');
validatePackage();

// ⚠️  ZIP FILE CREATION DISABLED
// ⚠️  User preference: Do not create ZIP until explicitly instructed
console.log('📦 Creating ZIP archive...');
const zipFileName = `${PLUGIN_NAME}-${VERSION}.zip`;
try {
    execSync(`powershell -Command "Compress-Archive -Path '${RELEASE_DIR}\\*' -DestinationPath '${zipFileName}' -Force"`, { stdio: 'pipe' });
    console.log(`   ✅ ${zipFileName} created successfully`);
} catch (error) {
    console.error('   ❌ Failed to create ZIP file:', error.message);
    process.exit(1);
}

// Show package info
console.log('\n🎉 Release files created successfully!');
console.log('========================================================');
console.log(`📂 Release directory: ${RELEASE_DIR}`);
console.log(`⚠️  ZIP file NOT created (user preference)`);
console.log(`📝 Potential ZIP name: ${zipFileName}`);
console.log(`📏 Version: ${VERSION}`);
console.log(`📁 Files included:`);
listPackageContents();
console.log('🔧 To create ZIP: User must explicitly request it');

function copyDirectory(src, dest) {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }
    
    const items = fs.readdirSync(src);
    for (const item of items) {
        const srcPath = path.join(src, item);
        const destPath = path.join(dest, item);
        
        if (fs.statSync(srcPath).isDirectory()) {
            copyDirectory(srcPath, destPath);
        } else {
            // Skip development files
            if (shouldExcludeFile(item)) {
                continue;
            }
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

function shouldExcludeFile(filename) {
    const excludePatterns = [
        '.map',
        '.scss',
        '.less',
        '.dev.js',
        '.dev.css',
        '.DS_Store',
        'Thumbs.db',
        '.gitkeep'
    ];
    
    return excludePatterns.some(pattern => filename.includes(pattern));
}

function createReadmeTxt() {
    const readme = `=== Gary AI ===
Contributors: garyaiteam
Tags: chatbot, ai, customer-support, contextual-ai, chat-widget
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: ${VERSION}
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered chatbot widget for WordPress using Contextual AI technology.

== Description ==

Gary AI provides intelligent customer support and engagement through advanced conversational AI. The plugin integrates seamlessly with your WordPress site to offer 24/7 automated assistance to your visitors.

= Features =

* AI-powered conversations using Contextual AI technology
* Easy setup and configuration
* Customizable chat widget appearance
* Analytics and conversation tracking
* GDPR compliance features
* Mobile-responsive design

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Contextual AI API credentials

== Installation ==

1. Upload the plugin files to the \`/wp-content/plugins/gary-ai\` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Gary AI > Settings to configure your API credentials
4. Enable the chatbot and customize the appearance as needed

== Frequently Asked Questions ==

= How do I get API credentials? =

You need to sign up for a Contextual AI account and obtain your API key, Agent ID, and Datastore ID from your dashboard.

= Is the plugin GDPR compliant? =

Yes, the plugin includes GDPR compliance features and respects user privacy preferences.

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered chat functionality
* Admin dashboard and settings
* Analytics and tracking
* GDPR compliance features

== Upgrade Notice ==

= 1.0.0 =
Initial release of Gary AI chatbot plugin.
`;

    fs.writeFileSync(path.join(RELEASE_DIR, 'readme.txt'), readme);
}

function createLicenseFile() {
    const license = `Copyright (C) 2025 Gary AI Team

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
`;

    fs.writeFileSync(path.join(RELEASE_DIR, 'LICENSE'), license);
}

function validatePackage() {
    const requiredFiles = [
        'gary-ai.php',
        'uninstall.php',
        'readme.txt',
        'LICENSE',
        'includes/class-contextual-ai-client.php',
        'includes/class-admin-ajax.php',
        'assets/css/chat-widget.css',
        'assets/js/chat-widget.js'
    ];
    
    let allValid = true;
    for (const file of requiredFiles) {
        const filePath = path.join(RELEASE_DIR, file);
        if (!fs.existsSync(filePath)) {
            console.error(`   ❌ Missing required file: ${file}`);
            allValid = false;
        }
    }
    
    if (allValid) {
        console.log('   ✅ All required files present');
    } else {
        console.error('   ❌ Package validation failed');
        process.exit(1);
    }
}

function listPackageContents() {
    function listDir(dir, prefix = '') {
        const items = fs.readdirSync(dir);
        for (const item of items) {
            const itemPath = path.join(dir, item);
            if (fs.statSync(itemPath).isDirectory()) {
                console.log(`${prefix}📁 ${item}/`);
                listDir(itemPath, prefix + '  ');
            } else {
                const stats = fs.statSync(itemPath);
                const size = (stats.size / 1024).toFixed(1);
                console.log(`${prefix}📄 ${item} (${size}KB)`);
            }
        }
    }
    
    listDir(RELEASE_DIR, '   ');
} 