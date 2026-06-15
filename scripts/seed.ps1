# scripts/seed.ps1
Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host "   EJECUCION DE SEEDERS DENTAL SYSTEM" -ForegroundColor Cyan
Write-Host "=======================================================" -ForegroundColor Cyan

Write-Host "Poblando la base de datos con datos de prueba/iniciales..." -ForegroundColor Yellow

try {
    # Ejecutamos el comando artisan db:seed dentro del contenedor de la aplicacion
    docker exec dental_system_app /usr/local/bin/php artisan db:seed --force
    
    if ($LASTEXITCODE -ne 0) {
        throw "Error al ejecutar los seeders (Código: $LASTEXITCODE)."
    }
    
    Write-Host " "
    Write-Host "¡Seeders ejecutados correctamente! Base de datos poblada." -ForegroundColor Green
} catch {
    Write-Error "Fallo al ejecutar los seeders de la base de datos."
    Write-Error "Asegúrate de que los contenedores estén encendidos (ejecuta .\scripts\install.ps1 o docker compose up -d)."
    Write-Error "Detalle: $_"
}

Write-Host "=======================================================" -ForegroundColor Cyan
