# Guía de Despliegue en Producción On-Premise (Windows)

Este documento contiene las instrucciones para operar la infraestructura Docker del proyecto Dental System en un entorno de red local empresarial usando una PC Windows como Servidor Principal.

## Arquitectura

El proyecto ha sido optimizado y dockerizado para Windows. Se ha consolidado el servicio principal de PHP mediante `Supervisor` para ahorrar recursos de RAM y CPU, ejecutando Nginx, MySQL, Redis y Evolution API como contenedores adjuntos.

## Requisitos Previos

1. **Docker Desktop** instalado y en ejecución en la máquina de Windows.
2. Permitir el tráfico en los puertos `80` y `8080` a través del Firewall de Windows (el script `install.ps1` realiza esto automáticamente).

## Instalación Iniciallko

Para realizar la instalación inicial de todo el sistema, abre **PowerShell como Administrador** y ejecuta:

```powershell
.\scripts\install.ps1
```

Este script se encarga automáticamente de:
- Copiar el `.env`.
- Configurar el Firewall de Windows.
- Construir las imágenes Docker (`npm run build` incluido).
- Levantar los servicios (`docker compose up -d`).
- Generar la Key, correr las Migraciones y optimizar cachés de Laravel.

## Acceso desde la Red Local

Los usuarios en otras computadoras de la oficina NO necesitan instalar nada. Solo deben abrir su navegador web e ingresar la IP local de la computadora servidor.

Para conocer la IP local, ejecuta en el PowerShell de tu servidor:
```powershell
ipconfig
```
Busca tu "Dirección IPv4" (ej. `192.168.1.100`).

**Accesos:**
- **Sistema Web:** `http://192.168.1.100`
- **Evolution API Websockets:** `ws://192.168.1.100:8080`

> **Importante:** Tu frontend Svelte utiliza WebSockets (`socket.io-client`). Asegúrate de que la variable `SERVER_URL` de Evolution en tu configuración apunte a la IP o dominio real si tienes problemas de conexión en tiempo real.

## Actualizaciones Automáticas

Cuando existan cambios en el código fuente, simplemente abre PowerShell y ejecuta:

```powershell
.\scripts\update.ps1
```

Esto descargará el código, reconstruirá las imágenes, reiniciará los servicios necesarios sin tiempo de inactividad prolongado y aplicará las migraciones.

## Copias de Seguridad (Backups)

La base de datos MySQL posee un volumen persistente gestionado por Docker (`mysql_data`). Sin embargo, para tener respaldos externos seguros (archivos `.sql`), ejecuta:

```powershell
.\scripts\backup.ps1
```

Esto generará un archivo `.sql` en la carpeta `backups/` con la fecha y hora actual.

### Restauración

Para restaurar una copia de seguridad en caso de desastre, utiliza el siguiente comando indicando el nombre de tu archivo `.sql`:

```powershell
cat .\backups\tu_archivo_backup.sql | docker exec -i dental_system_mysql /usr/bin/mysql -u root -proot system_dental
```

## Reinicio de Servicios / PC

Los contenedores en `docker-compose.yml` están configurados con `restart: always`. Esto significa que si **reinicias la computadora Windows**, o si reinicias Docker Desktop, **el sistema entero arrancará automáticamente** sin intervención humana en cuanto el demonio de Docker inicie.

## Generación de PDFs

La aplicación utiliza `spatie/laravel-pdf`. Este paquete requiere dependencias de Chrome/Puppeteer.
Nuestra imagen de Docker (`Dockerfile`) ya instala **Chromium y Node.js de forma nativa** dentro del contenedor Linux. Esto elimina la necesidad de depender de los ejecutables de Chrome de Windows, asegurando una compatibilidad perfecta en producción.
