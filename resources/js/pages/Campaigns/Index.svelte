<script module lang="ts">
    import { index as campaignsIndex } from '@/routes/campaigns';

    export const layout = {
        breadcrumbs: [
            { title: 'Campañas', href: '#' }
        ]
    };
</script>

<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Badge } from '@/components/ui/badge';
    import { Plus, Edit, Trash2, Send } from 'lucide-svelte';

    let { campaigns = [] } = $props();

    let isModalOpen = $state(false);

    const form = useForm({
        id: null as number | null,
        name: '',
        status: 'Encendida',
        start_date: '',
        end_date: '',
        message: '',
        target_audience: 'Todos',
        send_time: '',
        frequency: 'Unica',
        channel: 'WhatsApp'
    });

    function openNewModal() {
        form.reset();
        form.id = null;
        isModalOpen = true;
    }

    function openEditModal(campaign: any) {
        form.id = campaign.id;
        form.name = campaign.name;
        form.status = campaign.status;
        form.start_date = campaign.start_date ? campaign.start_date.substring(0, 10) : '';
        form.end_date = campaign.end_date ? campaign.end_date.substring(0, 10) : '';
        form.message = campaign.message;
        form.target_audience = campaign.target_audience || 'Todos';
        form.send_time = campaign.send_time ? campaign.send_time.substring(0, 5) : '';
        form.frequency = campaign.frequency || 'Unica';
        form.channel = campaign.channel || 'WhatsApp';
        isModalOpen = true;
    }

    function saveCampaign(e: Event) {
        e.preventDefault();
        if (form.id) {
            form.put(`/campaigns/${form.id}`, {
                onSuccess: () => { isModalOpen = false; }
            });
        } else {
            form.post('/campaigns', {
                onSuccess: () => { isModalOpen = false; }
            });
        }
    }

    function deleteCampaign(id: number) {
        if(confirm('¿Seguro que deseas eliminar esta campaña?')) {
            router.delete(`/campaigns/${id}`);
        }
    }
</script>

<AppHead title="Gestión de Campañas" />

<div class="flex h-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Campañas de Marketing</h1>
            <p class="text-muted-foreground">Administra envíos masivos y recordatorios automáticos (ej: Promociones, Saludos de Cumpleaños).</p>
        </div>
        <Button onclick={openNewModal} class="bg-blue-600 hover:bg-blue-700">
            <Plus class="w-4 h-4 mr-2" /> Nueva Campaña
        </Button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
        {#each campaigns as campaign}
            <Card class="hover:shadow-md transition-shadow relative overflow-hidden">
                <div class={`absolute top-0 left-0 w-1 h-full ${campaign.status === 'Encendida' ? 'bg-emerald-500' : 'bg-slate-300'}`}></div>
                <CardHeader class="pb-2">
                    <div class="flex justify-between items-start">
                        <CardTitle class="text-lg font-bold">{campaign.name}</CardTitle>
                        <Badge variant={campaign.status === 'Encendida' ? 'success' : 'secondary'}>{campaign.status}</Badge>
                    </div>
                    <CardDescription class="text-xs text-muted-foreground">
                        {campaign.start_date || 'Sin inicio'} al {campaign.end_date || 'Sin fin'} • {campaign.send_time || 'Hora auto'}
                    </CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="bg-slate-50 p-3 rounded-md border mt-2 mb-4 text-sm text-slate-700 italic">
                        "{campaign.message.substring(0, 100)}{campaign.message.length > 100 ? '...' : ''}"
                    </div>
                    <div class="flex items-center justify-between border-t pt-3">
                        <div class="text-xs text-muted-foreground flex items-center gap-1">
                            <Send class="w-3 h-3" /> WhatsApp
                        </div>
                        <div class="flex gap-2">
                            <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600" onclick={() => openEditModal(campaign)}>
                                <Edit class="w-4 h-4" />
                            </Button>
                            <Button variant="ghost" size="icon" class="h-8 w-8 text-red-600" onclick={() => deleteCampaign(campaign.id)}>
                                <Trash2 class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        {/each}

        {#if campaigns.length === 0}
            <div class="col-span-full p-12 text-center text-muted-foreground border-2 border-dashed rounded-xl">
                <Send class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                <h3 class="text-lg font-medium">No hay campañas creadas</h3>
                <p class="text-sm mt-1">Crea tu primera campaña para enviar mensajes automáticos a tus pacientes.</p>
            </div>
        {/if}
    </div>
</div>

<Dialog bind:open={isModalOpen}>
    <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
            <DialogTitle>{form.id ? 'Editar Campaña' : 'Nueva Campaña'}</DialogTitle>
            <DialogDescription>Configura los detalles del envío masivo o recordatorio.</DialogDescription>
        </DialogHeader>
        <form onsubmit={saveCampaign} class="space-y-4 pt-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2 col-span-2 md:col-span-1">
                    <Label>Nombre de la Campaña</Label>
                    <Input bind:value={form.name} placeholder="Ej. Promoción Ortodoncia" required />
                </div>
                <div class="space-y-2 col-span-2 md:col-span-1">
                    <Label>Estado</Label>
                    <select bind:value={form.status} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="Encendida">Encendida</option>
                        <option value="Apagada">Apagada</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Fecha Inicio (Opcional)</Label>
                    <Input type="date" bind:value={form.start_date} />
                </div>
                <div class="space-y-2">
                    <Label>Fecha Fin (Opcional)</Label>
                    <Input type="date" bind:value={form.end_date} />
                </div>
                <div class="space-y-2">
                    <Label>Hora de Envío (Opcional)</Label>
                    <Input type="time" bind:value={form.send_time} />
                </div>
                <div class="space-y-2">
                    <Label>Audiencia</Label>
                    <Input bind:value={form.target_audience} placeholder="Ej. Todos, Sin citas recientes" />
                </div>
                <div class="space-y-2 col-span-2">
                    <Label>Mensaje</Label>
                    <Textarea bind:value={form.message} rows={4} placeholder="Hola {nombre}, te escribimos para..." required />
                    <p class="text-xs text-muted-foreground mt-1">Variables disponibles: {`{nombre}`}, {`{apellido}`}</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <Button type="button" variant="outline" onclick={() => isModalOpen = false}>Cancelar</Button>
                <Button type="submit" disabled={form.processing} class="bg-blue-600 hover:bg-blue-700">Guardar Campaña</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
