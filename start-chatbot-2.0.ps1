# WordPress Chatbot 2.0 Startup Script
# This script deploys the Gary AI plugin in a fresh Docker environment

Write-Host "🚀 Starting WordPress Chatbot 2.0 Deployment..." -ForegroundColor Green

# Stop any existing containers
Write-Host "📦 Stopping existing containers..." -ForegroundColor Yellow
docker-compose -f docker-compose-chatbot-2.0.yml down --volumes --remove-orphans

# Clean up any conflicting containers
Write-Host "🧹 Cleaning up conflicting containers..." -ForegroundColor Yellow
docker container prune -f
docker volume prune -f

# Start the new WordPress Chatbot 2.0 environment
Write-Host "🔄 Starting WordPress Chatbot 2.0 containers..." -ForegroundColor Green
docker-compose -f docker-compose-chatbot-2.0.yml up -d

# Wait for services to initialize
Write-Host "⏳ Waiting for services to initialize (60 seconds)..." -ForegroundColor Yellow
Start-Sleep -Seconds 60

# Check container status
Write-Host "📊 Checking container status..." -ForegroundColor Cyan
docker-compose -f docker-compose-chatbot-2.0.yml ps

# Check if WordPress is accessible
Write-Host "🌐 Testing WordPress accessibility..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080" -TimeoutSec 10 -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ WordPress is accessible on port 8080!" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  WordPress not yet accessible, may need more time to initialize" -ForegroundColor Yellow
}

# Display access information
Write-Host "`n🎯 WordPress Chatbot 2.0 Access Information:" -ForegroundColor Green
Write-Host "   WordPress:   http://localhost:8080" -ForegroundColor White
Write-Host "   Admin:       http://localhost:8080/wp-admin" -ForegroundColor White
Write-Host "   phpMyAdmin:  http://localhost:8081" -ForegroundColor White
Write-Host "   MailHog:     http://localhost:8025" -ForegroundColor White

Write-Host "`n🔧 Container Names:" -ForegroundColor Green
Write-Host "   WordPress:   wordpress-chatbot-2-0" -ForegroundColor White
Write-Host "   MySQL:       mysql-chatbot-2-0" -ForegroundColor White
Write-Host "   phpMyAdmin:  phpmyadmin-chatbot-2-0" -ForegroundColor White
Write-Host "   MailHog:     mailhog-chatbot-2-0" -ForegroundColor White

Write-Host "`n📋 Next Steps:" -ForegroundColor Green
Write-Host "   1. Access WordPress at http://localhost:8080" -ForegroundColor White
Write-Host "   2. Complete WordPress setup" -ForegroundColor White
Write-Host "   3. Activate Gary AI plugin" -ForegroundColor White
Write-Host "   4. Configure with Contextual AI credentials" -ForegroundColor White

Write-Host "`n🎉 WordPress Chatbot 2.0 deployment complete!" -ForegroundColor Green
