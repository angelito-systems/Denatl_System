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
    import { useForm } from '@inertiajs/svelte';
    import { Save, Settings } from 'lucide-svelte';

    let { configs } = $props();

    const form = useForm({
        settings: {
            clinica_nombre: configs.clinica_nombre || 'Clínica Dental System',
            clinica_ruc: configs.clinica_ruc || '',
            clinica_telefono: configs.clinica_telefono || '',
            clinica_direccion: configs.clinica_direccion || '',
            ticket_pie: configs.ticket_pie || '¡Gracias por su preferencia!',
            impresora_termica: configs.impresora_termica || '',
            apis_net_pe_token: configs.apis_net_pe_token || '',
            whatsapp_api_url: configs.whatsapp_api_url || 'http://localhost:8080',
            whatsapp_api_key: configs.whatsapp_api_key || '',
            whatsapp_instance: configs.whatsapp_instance || 'clinica-dental',
        }
    });

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
                <div class="space-y-2 md:col-span-2">
                    <Label>Nombre de Impresora Predeterminada</Label>
                    <Input bind:value={form.settings.impresora_termica} placeholder="Ej: EPSON TM-T20III Receipt" />
                    <p class="text-xs text-muted-foreground mt-1">Asegúrese de que el nombre coincida exactamente con el gestor de QZ Tray.</p>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <Label>Mensaje al Pie del Ticket</Label>
                    <Input bind:value={form.settings.ticket_pie} />
                </div>
            </CardContent>
        </Card>

        <!-- Conexiones Externas (RENIEC) -->
        <Card>
            <CardHeader>
                <CardTitle>Conexiones Externas (RENIEC / SUNAT)</CardTitle>
                <CardDescription>Credenciales para consultas automáticas de datos peruanos.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-2 max-w-xl">
                    <Label>Token de apis.net.pe (RENIEC/RUC)</Label>
                    <Input bind:value={form.settings.apis_net_pe_token} type="password" />
                    <p class="text-xs text-muted-foreground mt-1">Si dejas esto en blanco, el sistema operará en modo prueba local sin consultar a las bases de datos externas.</p>
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
