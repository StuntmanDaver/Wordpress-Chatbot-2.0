# Gary AI Plugin Build Script
# This script creates a production-ready plugin package

param(
    [string]$Version = "1.0.0",
    [string]$OutputDir = ".\build\releases"
)

Write-Host "Building Gary AI Plugin v$Version..." -ForegroundColor Green

# Create build directories
$BuildDir = ".\build\gary-ai"
$ReleaseDir = "$OutputDir\gary-ai-v$Version"

if (Test-Path $BuildDir) {
    Remove-Item $BuildDir -Recurse -Force
}
if (Test-Path $ReleaseDir) {
    Remove-Item $ReleaseDir -Recurse -Force
}

New-Item -ItemType Directory -Path $BuildDir -Force | Out-Null
New-Item -ItemType Directory -Path $ReleaseDir -Force | Out-Null

Write-Host "Copying plugin files..." -ForegroundColor Yellow

# Define files and directories to include in the build
$IncludeItems = @(
    "gary-ai.php",
    "readme.txt",
    "includes\",
    "templates\",
    "assets\",
    "languages\",
    "docs\"
)

# Copy each item to build directory
foreach ($item in $IncludeItems) {
    $sourcePath = ".\$item"
    if (Test-Path $sourcePath) {
        if (Test-Path $sourcePath -PathType Container) {
            # It's a directory
            Copy-Item $sourcePath -Destination $BuildDir -Recurse -Force
            Write-Host "  Copied directory: $item" -ForegroundColor Gray
        } else {
            # It's a file
            Copy-Item $sourcePath -Destination $BuildDir -Force
            Write-Host "  Copied file: $item" -ForegroundColor Gray
        }
    } else {
        Write-Host "  Warning: $item not found, skipping..." -ForegroundColor Yellow
    }
}

# Update version in main plugin file
$mainPluginFile = "$BuildDir\gary-ai.php"
if (Test-Path $mainPluginFile) {
    $content = Get-Content $mainPluginFile -Raw
    $content = $content -replace "Version: [\d\.]+", "Version: $Version"
    $content = $content -replace "define\('GARY_AI_VERSION', '[^']+'\)", "define('GARY_AI_VERSION', '$Version')"
    Set-Content $mainPluginFile -Value $content
    Write-Host "  Updated version to $Version" -ForegroundColor Gray
}

# Create README for the build
$buildReadme = @"
# Gary AI WordPress Plugin v$Version
Build Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Installation Instructions

1. Upload the entire 'gary-ai' folder to your WordPress plugins directory:
   `/wp-content/plugins/gary-ai/`

2. Activate the plugin through the WordPress admin panel:
   Plugins > Installed Plugins > Gary AI > Activate

3. Configure the plugin:
   - Go to Gary AI > Settings in your WordPress admin
   - Enter your Contextual AI API key
   - Follow the Setup Wizard for guided configuration

## Features Included

- ✅ Datastore Management UI
- ✅ Document Upload Interface  
- ✅ Agent Management UI
- ✅ Setup Wizard
- ✅ Complete Admin Interface
- ✅ Security Features (CSRF protection, input sanitization)
- ✅ Contextual AI Integration

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Contextual AI API account

## Support

For support and documentation, visit the plugin settings page in your WordPress admin.
"@

Set-Content "$BuildDir\BUILD_README.md" -Value $buildReadme

# Create ZIP package
Write-Host "Creating ZIP package..." -ForegroundColor Yellow
$zipPath = "$ReleaseDir\gary-ai-v$Version.zip"

# Use PowerShell's Compress-Archive
Compress-Archive -Path "$BuildDir\*" -DestinationPath $zipPath -Force

# Copy unzipped version as well
Copy-Item $BuildDir -Destination "$ReleaseDir\gary-ai" -Recurse -Force

Write-Host "Build completed successfully!" -ForegroundColor Green
Write-Host "Package location: $zipPath" -ForegroundColor Cyan
Write-Host "Unzipped version: $ReleaseDir\gary-ai" -ForegroundColor Cyan

# Display build summary
Write-Host "`nBuild Summary:" -ForegroundColor Green
Write-Host "  Version: $Version" -ForegroundColor White
Write-Host "  Build Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor White
Write-Host "  Package Size: $([math]::Round((Get-Item $zipPath).Length / 1MB, 2)) MB" -ForegroundColor White
Write-Host "  Files Included: $((Get-ChildItem $BuildDir -Recurse -File).Count) files" -ForegroundColor White
