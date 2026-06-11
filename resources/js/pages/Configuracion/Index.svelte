<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Configuración', href: '/configuracion' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Textarea } from '@/components/ui/textarea';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { useForm } from '@inertiajs/svelte';
    import { Save, Settings, Printer, RefreshCw } from 'lucide-svelte';
    import { toast } from 'svelte-sonner';
    import { QZTrayService } from '@/lib/utils/qztray';

    let { configs } = $props();

    const form = useForm({
        settings: {
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
        }
    });

    let availablePrinters = $state<string[]>(configs.impresora_termica ? [configs.impresora_termica] : []);
    let isFetchingPrinters = $state(false);

    async function fetchPrinters() {
        isFetchingPrinters = true;
        try {
            availablePrinters = await QZTrayService.getPrinters();
            toast.success(`Se encontraron ${availablePrinters.length} impresoras.`);
        } catch (error) {
            console.error('Error fetching printers', error);
            toast.error('No se pudo conectar con QZ Tray. Asegúrate de que la aplicación esté ejecutándose localmente.');
        } finally {
            isFetchingPrinters = false;
        }
    }

    async function handleFileUpload(e: Event, field: string) {
        const input = e.target as HTMLInputElement;
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const text = await file.text();
            (form.settings as any)[field] = text;
            toast.success(`Archivo ${file.name} cargado correctamente.`);
        }
    }

    function submitForm(e: Event) {
        e.preventDefault();
        form.post('/configuracion', {
            preserveScroll: true
        });
    }
</script>

<AppHead title="Configuración del Sistema" />

<div class="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
    <div class="flex items-center gap-2 mb-2">
        <Settings class="h-8 w-8 text-blue-600" />
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Configuración Global</h1>
            <p class="text-muted-foreground">Administra las preferencias, conexiones y perfiles de la clínica.</p>
        </div>
    </div>

    <form onsubmit={submitForm} class="space-y-8">
        <!-- Datos Generales -->
        <Card>
            <CardHeader>
                <CardTitle>Datos Generales</CardTitle>
                <CardDescription>Esta información aparecerá en los PDFs, contratos e interfaz gráfica.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>Nombre Comercial</Label>
                    <Input bind:value={form.settings.clinica_nombre} required />
                </div>
                <div class="space-y-2">
                    <Label>RUC (Opcional)</Label>
                    <Input bind:value={form.settings.clinica_ruc} />
                </div>
                <div class="space-y-2">
                    <Label>Teléfono / Celular</Label>
                    <Input bind:value={form.settings.clinica_telefono} />
                </div>
                <div class="space-y-2">
                    <Label>Dirección Física</Label>
                    <Input bind:value={form.settings.clinica_direccion} />
                </div>
            </CardContent>
        </Card>

        <!-- Impresión QZTray -->
        <Card>
            <CardHeader>
                <CardTitle>Impresión (Tickets QZTray)</CardTitle>
                <CardDescription>Configura los parámetros para impresoras térmicas locales.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4 md:col-span-2">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="space-y-2 flex-1">
                            <Label>Impresora Predeterminada</Label>
                            <Select bind:value={form.settings.impresora_termica}>
                                <SelectTrigger>
                                    {form.settings.impresora_termica || 'Seleccionar Impresora...'}
                                </SelectTrigger>
                                <SelectContent>
                                    {#each availablePrinters as printer}
                                        <SelectItem value={printer}>{printer}</SelectItem>
                                    {/each}
                                </SelectContent>
                            </Select>
                        </div>
                        <Button type="button" variant="outline" onclick={fetchPrinters} disabled={isFetchingPrinters}>
                            {#if isFetchingPrinters}
                                <RefreshCw class="h-4 w-4 mr-2 animate-spin" /> Buscando...
                            {:else}
                                <Printer class="h-4 w-4 mr-2" /> Buscar Impresoras
                            {/if}
                        </Button>
                    </div>
                </div>
                
                <div class="space-y-2 md:col-span-2">
                    <Label>Certificado Digital (digital-certificate.txt)</Label>
                    <div class="flex gap-2 mb-2">
                        <Input type="file" accept=".txt" class="flex-1" onchange={(e) => handleFileUpload(e, 'qztray_cert_txt')} />
                    </div>
                    <Textarea bind:value={form.settings.qztray_cert_txt} placeholder="Pega aquí el contenido de digital-certificate.txt o sube el archivo arriba" class="h-24 font-mono text-xs" />
                </div>
                
                <div class="space-y-2 md:col-span-2">
                    <Label>Llave Privada (private-key.pem)</Label>
                    <div class="flex gap-2 mb-2">
                        <Input type="file" accept=".pem,.key,.txt" class="flex-1" onchange={(e) => handleFileUpload(e, 'qztray_private_key_pem')} />
                    </div>
                    <Textarea bind:value={form.settings.qztray_private_key_pem} placeholder="Pega aquí el contenido de private-key.pem o sube el archivo arriba" class="h-24 font-mono text-xs" />
                    <p class="text-xs text-muted-foreground mt-1">Estos certificados permiten la impresión directa y silenciosa sin que QZ Tray muestre alertas de advertencia.</p>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <Label>Mensaje al Pie del Ticket</Label>
                    <Input bind:value={form.settings.ticket_pie} />
                </div>
            </CardContent>
        </Card>

        <!-- Facturación Electrónica (SUNAT) -->
        <Card>
            <CardHeader>
                <CardTitle>Facturación Electrónica (SUNAT Greenter)</CardTitle>
                <CardDescription>Credenciales para emisión de comprobantes de pago (Boletas/Facturas).</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>RUC Emisor</Label>
                    <Input bind:value={form.settings.sunat_ruc} placeholder="20000000000" />
                </div>
                <div class="space-y-2">
                    <Label>Razón Social</Label>
                    <Input bind:value={form.settings.sunat_razon_social} placeholder="MI CLINICA SAC" />
                </div>
                <div class="space-y-2">
                    <Label>Entorno SUNAT</Label>
                    <Select bind:value={form.settings.sunat_environment}>
                        <SelectTrigger>{form.settings.sunat_environment === 'production' ? 'Producción' : 'Demo (Pruebas)'}</SelectTrigger>
                        <SelectContent>
                            <SelectItem value="demo">Demo (Pruebas)</SelectItem>
                            <SelectItem value="production">Producción</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="space-y-2">
                    <Label>Usuario SOL</Label>
                    <Input bind:value={form.settings.sunat_sol_user} placeholder="MODDATOS" />
                </div>
                <div class="space-y-2">
                    <Label>Clave SOL</Label>
                    <Input bind:value={form.settings.sunat_sol_pass} type="password" placeholder="moddatos" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <Label>Certificado Digital (.pem)</Label>
                    <div class="flex gap-2 mb-2">
                        <Input type="file" accept=".pem,.key,.txt" class="flex-1" onchange={(e) => handleFileUpload(e, 'sunat_cert_pem')} />
                    </div>
                    <Textarea bind:value={form.settings.sunat_cert_pem} placeholder="Pega aquí el contenido de tu certificado digital PEM para firma electrónica de XMLs" class="h-24 font-mono text-xs" />
                </div>
            </CardContent>
        </Card>

        <!-- Conexiones Externas (apisperu.com) -->
        <Card>
            <CardHeader>
                <CardTitle>Conexiones Externas (apisperu.com)</CardTitle>
                <CardDescription>Credenciales para consultas automáticas de DNI/RUC.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label>URL Base de la API</Label>
                    <Input bind:value={form.settings.apis_peru_url} placeholder="https://dniruc.apisperu.com" />
                </div>
                <div class="space-y-2">
                    <Label>Token de Acceso</Label>
                    <Input bind:value={form.settings.apis_peru_token} type="password" />
                </div>
            </CardContent>
        </Card>

        <!-- WhatsApp CRM -->
        <Card>
            <CardHeader>
                <CardTitle>Evolution API Config (WhatsApp CRM)</CardTitle>
                <CardDescription>Parámetros para la integración omnicanal del CRM.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 md:col-span-2">
                    <Label>URL Base de Evolution API</Label>
                    <Input bind:value={form.settings.whatsapp_api_url} />
                </div>
                <div class="space-y-2">
                    <Label>Instance ID</Label>
                    <Input bind:value={form.settings.whatsapp_instance} />
                </div>
                <div class="space-y-2">
                    <Label>Global API Key</Label>
                    <Input bind:value={form.settings.whatsapp_api_key} type="password" />
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end pt-4 border-t sticky bottom-6 bg-background/80 backdrop-blur px-4 py-3 rounded-lg shadow-sm border mt-8">
            <Button type="submit" disabled={form.processing} class="bg-blue-600 hover:bg-blue-700 w-full sm:w-auto">
                <Save class="w-4 h-4 mr-2" />
                Guardar Cambios de Configuración
            </Button>
        </div>
    </form>
</div>
