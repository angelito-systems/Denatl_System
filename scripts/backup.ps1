<#
.SYNOPSIS
Script para realizar copias de seguridad de la base de datos MySQL en Docker.
#>

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFolder = ".\backups"
$backupFile = "$backupFolder\dental_system_$timestamp.sql"

if (-not (Test-Path -Path $backupFolder)) {
    New-Item -ItemType Directory -Path $backupFolder > $null
}

Write-Host "Iniciando volcado de la base de datos MySQL..." -ForegroundColor Yellow

# Obtenemos la password desde el env o asumimos 'root'
$password = "root" # Por defecto en docker-compose
if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $dbPassLine = $envContent | Where-Object { $_ -match "^DB_PASSWORD=(.*)" }
    if ($dbPassLine) {
        $password = $matches[1]
    }
}

try {
    # Ejecuta mysqldump directo desde el contenedor
    docker exec dental_system_mysql /usr/bin/mysqldump -u root -p"$password" system_dental > $backupFile
    Write-Host "Copia de seguridad completada exitosamente: $backupFile" -ForegroundColor Green
} catch {
    Write-Error "Fallo al realizar la copia de seguridad. Asegurate de que el contenedor de MySQL este corriendo."
}
