# Build Plugin Script
# ⚠️  WARNING: Do not create ZIP file until explicitly instructed by user
# ⚠️  This script should only build, not package into ZIP

Write-Host "📦 Building plugin..." -ForegroundColor Cyan
Write-Host "⚠️  WARNING: ZIP creation disabled until user explicitly requests it" -ForegroundColor Yellow
docker-compose run --rm dev-tools npm run build
Write-Host "✅ Plugin built (ZIP creation skipped per user preference)" -ForegroundColor Green
