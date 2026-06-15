<#
.SYNOPSIS
Script automatizado de actualizacion para Dental System en Docker Desktop.
#>

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host "   ACTUALIZACION DENTAL SYSTEM (ON-PREMISE)            " -ForegroundColor Cyan
Write-Host "=======================================================" -ForegroundColor Cyan

Write-Host "[1/4] Obteniendo ultimos cambios del repositorio (git pull)..." -ForegroundColor Yellow
git pull origin main

Write-Host "[2/4] Reconstruyendo imagenes de Docker..." -ForegroundColor Yellow
docker compose build app

Write-Host "[3/4] Reiniciando contenedores con la nueva version..." -ForegroundColor Yellow
docker compose up -d

Write-Host "[4/4] Ejecutando migraciones y limpiando cache..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
docker exec dental_system_app /usr/local/bin/php artisan migrate --force
docker exec dental_system_app /usr/local/bin/php artisan cache:clear
docker exec dental_system_app /usr/local/bin/php artisan config:cache
docker exec dental_system_app /usr/local/bin/php artisan route:cache
docker exec dental_system_app /usr/local/bin/php artisan view:cache

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host " ¡ACTUALIZACION COMPLETADA EXITOSAMENTE! " -ForegroundColor Green
Write-Host "=======================================================" -ForegroundColor Cyan
