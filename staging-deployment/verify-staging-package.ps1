# Gary AI Plugin - Staging Package Verification Script
# Verifies the staging deployment package is complete and ready

Write-Host "🔍 Verifying Gary AI Plugin Staging Package..." -ForegroundColor Green

$packagePath = "gary-ai-staging-v1.0.2.zip"
$extractPath = "temp-verify"

# Check if package exists
if (Test-Path $packagePath) {
    Write-Host "✅ Package found: $packagePath" -ForegroundColor Green
    
    # Get package size
    $size = (Get-Item $packagePath).Length
    $sizeKB = [math]::Round($size / 1KB, 1)
    Write-Host "📦 Package size: $sizeKB KB" -ForegroundColor Cyan
} else {
    Write-Host "❌ Package not found: $packagePath" -ForegroundColor Red
    exit 1
}

# Extract and verify contents
Write-Host "📂 Extracting package for verification..." -ForegroundColor Yellow
if (Test-Path $extractPath) { Remove-Item $extractPath -Recurse -Force }
Expand-Archive -Path $packagePath -DestinationPath $extractPath

# Critical files checklist
$criticalFiles = @(
    "gary-ai.php",
    "assets\css\chat-widget.css",
    "includes\class-contextual-ai-client.php",
    "templates\admin-datastores.php",
    "templates\admin-documents.php",
    "templates\admin-agents.php",
    "templates\admin-setup-wizard.php",
    "uninstall.php"
)

Write-Host "🔍 Verifying critical files..." -ForegroundColor Cyan
$allFilesPresent = $true

foreach ($file in $criticalFiles) {
    $fullPath = Join-Path $extractPath $file
    if (Test-Path $fullPath) {
        Write-Host "  ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $file (MISSING)" -ForegroundColor Red
        $allFilesPresent = $false
    }
}

# Check modern widget CSS
$widgetCSS = Join-Path $extractPath "assets\css\chat-widget.css"
if (Test-Path $widgetCSS) {
    $cssContent = Get-Content $widgetCSS -Raw
    if ($cssContent -match "morph.*animation" -and $cssContent -match "glassmorphism") {
        Write-Host "  ✅ Modern morphing widget CSS verified" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  Widget CSS may not contain modern morphing features" -ForegroundColor Yellow
    }
}

# Check plugin version
$mainFile = Join-Path $extractPath "gary-ai.php"
if (Test-Path $mainFile) {
    $pluginContent = Get-Content $mainFile -Raw
    if ($pluginContent -match "Version:\s*1\.0\.2") {
        Write-Host "  ✅ Plugin version 1.0.2 confirmed" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  Plugin version may not be 1.0.2" -ForegroundColor Yellow
    }
}

# Cleanup
Remove-Item $extractPath -Recurse -Force

# Final verification result
Write-Host "`n📋 Staging Package Verification Results:" -ForegroundColor Green
if ($allFilesPresent) {
    Write-Host "✅ All critical files present" -ForegroundColor Green
    Write-Host "✅ Package ready for staging deployment" -ForegroundColor Green
    Write-Host "`n🚀 Deploy to: https://staging.imisolutions.com/" -ForegroundColor Cyan
    Write-Host "📖 Instructions: See STAGING-DEPLOYMENT-GUIDE.md" -ForegroundColor Cyan
} else {
    Write-Host "❌ Some critical files are missing" -ForegroundColor Red
    Write-Host "⚠️  Package may not be complete" -ForegroundColor Yellow
}

Write-Host "`n🎯 Next Steps:" -ForegroundColor Green
Write-Host "1. Download gary-ai-staging-v1.0.2.zip" -ForegroundColor White
Write-Host "2. Upload to WordPress admin → Plugins → Add New → Upload" -ForegroundColor White
Write-Host "3. Activate plugin and configure API credentials" -ForegroundColor White
Write-Host "4. Test modern morphing chatbot widget" -ForegroundColor White
