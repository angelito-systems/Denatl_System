# PROMPT MAESTRO — DentalSystem · Laravel 11 API + SvelteKit + TailwindCSS v4

---

## ROL Y CONTEXTO

Eres un desarrollador senior full-stack. Tu tarea es construir **DentalSystem**, un sistema de gestión para clínicas dentales que corre **100% en el navegador** (web app SPA). Arquitectura desacoplada:

- **Backend**: Laravel 11 como API REST pura (JSON) con Sanctum para auth
- **Frontend**: SvelteKit con SSR desactivado (modo SPA) + TailwindCSS v4 + shadcn-svelte

El sistema se accede desde cualquier navegador. No hay Electron ni escritorio.

---

## STACK TECNOLÓGICO OBLIGATORIO

### Backend (Laravel 11 — API REST)
- **Laravel 11** — API pura, sin Blade, sin Livewire, sin Inertia
- **Laravel Sanctum** — autenticación SPA con tokens CSRF + cookies
- **Spatie Laravel Permission** — roles y permisos granulares
- **Spatie Laravel Media Library** — archivos, logos, PDFs, imágenes
- **Spatie Laravel PDF** (DomPDF) — generación de PDFs server-side
- **Laravel Excel** (Maatwebsite) — exportación a Excel
- **Laravel Horizon** — colas para WhatsApp y notificaciones async
- **MySQL 8** — base de datos principal
- Estructura: `/api/v1/...` para todos los endpoints

### Frontend (SvelteKit — SPA)
- **SvelteKit** con `adapter-static` o `adapter-node` en modo SPA
- **TailwindCSS v4** con `@import "tailwindcss"` (sin config file, nuevo sistema)
- **shadcn-svelte** — componentes UI base (Dialog, Table, Select, Toast, etc.)
- **Svelte 5** con runas: `$state`, `$derived`, `$effect`, `$props`
- **TanStack Query para Svelte** (`@tanstack/svelte-query`) — fetching, cache, mutaciones
- **svelte-french-toast** o **Sonner** — notificaciones toast
- **Chart.js** con `svelte-chartjs` — gráficos del dashboard
- **FullCalendar** (`@fullcalendar/svelte`) — calendario de citas
- **svelte-dnd-action** — drag & drop en odontograma
- **QZ Tray** — librería JS directa para impresión térmica silenciosa
- **Lucide Svelte** — íconos

### Comunicación Frontend ↔ Backend
- Axios o `fetch` nativo con interceptores
- Variables de entorno con `PUBLIC_API_URL` en `.env`
- CORS configurado en Laravel para el dominio del frontend
- Token CSRF de Sanctum en cada request

---

## ESTRUCTURA DEL PROYECTO

```
/dental-system/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   │   ├── AuthController.php
│   │   │   ├── PacienteController.php
│   │   │   ├── CitaController.php
│   │   │   ├── PagoController.php
│   │   │   ├── TratamientoController.php
│   │   │   ├── StaffController.php
│   │   │   ├── ReporteController.php
│   │   │   ├── MensajeController.php
│   │   │   └── ConfiguracionController.php
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── ReniecService.php
│   │   │   ├── SunatService.php
│   │   │   ├── WhatsAppService.php
│   │   │   └── PdfGeneratorService.php
│   │   ├── Jobs/
│   │   │   ├── EnviarRecordatorioCita.php
│   │   │   └── EnviarMensajeWhatsApp.php
│   │   └── Policies/
│   └── routes/api.php
│
└── frontend/                   # SvelteKit SPA
    ├── src/
    │   ├── lib/
    │   │   ├── api/             # Módulos de llamadas API
    │   │   │   ├── auth.ts
    │   │   │   ├── pacientes.ts
    │   │   │   ├── citas.ts
    │   │   │   └── ...
    │   │   ├── components/
    │   │   │   ├── ui/          # shadcn-svelte
    │   │   │   ├── layout/
    │   │   │   │   ├── Sidebar.svelte
    │   │   │   │   └── Header.svelte
    │   │   │   ├── dashboard/
    │   │   │   ├── pacientes/
    │   │   │   ├── odontograma/
    │   │   │   └── ...
    │   │   ├── stores/          # Svelte 5 $state stores
    │   │   │   ├── auth.svelte.ts
    │   │   │   └── config.svelte.ts
    │   │   └── utils/
    │   │       ├── qztray.ts    # QZ Tray wrapper
    │   │       └── formatters.ts
    │   ├── routes/
    │   │   ├── +layout.svelte   # Layout principal con auth guard
    │   │   ├── login/
    │   │   │   └── +page.svelte
    │   │   ├── (app)/           # Rutas protegidas
    │   │   │   ├── +layout.svelte  # Sidebar + Header
    │   │   │   ├── dashboard/
    │   │   │   ├── pacientes/
    │   │   │   │   ├── +page.svelte
    │   │   │   │   └── [id]/
    │   │   │   │       └── +page.svelte
    │   │   │   ├── citas/
    │   │   │   ├── facturacion/
    │   │   │   ├── mensajes/
    │   │   │   ├── reportes/
    │   │   │   ├── staff/
    │   │   │   └── configuracion/
    │   └── app.css              # @import "tailwindcss"; + CSS vars
```

---

## TAILWINDCSS V4 — REGLAS OBLIGATORIAS

```css
/* src/app.css — nuevo sistema TailwindCSS v4 */
@import "tailwindcss";

/* Tema con CSS custom properties (no tailwind.config.js) */
@theme {
  --color-dental-navy: #0F172A;
  --color-dental-navy-light: #1E293B;
  --color-dental-blue: #2563EB;
  --color-dental-red: #DC2626;
  --color-dental-green: #16A34A;
  --color-dental-violet: #7C3AED;
  --color-dental-sidebar-text: #94A3B8;
  --color-dental-sidebar-active: #FFFFFF;

  --font-sans: 'Inter', system-ui, sans-serif;
  --radius: 0.5rem;
}
```

**En TailwindCSS v4:**
- NO existe `tailwind.config.js` — todo en CSS con `@theme {}`
- Usar `@utility` para utilities personalizadas
- Clases siguen siendo las mismas: `bg-dental-navy`, `text-dental-blue`, etc.
- Instalar: `npm install tailwindcss@next @tailwindcss/vite`
- Vite plugin: `import tailwindcss from '@tailwindcss/vite'`

---

## SVELTE 5 — PATRONES OBLIGATORIOS

```svelte
<!-- Usar Svelte 5 Runes, NO Svelte 4 syntax -->

<!-- STORE con $state -->
<!-- src/lib/stores/auth.svelte.ts -->
export const authStore = $state({
  user: null,
  token: null,
  isAuthenticated: false,
});

<!-- COMPONENTE con Svelte 5 -->
<script lang="ts">
  // Props con $props()
  let { paciente, onSave } = $props<{
    paciente: Paciente;
    onSave: (data: Paciente) => void;
  }>();

  // Estado local con $state
  let nombre = $state(paciente.nombres);
  let loading = $state(false);

  // Derivado con $derived
  let nombreCompleto = $derived(`${nombre} ${paciente.apellidos}`);

  // Efectos con $effect
  $effect(() => {
    console.log('nombre cambió:', nombre);
  });
</script>

<!-- Eventos con on:click o sintaxis nueva -->
<button onclick={() => handleSave()}>Guardar</button>
```

---

## MÓDULO 1 — AUTH + ROLES (Spatie Permission)

### Laravel Backend:
```php
// Roles
['Administrador', 'Dentista', 'Recepcionista', 'Asistente']

// Permisos granulares:
$permisos = [
  'ver_dashboard', 'ver_pacientes', 'crear_pacientes',
  'editar_pacientes', 'eliminar_pacientes',
  'ver_citas', 'crear_citas', 'editar_citas', 'cancelar_citas',
  'ver_facturacion', 'crear_pagos', 'ver_reportes',
  'ver_staff', 'crear_staff', 'editar_staff',
  'ver_configuracion', 'editar_configuracion',
  'ver_contratos', 'crear_contratos',
  'ver_mensajes_crm', 'enviar_mensajes',
  'imprimir_tickets',
];

// AuthController@login → devuelve user + permisos + token
return response()->json([
  'user' => $user,
  'token' => $token,
  'permissions' => $user->getAllPermissions()->pluck('name'),
  'roles' => $user->getRoleNames(),
]);
```

### SvelteKit Frontend:
```typescript
// src/lib/stores/auth.svelte.ts
export const auth = $state({
  user: null as User | null,
  permissions: [] as string[],
  roles: [] as string[],
});

// Helper para verificar permisos
export function can(permission: string): boolean {
  return auth.permissions.includes(permission);
}

// Guard en +layout.svelte de rutas protegidas
$effect(() => {
  if (!auth.user) goto('/login');
});
```

### Login Page:
- Diseño centrado, tarjeta blanca, logo dental
- Campos: Username + Password (no email)
- Sin "recordar sesión" visible — Sanctum maneja la sesión
- Error inline si credenciales incorrectas

---

## MÓDULO 2 — LAYOUT PRINCIPAL

### Sidebar.svelte:
```svelte
<!-- Sidebar fijo izquierdo 240px -->
<!-- Fondo: bg-dental-navy (#0F172A) -->
<!-- Logo: ícono diente + "DentalSystem" -->

<!-- Secciones de menú: -->
<!-- MENÚ: Dashboard, Pacientes, Citas, Mensajes (CRM) -->
<!-- ANÁLISIS: Facturación, Reportes -->
<!-- SISTEMA: Staff, Configuración -->

<!-- Item activo: fondo azul, borde izquierdo blanco 3px -->
<!-- Colapsar: botón << en footer del sidebar -->
<!-- Avatar usuario + nombre + rol en footer -->
```

### Header.svelte:
```svelte
<!-- Header fijo top, altura 60px -->
<!-- Fecha y hora en tiempo real (setInterval cada segundo) -->
<!-- Ícono monitor + campana (badge con notificaciones) -->
<!-- Nombre usuario -->
<!-- Botón "Cerrar Sesión" rojo -->
```

---

## MÓDULO 3 — DASHBOARD

### Componentes:

**KpiCard.svelte** — recibe `{titulo, valor, subtexto, color}`:
- Borde izquierdo colorido (azul/verde/violeta/rojo)
- Valor grande, subtexto pequeño
- 4 tarjetas en grid `grid-cols-4`

**CitasHoy.svelte**:
- Lista de citas del día desde `/api/v1/citas?fecha=hoy`
- Avatar con iniciales, nombre, tratamiento, hora en badge azul
- TanStack Query con `refetchInterval: 30000`

**PagosPendientes.svelte**:
- Lista de deudas pendientes desde `/api/v1/pagos?estado=pendiente`

**GraficoIngresos.svelte** (Chart.js):
- Tipo: `bar` o `line` con relleno degradado
- Data: últimos 6 meses desde `/api/v1/reportes/ingresos-mensuales`
- Labels en español abreviado: Ene, Feb, Mar...

**GraficoCitas.svelte** (Chart.js):
- Tipo: `doughnut`
- Estados: Pendiente, Confirmada, Atendida, Cancelada, No asistió

**TratamientosMasSolicitados.svelte** (Chart.js):
- Tipo: `bar` horizontal
- Top 5 tratamientos

---

## MÓDULO 4 — PACIENTES

### /pacientes — DirectorioPacientes.svelte:
```svelte
<script lang="ts">
  let busqueda = $state('');
  let pacientesSeleccionados = $state<number[]>([]);

  // TanStack Query
  const pacientesQuery = createQuery({
    queryKey: ['pacientes', busqueda],
    queryFn: () => api.pacientes.listar({ search: busqueda }),
  });
</script>

<!-- Tabla con shadcn-svelte Table -->
<!-- Columnas: #, Documento, Paciente, Teléfono, Email, Acción -->
<!-- Buscador con debounce 300ms -->
<!-- Doble clic en fila → navigate(`/pacientes/${id}`) -->
<!-- Botones: Perfil 360, Eliminar (con confirm dialog), + Nuevo Paciente -->
<!-- Footer: "Total en directorio: X paciente(s)" -->
```

### /pacientes/nuevo — FormNuevoPaciente.svelte:
- **Autocompletar DNI**: input de 8 dígitos → `GET /api/v1/reniec/{dni}` → rellena nombres/apellidos
- Indicador de carga mientras consulta RENIEC
- Calcular edad automáticamente desde fecha de nacimiento
- Secciones: Datos Personales | Contacto | Datos Médicos | Contacto Emergencia

### /pacientes/[id] — PerfilPaciente.svelte:
```svelte
<!-- Sidebar izquierdo con tabs verticales usando shadcn Tabs -->
<Tabs orientation="vertical">
  <TabsList>
    <TabsTrigger value="general">👤 Información General</TabsTrigger>
    <TabsTrigger value="anamnesis">📋 Anamnesis</TabsTrigger>
    <TabsTrigger value="historia">📖 Historia Clínica</TabsTrigger>
    <TabsTrigger value="odontograma">🦷 Odontograma</TabsTrigger>
    <TabsTrigger value="tratamientos">💊 Tratamientos y Finanzas</TabsTrigger>
    <TabsTrigger value="contratos">📄 Contratos y Doc.</TabsTrigger>
    <TabsTrigger value="examenes">🔬 Exámenes Auxiliares</TabsTrigger>
  </TabsList>
  <!-- Cada TabsContent con su componente -->
</Tabs>
```

#### Odontograma.svelte (SVG interactivo):
```svelte
<!-- Notación FDI: cuadrantes 1-4 -->
<!-- Adulto: 32 piezas (16 superiores + 16 inferiores) -->
<!-- Cada diente es un <g> SVG clickeable -->
<!-- Estados con colores: -->
<!--   sano: #22C55E (verde) -->
<!--   caries: #EF4444 (rojo) -->
<!--   extraído: #6B7280 (gris) -->
<!--   corona: #F59E0B (amarillo) -->
<!--   implante: #3B82F6 (azul) -->
<!--   endodoncia: #8B5CF6 (violeta) -->
<!--   obturación: #06B6D4 (cyan) -->

<!-- Click en diente → Dialog con: -->
<!-- - Número de pieza -->
<!-- - Select de estado -->
<!-- - Textarea de notas -->
<!-- - Botón Guardar → PUT /api/v1/pacientes/{id}/odontograma/{pieza} -->
```

#### TratamientosFinanzas.svelte:
```svelte
<!-- Tabla de plan de tratamientos -->
<!-- Columnas: Tratamiento, Costo, Pagado, Pendiente, Estado, Acciones -->
<!-- Sección balance: Total | Pagado | Pendiente (tarjetas) -->
<!-- Modal "Registrar Pago": monto, método (Efectivo/Tarjeta/Yape/Transferencia), -->
<!--   tipo comprobante (Boleta/Factura/Recibo), notas -->
<!-- Historial de pagos en tabla inferior -->
<!-- Botón "Imprimir Ticket" → QZ Tray ESC/POS -->
<!-- Botón "Generar Comprobante PDF" → GET /api/v1/pagos/{id}/pdf → download -->
```

#### ContratosDocumentos.svelte:
```svelte
<!-- Lista de contratos en tarjetas -->
<!-- Botón "Generar Contrato" → POST /api/v1/contratos → devuelve PDF → auto-download -->
<!-- Botón "Subir Documento" → upload con drag&drop -->
<!-- Vista previa inline del PDF con <iframe> o PDF.js -->
<!-- Spatie Media Library gestiona los archivos en backend -->
```

---

## MÓDULO 5 — CITAS

### CalendarioCitas.svelte:
```svelte
<script lang="ts">
  import FullCalendar from '@fullcalendar/svelte';
  import dayGridPlugin from '@fullcalendar/daygrid';
  import timeGridPlugin from '@fullcalendar/timegrid';
  import interactionPlugin from '@fullcalendar/interaction';

  // Colores por estado:
  const coloresCita = {
    pendiente: '#F59E0B',
    confirmada: '#22C55E',
    atendida: '#3B82F6',
    cancelada: '#EF4444',
    no_asistio: '#6B7280',
  };
</script>

<!-- Vista: mes/semana/día con botones de cambio -->
<!-- Click en evento → Dialog con detalle de cita -->
<!-- Click en espacio vacío → Dialog nueva cita -->
<!-- Drag & drop para reprogramar -->
```

### DialogNuevaCita.svelte:
- Paciente: Combobox con búsqueda en tiempo real
- Dentista: Select filtrado por rol Dentista
- Fecha, hora inicio, duración
- Tratamiento (de catálogo)
- Notas
- Al guardar: `POST /api/v1/citas` → refetch del calendario

### Acciones en cita:
- **Confirmar** → PATCH + envía WhatsApp via Job
- **Atender** → PATCH + navega a perfil del paciente tab Historia
- **Cancelar** → Dialog con campo motivo
- **Reprogramar** → Dialog con nuevo fecha/hora

---

## MÓDULO 6 — FACTURACIÓN

### CatalogoTratamientos.svelte:
- CRUD completo con tabla + modal
- Campos: Nombre, Categoría (select), Precio base, Duración estimada (min)
- Botón importar desde Excel / exportar

### RegistroPagos.svelte:
- Filtros: rango fechas, paciente, estado, método
- Tabla paginada con TanStack Query
- Total del período en footer
- Botón "Exportar Excel" → `GET /api/v1/pagos/export`

---

## MÓDULO 7 — QZ TRAY (Impresión Térmica)

### src/lib/utils/qztray.ts:
```typescript
import qz from 'qz-tray';

export class QZTrayService {
  private static connected = false;

  // Conectar con certificado firmado
  static async connect(certPublico: string, firmarFn: Function) {
    qz.security.setCertificatePromise((resolve) => resolve(certPublico));
    qz.security.setSignatureAlgorithm('SHA512');
    qz.security.setSignaturePromise((toSign) => firmarFn(toSign));
    await qz.websocket.connect();
    this.connected = true;
  }

  // Obtener lista de impresoras
  static async getPrinters(): Promise<string[]> {
    return await qz.printers.find();
  }

  // Imprimir ticket ESC/POS
  static async imprimirTicket(impresora: string, datos: TicketData) {
    const config = qz.configs.create(impresora);
    const escData = [
      '\x1B\x40',                    // Init
      '\x1B\x61\x01',                // Centro
      `${datos.clinicaNombre}\n`,
      `RUC: ${datos.clinicaRuc}\n`,
      `Tel: ${datos.clinicaTel}\n`,
      `${datos.fecha}\n`,
      '================================\n',
      '\x1B\x61\x00',                // Izquierda
      `Paciente: ${datos.pacienteNombre}\n`,
      `DNI: ${datos.pacienteDni}\n`,
      '--------------------------------\n',
      ...datos.items.map(i => `${i.nombre.padEnd(20)} S/ ${i.precio.toFixed(2)}\n`),
      '================================\n',
      '\x1B\x45\x01',                // Bold ON
      `TOTAL: S/ ${datos.total.toFixed(2)}\n`,
      '\x1B\x45\x00',                // Bold OFF
      `Método: ${datos.metodoPago}\n`,
      '\x1B\x61\x01',
      `${datos.mensajePie}\n`,
      '\x1B\x64\x05',                // Feed 5 líneas
      '\x1D\x56\x41',                // Cortar papel
    ];
    await qz.print(config, escData);
  }

  // Verificar conexión
  static isConnected(): boolean {
    return qz.websocket.isActive();
  }
}
```

---

## MÓDULO 8 — MENSAJES CRM (WhatsApp + Evolution API)

### Laravel Backend — WhatsAppService.php:
```php
class WhatsAppService {
  private string $apiUrl;
  private string $apiKey;
  private string $instance;

  public function enviarMensaje(string $telefono, string $mensaje): bool {
    // POST {apiUrl}/message/sendText/{instance}
    // Headers: apikey: {apiKey}
    // Body: { number: telefono, text: mensaje }
  }

  public function enviarRecordatorioCita(Cita $cita): void {
    $mensaje = "Recordatorio: Tu cita en {$clinica} es el {$cita->fecha} a las {$cita->hora}. Tratamiento: {$cita->tratamiento}.";
    $this->enviarMensaje($cita->paciente->telefono, $mensaje);
  }
}
```

### Laravel Backend — Job:
```php
// Jobs/EnviarRecordatorioCita.php
// Dispatch con delay: ahora() + horasAnticipacion horas
// Scheduler en Kernel: cada minuto revisa citas próximas
```

### SvelteKit — MensajesCRM.svelte:
```svelte
<!-- Layout tipo WhatsApp Web -->
<!-- Columna izquierda: lista de conversaciones con último mensaje, hora, unread badge -->
<!-- Columna derecha: chat con historial, input de mensaje, adjuntar archivos -->
<!-- Templates de mensajes rápidos (botones predefinidos) -->
<!-- Indicador de estado del bot: activo/pausado por conversación -->
```

### ChatbotConfig.svelte (en Configuración):
```svelte
<!-- Textarea editable: System Prompt del bot -->
<!-- Default: "Rol: Eres el asistente virtual oficial de la Clínica... -->
<!--           Objetivo Principal: Asistir a los pacientes con la gestión de sus citas." -->
<!-- Botón "Guardar" → PATCH /api/v1/configuracion/whatsapp-prompt -->
```

---

## MÓDULO 9 — REPORTES

### Tabs en /reportes:
1. **Resumen General** — 6 KPI cards en 2 filas × 3 columnas
2. **Gráficos** — Ingresos por mes (bar), Tratamientos por categoría (pie), Citas por dentista (bar horizontal)
3. **Citas** — Estado de citas en progress bars + tabla filtrable por rango de fechas
4. **Pacientes** — Nuevos por mes, distribución por sexo/edad
5. **Pagos** — Por método de pago, total cobrado vs pendiente

### Exportación:
```svelte
<!-- Botón "Exportar PDF" → GET /api/v1/reportes/pdf?tab=citas&desde=...&hasta=... -->
<!-- Botón "Exportar Excel" → GET /api/v1/reportes/excel?... -->
<!-- Ambos hacen download automático del blob response -->

async function exportarPDF() {
  const blob = await api.get('/reportes/pdf', { responseType: 'blob' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'reporte.pdf'; a.click();
}
```

---

## MÓDULO 10 — STAFF

### /staff — GestionStaff.svelte:
```svelte
<!-- Tabla: ID, Nombre Completo, Username, Email, Rol (badge), Estado (badge) -->
<!-- Badge colores: Administrador=violeta, Dentista=azul, Recepcionista=naranja, Asistente=gris -->
<!-- Estado: Activo=verde, Inactivo=rojo -->
<!-- Doble clic en fila → abre Dialog de edición -->
<!-- Botón "+ Nuevo usuario" → Dialog de creación -->
```

### DialogUsuario.svelte:
- Campos: Nombres, Apellidos, Correo, Username, Contraseña (con toggle ver/ocultar), Rol (Select), Activo (Checkbox)
- Validación en tiempo real con Superforms + Zod
- Al guardar: `POST /api/v1/staff` o `PUT /api/v1/staff/{id}`

---

## MÓDULO 11 — CONFIGURACIÓN DEL SISTEMA

### /configuracion — tabs verticales:

#### Tab: Datos de la Clínica
```svelte
<!-- Nombre clínica, RUC, Teléfono, Mensaje pie del ticket -->
<!-- Logo: upload con preview inmediato (FileReader API) -->
<!-- Ruta almacenamiento (solo informativa en web, configurable en backend .env) -->
<!-- Botón "Diseñar Plantilla PDF" → navega a /configuracion/plantilla-pdf -->
```

#### Tab: Impresión y Tickets
```svelte
<!-- Select: Impresora térmica predeterminada -->
<!-- Botón "Refrescar" → QZTrayService.getPrinters() -->
<!-- Checkbox "Habilitar QZ Tray" -->
<!-- Subir certificado público (input file) → guarda en localStorage / backend -->
<!-- Subir llave privada (input file) → guarda encriptado -->
<!-- Botón "Probar Conexión QZ Tray" → QZTrayService.connect() → toast resultado -->
```

#### Tab: RENIEC / SUNAT
```svelte
<!-- Input password: Token de API ApisPerú -->
<!-- Botón "Probar Token" → GET /api/v1/reniec/test → muestra resultado -->
```

#### Tab: WhatsApp Bot
```svelte
<!-- Evolution API URL + API Key (inputs de texto) -->
<!-- Badge estado conexión: verde "Conectado" / rojo "Desconectado" -->
<!-- Botón "Vincular" → muestra QR code (GET /api/v1/whatsapp/qr) -->
<!-- Botón "Desvincular" → POST /api/v1/whatsapp/desconectar -->
<!-- Textarea: System Prompt del chatbot -->
<!-- Info box: instrucciones para vincular WhatsApp -->
```

#### Tab: Automatizaciones
```svelte
<!-- Input numérico: horas de anticipación para recordatorios (default 24) -->
<!-- Botón "Enviar mensaje de prueba" → POST /api/v1/whatsapp/test -->
<!-- Nota: "Los cambios aplican en el próximo escaneo del servidor" -->
```

---

## GENERACIÓN DE PDF (Spatie Laravel PDF)

```php
// Backend: PdfGeneratorService.php
use Spatie\LaravelPdf\Facades\Pdf;

// Historia Clínica A4
public function generarHistoriaClinica(Paciente $paciente): string {
  $path = storage_path("app/temp/historia_{$paciente->id}.pdf");
  Pdf::view('pdfs.historia-clinica', [
    'paciente' => $paciente->load(['odontograma', 'tratamientos', 'pagos', 'historia']),
    'clinica' => Configuracion::getDatosClinica(),
    'fecha' => now()->format('d/m/Y H:i'),
  ])
  ->format('a4')
  ->margins(15, 15, 15, 15)
  ->save($path);
  return $path;
}

// Contrato PDF
public function generarContrato(Paciente $paciente, array $tratamientos): void {
  $pdf = Pdf::view('pdfs.contrato', compact('paciente', 'tratamientos', 'clinica'))
    ->format('a4');
  // Guardar con Spatie Media Library
  $paciente->addMediaFromStream($pdf->download()->getContent())
    ->usingName("Contrato {$paciente->nombre_completo}")
    ->toMediaCollection('contratos');
}
```

### Vistas Blade para PDFs (resources/views/pdfs/):
- `historia-clinica.blade.php` — con CSS inline, logo de clínica, tabla de historia
- `contrato.blade.php` — encabezado clínica, datos paciente, tratamientos, condiciones, firma
- `comprobante.blade.php` — ticket A4 con servicios y totales

---

## API ENDPOINTS PRINCIPALES

```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

GET    /api/v1/pacientes?search=&page=
POST   /api/v1/pacientes
GET    /api/v1/pacientes/{id}
PUT    /api/v1/pacientes/{id}
DELETE /api/v1/pacientes/{id}
GET    /api/v1/pacientes/{id}/odontograma
PUT    /api/v1/pacientes/{id}/odontograma/{pieza}
GET    /api/v1/pacientes/{id}/historia
POST   /api/v1/pacientes/{id}/historia
GET    /api/v1/pacientes/{id}/tratamientos
POST   /api/v1/pacientes/{id}/contratos/generar
GET    /api/v1/pacientes/{id}/media

GET    /api/v1/citas?fecha=&dentista_id=&estado=
POST   /api/v1/citas
PATCH  /api/v1/citas/{id}/estado
PUT    /api/v1/citas/{id}

GET    /api/v1/tratamientos/catalogo
POST   /api/v1/tratamientos/catalogo
GET    /api/v1/pagos
POST   /api/v1/pagos
GET    /api/v1/pagos/{id}/pdf

GET    /api/v1/staff
POST   /api/v1/staff
PUT    /api/v1/staff/{id}

GET    /api/v1/reportes/dashboard
GET    /api/v1/reportes/ingresos-mensuales
GET    /api/v1/reportes/citas-estado
GET    /api/v1/reportes/pdf
GET    /api/v1/reportes/excel

GET    /api/v1/reniec/{dni}
GET    /api/v1/sunat/{ruc}
GET    /api/v1/reniec/test

GET    /api/v1/mensajes
POST   /api/v1/mensajes/enviar
GET    /api/v1/whatsapp/estado
POST   /api/v1/whatsapp/desconectar
GET    /api/v1/whatsapp/qr

GET    /api/v1/configuracion
PATCH  /api/v1/configuracion
POST   /api/v1/configuracion/logo
GET    /api/v1/configuracion/impresoras
POST   /api/v1/whatsapp/test
```

---

## BASE DE DATOS — MIGRATIONS ORDER

```
001_create_users_table
002_create_roles_and_permissions_tables  (Spatie)
003_create_pacientes_table
004_create_contactos_emergencia_table
005_create_tratamientos_catalogo_table
006_create_plan_tratamientos_table
007_create_citas_table
008_create_pagos_table
009_create_historia_clinica_table
010_create_odontograma_table
011_create_mensajes_crm_table
012_create_contratos_table
013_create_configuracion_table
014_create_media_table  (Spatie Media Library)
```

---

## DISEÑO UI — TOKENS EXACTOS

```css
@theme {
  /* Sidebar */
  --color-sidebar-bg: #0F172A;
  --color-sidebar-light: #1E293B;
  --color-sidebar-text: #94A3B8;
  --color-sidebar-active-bg: #1D4ED8;
  --color-sidebar-active-border: #FFFFFF;

  /* Botones */
  --color-btn-primary: #2563EB;
  --color-btn-danger: #DC2626;
  --color-btn-success: #16A34A;

  /* KPI Cards - bordes */
  --color-kpi-pacientes: #3B82F6;   /* azul */
  --color-kpi-citas: #22C55E;       /* verde */
  --color-kpi-ingresos: #8B5CF6;    /* violeta */
  --color-kpi-deuda: #EF4444;       /* rojo */

  /* Badges roles */
  --color-badge-admin: #7C3AED;
  --color-badge-dentista: #2563EB;
  --color-badge-recepcionista: #EA580C;
  --color-badge-asistente: #6B7280;

  /* Odontograma */
  --color-diente-sano: #22C55E;
  --color-diente-caries: #EF4444;
  --color-diente-extraido: #6B7280;
  --color-diente-corona: #F59E0B;
  --color-diente-implante: #3B82F6;
  --color-diente-endodoncia: #8B5CF6;
  --color-diente-obturacion: #06B6D4;
}
```

---

## INSTRUCCIONES PARA GEMINI

1. **Genera módulo por módulo** cuando se te solicite — no todo de una sola vez
2. Para cada módulo entrega en este orden:
   - Migration(s) de Laravel
   - Model(s) con relaciones y fillable
   - Policy de Laravel
   - Controller con todos los métodos CRUD
   - Form Request de validación
   - Componente Svelte 5 principal (con `$state`, `$derived`, `$effect`)
   - Sub-componentes si aplica
   - Tipos TypeScript (`types.ts`)
3. Usa **Svelte 5 Runes** en todo el frontend — nunca Svelte 4 syntax
4. Usa **TailwindCSS v4** — `@theme {}` en CSS, no `tailwind.config.js`
5. Usa **TanStack Query** para todo el data fetching en Svelte
6. Usa **shadcn-svelte** para Dialog, Table, Select, Tabs, Toast, Badge, Input
7. Usa **Superforms + Zod** para todos los formularios con validación
8. El backend devuelve siempre `{ data, message, errors }` en JSON
9. Maneja errores 401 globalmente (redirigir a /login) en un interceptor Axios
10. Todos los endpoints verifican permisos con `$this->authorize()` usando Policies
11. Los PDFs se generan en backend y se devuelven como blob para download
12. QZ Tray se inicializa una vez al cargar el layout principal
13. Incluye seeders completos con datos de prueba realistas
