# Guía de Actualización - Sistema de Auditoría

Dado que tienes la aplicación desplegada en **Windows con Docker**, debes ejecutar los siguientes comandos para que los cambios del nuevo módulo de auditoría tengan efecto en tu entorno.

Abre tu terminal (PowerShell o CMD) en la carpeta raíz del proyecto (`c:\Proyectos\laravel\dental_system`) y ejecuta los siguientes comandos paso a paso.

## 1. Actualizar los contenedores (Opcional, si agregaste dependencias nuevas)
Si tus contenedores ya están corriendo, no es estrictamente necesario reiniciarlos a menos que cambies el `Dockerfile` o `docker-compose.yml`. Sin embargo, para asegurarte de que todo esté limpio:

```bash
docker compose down
docker compose up -d
```
*(Si usas Laravel Sail, reemplaza `docker compose` por `./vendor/bin/sail`)*

## 2. Ejecutar las nuevas migraciones (Base de datos)
Debemos crear la nueva tabla `audit_logs` en la base de datos de Docker. Ejecuta el comando de Artisan dentro del contenedor de PHP/App:

```bash
docker compose exec app php artisan migrate
```
*(Nota: Sustituye `app` por el nombre de tu servicio de Laravel si es distinto, por ejemplo `laravel.test` en Sail).*

## 3. Recompilar el Frontend (Svelte + Tailwind)
Como hemos creado nuevas vistas en `resources/js/pages/AuditLogs` y modificado el Sidebar, necesitamos que Vite empaquete estos cambios.

Instala las dependencias de Node.js (por si acaso) y ejecuta el build dentro del contenedor:

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

> **Alternativa si tienes Node.js en Windows (Recomendado por velocidad):**
> Puedes correr la compilación directamente en tu PC Windows sin entrar a Docker:
> ```bash
> npm install
> npm run build
> ```

## 4. Limpiar Cachés (Opcional pero Recomendado)
Para que Laravel reconozca las nuevas rutas y configuraciones inmediatamente:

```bash
docker compose exec app php artisan optimize:clear
```

---

¡Listo! Una vez finalizado el `npm run build`, puedes ingresar a tu sistema con la cuenta de **Administrador** y verás la opción **"Auditoría"** en el menú lateral.
