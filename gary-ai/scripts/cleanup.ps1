# Cleanup Script
Write-Host "🧹 Cleaning up Docker resources..." -ForegroundColor Cyan
docker-compose down -v
docker system prune -f
Write-Host "✅ Cleanup completed" -ForegroundColor Green
