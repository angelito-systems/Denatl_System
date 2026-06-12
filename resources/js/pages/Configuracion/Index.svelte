<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Configuración', href: '/configuracion' }],
    };
</script>

<script lang="ts">
    // 1. Paquetes de terceros (Third-party)
    import { useForm } from '@inertiajs/svelte';
    import {
        Save,
        Settings,
        RefreshCw,
        Upload,
        ImageIcon,
        Wifi,
        WifiOff,
        QrCode,
    } from 'lucide-svelte';
    import { toast } from 'svelte-sonner';
    import qz from 'qz-tray';

    // 2. Componentes locales y utilidades
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
        CardDescription,
    } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let { configs } = $props();

    // Usar derived para que sea reactivo cuando configs cambie
    let configsDerived = $derived({
        clinica_nombre: configs.clinica_nombre || 'Clínica Dental System',
        clinica_ruc: configs.clinica_ruc || '',
        clinica_telefono: configs.clinica_telefono || '',
        clinica_direccion: configs.clinica_direccion || '',
        ticket_pie: configs.ticket_pie || '¡Gracias por su preferencia!',
        impresora_termica: configs.impresora_termica || '',
        apis_peru_url: configs.apis_peru_url || 'https://dniruc.apisperu.com',
        apis_peru_token: configs.apis_peru_token || '',
        whatsapp_api_url: configs.whatsapp_api_url || 'http://localhost:8080',
        whatsapp_api_key: configs.whatsapp_api_key || '',
        whatsapp_instance: configs.whatsapp_instance || 'clinica-dental',
        qztray_cert_txt: configs.qztray_cert_txt || '',
        qztray_private_key_pem: configs.qztray_private_key_pem || '',
        sunat_ruc: configs.sunat_ruc || '',
        sunat_razon_social: configs.sunat_razon_social || '',
        sunat_sol_user: configs.sunat_sol_user || '',
        sunat_sol_pass: configs.sunat_sol_pass || '',
        sunat_cert_pem: configs.sunat_cert_pem || '',
        sunat_environment: configs.sunat_environment || 'demo',
        reminder_1_value: configs.reminder_1_value || '24',
        reminder_1_unit: configs.reminder_1_unit || 'hours',
        reminder_2_value: configs.reminder_2_value || '2',
        reminder_2_unit: configs.reminder_2_unit || 'hours',
        pago_yape_plin: configs.pago_yape_plin || '',
        pago_transferencia: configs.pago_transferencia || '',
    });

    let logoPreview = $state<string | null>(configs.logo_url || null);
    let logoFile = $state<File | null>(null);
    let isUploadingLogo = $state(false);

    // Estado de WhatsApp
    let whatsappStatus = $state<
        'checking' | 'connected' | 'disconnected' | 'connecting'
    >('checking');
    let whatsappQrText = $state<string | null>(null);
    let _whatsappInstanceName = $state<string | null>(null);
    let isCheckingWhatsApp = $state(false);
    let pollingInterval: number | null = null;

    let qzPrinters = $state<string[]>([]);

    const form = useForm({
        settings: configsDerived,
    });

    async function _handleFileUpload(e: Event, field: string) {
        const input = e.target as HTMLInputElement;

        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const text = await file.text();
            (form.settings as any)[field] = text;
            toast.success(`Archivo ${file.name} cargado correctamente.`);
        }
    }

    async function handleLogoSelect(e: Event) {
        const input = e.target as HTMLInputElement;

        if (input.files && input.files.length > 0) {
            logoFile = input.files[0];
            logoPreview = URL.createObjectURL(logoFile);
        }
    }

    async function uploadLogo() {
        if (!logoFile) {
            return;
        }

        isUploadingLogo = true;
        const data = new FormData();
        data.append('logo', logoFile);

        try {
            const response = await fetch('/configuracion/logo', {
                method: 'POST',
                body: data,
                headers: {
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
            });

            if (response.ok || response.redirected) {
                toast.success('Logo actualizado correctamente.');
                logoFile = null;
            } else {
                toast.error('Error al subir el logo.');
            }
        } catch {
            toast.error('Error de red al subir el logo.');
        } finally {
            isUploadingLogo = false;
        }
    }

    async function connectQZ() {
        try {
            // Configure QZ security
            qz.security.setCertificatePromise((resolve, reject) => {
                fetch('/configuracion/qztray-cert')
                    .then(res => {
                        if (res.ok) return res.text();
                        throw new Error("No certificate");
                    })
                    .then(resolve)
                    .catch(reject);
            });

            qz.security.setSignatureAlgorithm("SHA512");
            qz.security.setSignaturePromise((toSign) => {
                return (resolve, reject) => {
                    fetch('/configuracion/sign-qztray?request=' + toSign)
                        .then(res => {
                            if (res.ok) return res.text();
                            throw new Error("Failed to sign");
                        })
                        .then(resolve)
                        .catch(reject);
                };
            });

            if (!qz.websocket.isActive()) {
                await qz.websocket.connect();
            }
            
            const printers = await qz.printers.find();
            qzPrinters = printers;
            toast.success('QZ Tray conectado. Seleccione su impresora.');
        } catch (e: any) {
            console.error(e);
            toast.error('Error con QZ Tray: ' + (e.message || 'Verifica que QZ Tray esté abierto.'));
        }
    }

    async function testEvolutionApiConnection() {
        const apiUrl = form.settings.whatsapp_api_url;
        const apiKey = form.settings.whatsapp_api_key;

        if (!apiUrl) {
            toast.error('La URL de Evolution API es requerida');

            return;
        }

        try {
            const response = await fetch(`${apiUrl}/`, {
                method: 'GET',
                headers: { apikey: apiKey },
            });

            if (response.ok) {
                toast.success('✅ Conexión exitosa con Evolution API');
            } else {
                toast.error('No se pudo conectar con Evolution API');
            }
        } catch {
            toast.error('Error de conexión con Evolution API');
        }
    }

    async function testApisPeruConnection() {
        const url = form.settings.apis_peru_url;
        const token = form.settings.apis_peru_token;

        if (!token) {
            toast.error('El Token de APIs Perú es requerido');
            return;
        }

        try {
            const response = await fetch(`${url}/api/v1/dni/00000000?token=${token}`);
            if (response.ok || response.status === 404 || response.status === 422) {
                toast.success('✅ Conexión exitosa con APIs Perú');
            } else {
                toast.error('Error al conectar. Verifica tu token.');
            }
        } catch {
            toast.error('Error de red al conectar con APIs Perú');
        }
    }

    async function getWhatsAppConnectionState() {
        const apiUrl = form.settings.whatsapp_api_url;
        const apiKey = form.settings.whatsapp_api_key;
        const instance = form.settings.whatsapp_instance;

        if (!apiUrl || !instance) {
            return null;
        }

        try {
            const response = await fetch(
                `${apiUrl}/instance/connectionState/${instance}`,
                {
                    method: 'GET',
                    headers: { apikey: apiKey },
                },
            );

            if (response.ok) {
                const data = await response.json();
                console.log('Connection state:', data);

                return data;
            }

            return null;
        } catch (error) {
            console.error('Error getting connection state:', error);

            return null;
        }
    }

    async function checkWhatsAppConnection() {
        if (isCheckingWhatsApp) {
            return;
        }

        isCheckingWhatsApp = true;

        try {
            const stateData = await getWhatsAppConnectionState();

            if (!stateData) {
                whatsappStatus = 'disconnected';

                return;
            }

            const state = stateData.instance?.state;
            _whatsappInstanceName = stateData.instance?.instanceName || null;

            if (state === 'open') {
                whatsappStatus = 'connected';
                whatsappQrText = null;

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }

                toast.success(
                    '<i class="fa-brands fa-whatsapp"></i> WhatsApp conectado',
                );
            } else if (state === 'connecting') {
                whatsappStatus = 'connecting';
            } else if (state === 'close' || state === 'disconnected') {
                whatsappStatus = 'disconnected';
            } else {
                whatsappStatus = 'disconnected';
            }
        } catch (error) {
            console.error('Error checking connection:', error);
            whatsappStatus = 'disconnected';
        } finally {
            isCheckingWhatsApp = false;
        }
    }

    async function connectWhatsApp() {
        const apiUrl = form.settings.whatsapp_api_url;
        const apiKey = form.settings.whatsapp_api_key;
        const instance = form.settings.whatsapp_instance;

        if (!apiUrl || !instance) {
            toast.error('Completa la URL y el Instance ID');

            return;
        }

        try {
            const createResponse = await fetch(`${apiUrl}/instance/create`, {
                method: 'POST',
                headers: {
                    apikey: apiKey,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    instanceName: instance,
                    token: apiKey || 'dental123',
                    integration: 'WHATSAPP-BAILEYS',
                }),
            }).catch(() => null);

            if (
                createResponse &&
                !createResponse.ok &&
                createResponse.status !== 409
            ) {
                toast.warning(
                    'Error al crear instancia, pero se intentará conectar',
                );
            }

            const connectResponse = await fetch(
                `${apiUrl}/instance/connect/${instance}`,
                {
                    method: 'GET',
                    headers: { apikey: apiKey },
                },
            );

            if (connectResponse.ok) {
                const data = await connectResponse.json();
                const qrCodeText = data.code || data.base64;

                if (qrCodeText) {
                    whatsappQrText = qrCodeText;
                    whatsappStatus = 'connecting';
                    toast.info('Código QR generado. Escanéalo con WhatsApp');
                    startPolling();
                } else {
                    toast.error('No se pudo obtener el código QR');
                }
            } else {
                const errorText = await connectResponse.text();
                toast.error(`Error ${connectResponse.status}: ${errorText}`);
            }
        } catch (error) {
            console.error('Error:', error);
            toast.error('Error de conexión con Evolution API');
        }
    }

    function getQrImageUrl(): string {
        if (!whatsappQrText) {
            return '';
        }

        return `https://quickchart.io/qr?text=${encodeURIComponent(whatsappQrText)}&size=300&margin=2`;
    }

    function startPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }

        pollingInterval = setInterval(async () => {
            const stateData = await getWhatsAppConnectionState();

            if (stateData?.instance?.state === 'open') {
                whatsappStatus = 'connected';
                whatsappQrText = null;

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }

                toast.success('✅ WhatsApp conectado exitosamente');
            }
        }, 5000);

        setTimeout(() => {
            if (pollingInterval && whatsappStatus !== 'connected') {
                clearInterval(pollingInterval);
                pollingInterval = null;
                toast.warning('Tiempo de espera agotado. Intenta nuevamente.');
            }
        }, 120000);
    }

    async function disconnectWhatsApp() {
        const apiUrl = form.settings.whatsapp_api_url;
        const apiKey = form.settings.whatsapp_api_key;
        const instance = form.settings.whatsapp_instance;

        try {
            await fetch(`${apiUrl}/instance/logout/${instance}`, {
                method: 'DELETE',
                headers: { apikey: apiKey },
            });

            whatsappStatus = 'disconnected';
            whatsappQrText = null;

            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            toast.success('WhatsApp desconectado');
        } catch {
            toast.error('Error al desconectar');
        }
    }

    function submitForm(e: Event) {
        e.preventDefault();
        form.post('/configuracion', {
            preserveScroll: true,
            onSuccess: () => {
                setTimeout(() => checkWhatsAppConnection(), 500);
            },
        });
    }

</script>

<AppHead title="Configuración del Sistema" />

<div class="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
    <div class="flex items-center gap-2 mb-2">
        <Settings class="h-8 w-8 text-blue-600" />
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Configuración Global
            </h1>
            <p class="text-muted-foreground">
                Administra las preferencias de la clínica.
            </p>
        </div>
    </div>

    <form onsubmit={submitForm} class="space-y-8">
        <Card>
            <CardHeader>
                <CardTitle>Datos Generales</CardTitle>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2 space-y-3">
                    <Label>Logo</Label>
                    <div class="flex items-start gap-4">
                        <div
                            class="w-24 h-24 border-2 border-dashed rounded-lg flex items-center justify-center overflow-hidden"
                        >
                            {#if logoPreview}
                                <img
                                    src={logoPreview}
                                    alt="Logo"
                                    class="w-full h-full object-contain p-1"
                                />
                            {:else}
                                <ImageIcon
                                    class="w-8 h-8 text-muted-foreground/40"
                                />
                            {/if}
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="cursor-pointer">
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 border rounded-md text-sm hover:bg-muted"
                                >
                                    <Upload class="w-4 h-4" />
                                    {logoFile
                                        ? logoFile.name
                                        : 'Seleccionar imagen'}
                                </div>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="hidden"
                                    onchange={handleLogoSelect}
                                />
                            </label>
                            {#if logoFile}
                                <Button
                                    type="button"
                                    size="sm"
                                    onclick={uploadLogo}
                                    disabled={isUploadingLogo}
                                >
                                    {#if isUploadingLogo}
                                        <RefreshCw
                                            class="w-3 h-3 mr-1.5 animate-spin"
                                        /> Subiendo...
                                    {:else}
                                        <Upload class="w-3 h-3 mr-1.5" /> Guardar
                                    {/if}
                                </Button>
                            {/if}
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Nombre Comercial</Label>
                    <Input bind:value={form.settings.clinica_nombre} required />
                </div>
                <div class="space-y-2">
                    <Label>RUC</Label>
                    <Input bind:value={form.settings.clinica_ruc} />
                </div>
                <div class="space-y-2">
                    <Label>Teléfono</Label>
                    <Input bind:value={form.settings.clinica_telefono} />
                </div>
                <div class="space-y-2">
                    <Label>Dirección</Label>
                    <Input bind:value={form.settings.clinica_direccion} />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Métodos de Pago (Para WhatsApp)</CardTitle>
                <CardDescription>Estos datos se mostrarán a los pacientes cuando consulten sus deudas o recordatorios desde el Bot de WhatsApp.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>Yape / Plin (Número a mostrar)</Label>
                    <Input bind:value={form.settings.pago_yape_plin} placeholder="Ej: 999 888 777" />
                </div>
                <div class="space-y-2">
                    <Label>Cuenta Bancaria / Transferencia</Label>
                    <textarea bind:value={form.settings.pago_transferencia} class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" rows="3" placeholder="Ej: BCP: 191-12345678-0-12&#10;CCI: 002-19112345678012"></textarea>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>WhatsApp CRM</CardTitle>
                        <CardDescription
                            >Conecta WhatsApp con la clínica usando Evolution
                            API</CardDescription
                        >
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onclick={testEvolutionApiConnection}
                    >
                        <Wifi class="w-4 h-4 mr-2" />
                        Probar API
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="grid grid-cols-1 gap-4">
                    <div class="space-y-2">
                        <Label>URL de Evolution API</Label>
                        <Input
                            bind:value={form.settings.whatsapp_api_url}
                            placeholder="http://localhost:8080"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Instance ID</Label>
                        <Input
                            bind:value={form.settings.whatsapp_instance}
                            placeholder="clinica-dental"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>API Key</Label>
                        <Input
                            bind:value={form.settings.whatsapp_api_key}
                            type="password"
                            placeholder="opcional"
                        />
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div
                        class="flex flex-wrap justify-between items-center gap-4 mb-6"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium">Estado:</span>
                            {#if whatsappStatus === 'connected'}
                                <Badge variant="success">
                                    <Wifi class="w-3 h-3 mr-1" />
                                    Conectado
                                </Badge>
                            {:else if whatsappStatus === 'connecting'}
                                <Badge variant="warning">
                                    <RefreshCw
                                        class="w-3 h-3 mr-1 animate-spin"
                                    />
                                    Conectando...
                                </Badge>
                            {:else}
                                <Badge variant="destructive">
                                    <WifiOff class="w-3 h-3 mr-1" />
                                    Desconectado
                                </Badge>
                            {/if}
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onclick={checkWhatsAppConnection}
                            >
                                <RefreshCw class="w-4 h-4 mr-2" />
                                Verificar
                            </Button>

                            {#if whatsappStatus !== 'connected'}
                                <Button
                                    type="button"
                                    size="sm"
                                    onclick={connectWhatsApp}
                                    disabled={whatsappStatus === 'connecting'}
                                >
                                    <QrCode class="w-4 h-4 mr-2" />
                                    Conectar
                                </Button>
                            {:else}
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="destructive"
                                    onclick={disconnectWhatsApp}
                                >
                                    <WifiOff class="w-4 h-4 mr-2" />
                                    Desconectar
                                </Button>
                            {/if}
                        </div>
                    </div>

                    {#if whatsappQrText}
                        <div
                            class="flex flex-col items-center gap-4 p-6 bg-muted/20 rounded-lg"
                        >
                            <p class="text-sm font-medium">
                                Escanea este código QR
                            </p>
                            <div class="bg-white p-4 rounded-xl shadow-lg">
                                <img
                                    src={getQrImageUrl()}
                                    alt="QR Code"
                                    class="w-64 h-64"
                                    onerror={(e) => {
                                        const img =
                                            e.target as HTMLImageElement;
                                        img.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(whatsappQrText || '')}`;
                                    }}
                                />
                            </div>
                            <div class="text-sm text-center">
                                <p>1. Abre WhatsApp</p>
                                <p>2. Menú > Dispositivos vinculados</p>
                                <p>3. Escanea el QR</p>
                            </div>
                        </div>
                    {/if}
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Automatizaciones del Bot</CardTitle>
                        <CardDescription>
                            Configura los tiempos de los recordatorios automáticos de citas que enviará el Bot.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>Primer Recordatorio (Ej. 24 horas antes)</Label>
                    <div class="flex gap-2">
                        <Input type="number" bind:value={form.settings.reminder_1_value} class="w-1/2" />
                        <select bind:value={form.settings.reminder_1_unit} class="flex h-10 w-1/2 items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="hours">Horas</option>
                            <option value="minutes">Minutos (Pruebas)</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Segundo Recordatorio (Ej. 2 horas antes)</Label>
                    <div class="flex gap-2">
                        <Input type="number" bind:value={form.settings.reminder_2_value} class="w-1/2" />
                        <select bind:value={form.settings.reminder_2_unit} class="flex h-10 w-1/2 items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="hours">Horas</option>
                            <option value="minutes">Minutos (Pruebas)</option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <div>
                        <CardTitle>Consultas RENIEC / RUC (APIs Perú)</CardTitle>
                        <CardDescription>Configura tu token para extraer datos de pacientes automáticamente por su DNI.</CardDescription>
                    </div>
                    <Button type="button" variant="outline" size="sm" onclick={testApisPeruConnection}>
                        <Wifi class="w-4 h-4 mr-2" /> Probar API
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>URL de la API</Label>
                    <Input bind:value={form.settings.apis_peru_url} placeholder="https://dniruc.apisperu.com" />
                </div>
                <div class="space-y-2">
                    <Label>Token de Acceso</Label>
                    <Input type="password" bind:value={form.settings.apis_peru_token} placeholder="eyJ0eXAi..." />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Facturación Electrónica (SUNAT)</CardTitle>
                <CardDescription>Credenciales de Greenter para emitir comprobantes (Boletas/Facturas).</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>Entorno SUNAT</Label>
                    <select bind:value={form.settings.sunat_environment} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="demo">Demo (Beta)</option>
                        <option value="produccion">Producción</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>RUC Emisor</Label>
                    <Input bind:value={form.settings.sunat_ruc} placeholder="20000000001" />
                </div>
                <div class="space-y-2">
                    <Label>Razón Social</Label>
                    <Input bind:value={form.settings.sunat_razon_social} placeholder="Mi Empresa S.A.C." />
                </div>
                <div class="space-y-2">
                    <Label>Usuario SOL (RUC + Usuario)</Label>
                    <Input bind:value={form.settings.sunat_sol_user} placeholder="20000000001MODDATOS" />
                </div>
                <div class="space-y-2">
                    <Label>Clave SOL</Label>
                    <Input type="password" bind:value={form.settings.sunat_sol_pass} />
                </div>
                <div class="md:col-span-2 space-y-2">
                    <div class="flex justify-between">
                        <Label>Certificado Digital SUNAT (.pem)</Label>
                        <label class="cursor-pointer text-xs text-blue-600 hover:underline">
                            Subir Archivo .pem
                            <input type="file" accept=".pem,.txt" class="hidden" onchange={(e) => _handleFileUpload(e, 'sunat_cert_pem')} />
                        </label>
                    </div>
                    <textarea bind:value={form.settings.sunat_cert_pem} class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-mono text-xs" rows="4" placeholder="-----BEGIN CERTIFICATE-----..."></textarea>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <div>
                        <CardTitle>Impresión Silenciosa (QZ Tray)</CardTitle>
                        <CardDescription>Configura tu impresora térmica y los certificados de seguridad local.</CardDescription>
                    </div>
                    <Button type="button" variant="outline" size="sm" onclick={connectQZ}>
                        <Wifi class="w-4 h-4 mr-2" /> Probar Conexión QZ
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2 space-y-2">
                    <div class="flex items-center gap-2">
                        <Label>Impresora Térmica (Nombre exacto)</Label>
                        {#if qzPrinters.length > 0}
                            <select bind:value={form.settings.impresora_termica} class="flex h-10 w-[300px] items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">-- Seleccionar Impresora --</option>
                                {#each qzPrinters as printer}
                                    <option value={printer}>{printer}</option>
                                {/each}
                            </select>
                        {/if}
                    </div>
                    {#if qzPrinters.length === 0}
                        <Input bind:value={form.settings.impresora_termica} placeholder="Ej. EPSON TM-T20III" />
                    {/if}
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <Label>Certificado Público QZ (digital-certificate.txt)</Label>
                        <label class="cursor-pointer text-xs text-blue-600 hover:underline">
                            Subir Archivo
                            <input type="file" accept=".txt,.pem" class="hidden" onchange={(e) => _handleFileUpload(e, 'qztray_cert_txt')} />
                        </label>
                    </div>
                    <textarea bind:value={form.settings.qztray_cert_txt} class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-mono text-xs" rows="4" placeholder="-----BEGIN CERTIFICATE-----..."></textarea>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <Label>Llave Privada QZ (private-key.pem)</Label>
                        <label class="cursor-pointer text-xs text-blue-600 hover:underline">
                            Subir Archivo
                            <input type="file" accept=".pem,.txt" class="hidden" onchange={(e) => _handleFileUpload(e, 'qztray_private_key_pem')} />
                        </label>
                    </div>
                    <textarea bind:value={form.settings.qztray_private_key_pem} class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring font-mono text-xs" rows="4" placeholder="-----BEGIN PRIVATE KEY-----..."></textarea>
                </div>
                <div class="md:col-span-2 space-y-2">
                    <Label>Texto al pie del ticket</Label>
                    <Input bind:value={form.settings.ticket_pie} placeholder="¡Gracias por su preferencia!" />
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end pt-4 border-t">
            <Button
                type="submit"
                disabled={form.processing}
                class="bg-blue-600 hover:bg-blue-700"
            >
                <Save class="w-4 h-4 mr-2" />
                Guardar Configuración
            </Button>
        </div>
    </form>
</div>
