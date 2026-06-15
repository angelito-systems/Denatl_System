<#
.SYNOPSIS
Script automatizado para configurar computadoras cliente en Dental System.
Requiere ejecutarse como Administrador.
#>

# Validar Administrador
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Warning "Este script debe ejecutarse como Administrador para instalar el certificado de seguridad."
    Write-Warning "Cierra esta ventana, haz clic derecho en PowerShell y selecciona 'Ejecutar como administrador'."
    exit
}

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host "   CONECTAR CLIENTE A DENTAL SYSTEM (ON-PREMISE)       " -ForegroundColor Cyan
Write-Host "=======================================================" -ForegroundColor Cyan

# 1. Solicitar IP del Servidor
$ServerIP = Read-Host "1. Ingrese la IP del Servidor Central (ejemplo: 10.77.180.99)"
if ([string]::IsNullOrWhiteSpace($ServerIP)) {
    Write-Error "La IP del servidor es obligatoria."
    exit
}

# 2. Solicitar Dominio Local
$LocalDomain = Read-Host "2. Ingrese el dominio virtual que se configuro en el servidor (ejemplo: clisodent.test)"
if ([string]::IsNullOrWhiteSpace($LocalDomain)) {
    $LocalDomain = "clisodent.test"
}

# 3. Descargar Certificado Raiz
Write-Host "Conectando con el servidor en $ServerIP para descargar certificado..." -ForegroundColor Yellow
$certUrl = "http://$ServerIP/download-cert"
$certPath = "$env:TEMP\DentalSystemRootCA.crt"

try {
    Invoke-WebRequest -Uri $certUrl -OutFile $certPath -UseBasicParsing
    Write-Host "Certificado descargado con exito." -ForegroundColor Green
} catch {
    Write-Error "Fallo al descargar el certificado. Asegurate de que el servidor este encendido y la IP sea correcta."
    Write-Error "Detalle: $_"
    exit
}

# 4. Instalar Certificado Raiz en Windows
Write-Host "Instalando certificado en Windows (es normal si ves un aviso de seguridad)..." -ForegroundColor Yellow
try {
    Import-Certificate -FilePath $certPath -CertStoreLocation "Cert:\LocalMachine\Root" > $null
    Write-Host "Certificado instalado correctamente. Google Chrome / Edge ahora confiaran en el sitio." -ForegroundColor Green
} catch {
    Write-Error "Fallo al instalar el certificado. $_"
}

# 5. Configurar Hosts para el dominio
Write-Host "Configurando el archivo hosts..." -ForegroundColor Yellow
$hostsPath = "$env:windir\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsPath -Raw

# Remover configuración anterior si existiera para el mismo dominio
if ($hostsContent -match "(?m)^.*$([regex]::Escape($LocalDomain)).*`r`n?") {
    $hostsContent = $hostsContent -replace "(?m)^.*$([regex]::Escape($LocalDomain)).*`r`n?", ""
    Set-Content -Path $hostsPath -Value $hostsContent
}

# Agregar nueva configuración
Add-Content -Path $hostsPath -Value "`r`n$ServerIP`t$LocalDomain"
Write-Host "Dominio $LocalDomain apuntado a $ServerIP correctamente." -ForegroundColor Green

# 6. Limpiar Cache DNS
ipconfig /flushdns > $null

Write-Host "=======================================================" -ForegroundColor Cyan
Write-Host " ¡COMPUTADORA CONFIGURADA EXITOSAMENTE! " -ForegroundColor Green
Write-Host " "
Write-Host " Ya puedes abrir tu navegador e ingresar a:" -ForegroundColor White
Write-Host " 👉 https://$LocalDomain" -ForegroundColor Yellow
Write-Host " "
Write-Host " (Recuerda cerrar y volver a abrir tu navegador para que reconozca el certificado)" -ForegroundColor DarkGray
Write-Host "=======================================================" -ForegroundColor Cyan
