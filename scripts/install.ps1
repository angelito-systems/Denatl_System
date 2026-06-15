<#
.SYNOPSIS
Script automatizado de instalación para Dental System en Docker Desktop (Windows).
Requiere ejecutarse como Administrador.
#>

param (
    [switch]$Force = $false
)

# Validar Administrador
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Warning "Este script debe ejecutarse como Administrador para configurar el Firewall y los Certificados SSL."
    Write-Warning "Cierra esta ventana, haz clic derecho en PowerShell y selecciona 'Ejecutar como administrador'."
    exit
}

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host "   INSTALACION DENTAL SYSTEM (ON-PREMISE WINDOWS)      " -ForegroundColor Cyan
Write-Host "=======================================================" -ForegroundColor Cyan

# 0. Detectar IP del servidor
$ip = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notmatch '^169\.' -and $_.IPAddress -notmatch '^127\.' -and $_.InterfaceAlias -notmatch 'vEthernet' -and $_.InterfaceAlias -notmatch 'Loopback' } | Select-Object -ExpandProperty IPAddress -First 1)
if ([string]::IsNullOrWhiteSpace($ip)) { $ip = $env:COMPUTERNAME }

# 1. Solicitar el dominio local al usuario
$LocalDomain = Read-Host "Ingrese el dominio local virtual (ejemplo: clisodent.test)"
if ([string]::IsNullOrWhiteSpace($LocalDomain)) {
    $LocalDomain = "clisodent.test"
}
Write-Host "Dominio seleccionado: $LocalDomain" -ForegroundColor Green

# 2. Copiar y configurar .env
if (-not (Test-Path ".env")) {
    Write-Host "[1/8] Creando archivo .env desde .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    
    # Actualizar APP_URL con el dominio local (Usando HTTPS)
    $envContent = Get-Content ".env"
    $envContent = $envContent -replace "^APP_URL=.*", "APP_URL=https://$LocalDomain"
    Set-Content -Path ".env" -Value $envContent
    
    Write-Host "Archivo .env creado y APP_URL configurado a https://$LocalDomain." -ForegroundColor Green
} else {
    Write-Host "[1/8] Archivo .env ya existe. Asegúrate de que APP_URL sea https://$LocalDomain" -ForegroundColor DarkGray
}

# 3. Configurar Hosts para dominio local
Write-Host "[2/8] Configurando dominio local ($LocalDomain) en archivo hosts..." -ForegroundColor Yellow
$hostsPath = "$env:windir\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsPath -Raw
if ($hostsContent -notmatch "(?m)^127\.0\.0\.1\s+$([regex]::Escape($LocalDomain))") {
    Add-Content -Path $hostsPath -Value "`r`n127.0.0.1`t$LocalDomain"
    Write-Host "Dominio $LocalDomain agregado al archivo hosts." -ForegroundColor Green
} else {
    Write-Host "El dominio $LocalDomain ya estaba configurado." -ForegroundColor DarkGray
}

# 4. Generar certificados SSL locales
Write-Host "[3/8] Verificando y generando certificados SSL locales..." -ForegroundColor Yellow
$sslDir = "$PWD\docker\nginx\ssl"
if (-not (Test-Path $sslDir)) {
    New-Item -ItemType Directory -Path $sslDir | Out-Null
}

$sslScript = @"
apk add --no-cache openssl
cd /ssl
if [ ! -f rootCA.key ]; then
    openssl genrsa -out rootCA.key 4096
    openssl req -x509 -new -nodes -key rootCA.key -sha256 -days 3650 -out rootCA.crt -subj "/C=PE/ST=Lima/L=Lima/O=DentalSystem/CN=DentalSystem Local Root CA"
fi
if [ ! -f server.key ]; then
    openssl genrsa -out server.key 2048
    openssl req -new -key server.key -out server.csr -subj "/C=PE/ST=Lima/L=Lima/O=DentalSystem/CN=$LocalDomain"
    echo "subjectAltName=DNS:$LocalDomain,IP:$ip" > extfile.cnf
    openssl x509 -req -in server.csr -CA rootCA.crt -CAkey rootCA.key -CAcreateserial -out server.crt -days 3650 -sha256 -extfile extfile.cnf
    rm server.csr extfile.cnf
fi
"@

# Usamos alpine openssl para generar los certificados sin depender de OpenSSL en Windows
docker run --rm -v "$($PWD.Path.Replace('\','/'))/docker/nginx/ssl:/ssl" alpine sh -c $sslScript

if (Test-Path "$sslDir\rootCA.crt") {
    $cert = Get-ChildItem "Cert:\LocalMachine\Root" | Where-Object { $_.Subject -match "DentalSystem Local Root CA" }
    if (-not $cert) {
        Write-Host "Instalando Certificado Raíz en Windows (puede que aparezca una confirmación de seguridad)..." -ForegroundColor Yellow
        Import-Certificate -FilePath "$sslDir\rootCA.crt" -CertStoreLocation "Cert:\LocalMachine\Root" > $null
        Write-Host "Certificado instalado correctamente en esta computadora." -ForegroundColor Green
    } else {
        Write-Host "El certificado Raíz ya estaba instalado localmente." -ForegroundColor DarkGray
    }
}

# 5. Configurar Firewall de Windows para los puertos 80, 443 y 8080
Write-Host "[4/8] Configurando reglas del Firewall de Windows..." -ForegroundColor Yellow
$ports = @{ "80" = "HTTP"; "443" = "HTTPS"; "8080" = "Evolution API" }
foreach ($port in $ports.Keys) {
    $name = "Dental System ($($ports[$port]) $port)"
    $rule = Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue
    if (-not $rule) {
        New-NetFirewallRule -DisplayName $name -Direction Inbound -LocalPort $port -Protocol TCP -Action Allow > $null
        Write-Host "Regla para el puerto $port creada." -ForegroundColor Green
    }
}

# 6. Levantar contenedores con Docker Compose
Write-Host "[5/8] Levantando infraestructura con Docker Compose..." -ForegroundColor Yellow
try {
    docker compose up -d --build
    if ($LASTEXITCODE -ne 0) {
        throw "Error al ejecutar Docker Compose (Código: $LASTEXITCODE)."
    }
    Write-Host "Contenedores creados e iniciados exitosamente." -ForegroundColor Green
} catch {
    Write-Error "Fallo al ejecutar Docker Compose. Detalle: $_"
    exit
}

# 7. Esperar a que la base de datos este lista
Write-Host "[6/8] Esperando que la base de datos y los contenedores esten listos (15s)..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

# 8. Generar Key, Migrar DB y Limpiar Cache
Write-Host "[7/8] Preparando Laravel (Generacion de Key, Migraciones, Cache)..." -ForegroundColor Yellow
docker exec dental_system_app /usr/local/bin/php artisan key:generate --force
docker exec dental_system_app /usr/local/bin/php artisan migrate --force
docker exec dental_system_app /usr/local/bin/php artisan storage:link
docker exec dental_system_app /usr/local/bin/php artisan config:cache
docker exec dental_system_app /usr/local/bin/php artisan route:cache
docker exec dental_system_app /usr/local/bin/php artisan view:cache
Write-Host "Laravel configurado exitosamente." -ForegroundColor Green

# 9. Informacion Final
Write-Host "[8/8] Informacion Final" -ForegroundColor Yellow
Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host " ¡INSTALACION COMPLETADA EXITOSAMENTE! " -ForegroundColor Green
Write-Host " "
Write-Host " Acceso seguro local : https://$LocalDomain" -ForegroundColor White
Write-Host " Acceso desde otras computadoras (sin script): https://$ip" -ForegroundColor White
Write-Host " "
Write-Host " Script de cliente generado en: scripts\conectar_cliente.ps1" -ForegroundColor Yellow
Write-Host " Copia este script en un USB y ejecutalo en las otras computadoras de la clinica." -ForegroundColor Yellow
Write-Host "=======================================================" -ForegroundColor Cyan
