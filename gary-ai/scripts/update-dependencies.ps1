# Update Dependencies Script
Write-Host "🔄 Updating dependencies..." -ForegroundColor Cyan
docker-compose run --rm dev-tools composer update --no-dev --optimize-autoloader
docker-compose run --rm dev-tools npm audit fix
docker-compose run --rm dev-tools npm update
Write-Host "✅ Dependencies updated" -ForegroundColor Green
