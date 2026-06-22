<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Save, MessageSquareQuote, CalendarPlus, Trash2 } from 'lucide-svelte';

    let { patient } = $props();

    const form = useForm({
        patient_id: patient.id,
        appointment_id: null as number | null,
        content: '',
    });

    function saveObservation(e: Event) {
        e.preventDefault();
        form.post('/observations', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('content', 'appointment_id');
            }
        });
    }

    function deleteObservation(id: number) {
        if (confirm('¿Estás seguro de eliminar esta observación?')) {
            router.delete(`/observations/${id}`, { preserveScroll: true });
        }
    }
</script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulario Nueva Observación -->
    <Card class="lg:col-span-1">
        <CardHeader>
            <CardTitle>Nueva Observación</CardTitle>
            <CardDescription>Añade una nota general o asociada a una cita específica.</CardDescription>
        </CardHeader>
        <CardContent>
            <form onsubmit={saveObservation} class="space-y-4">
                <div class="space-y-2">
                    <Label>Cita Asociada (Opcional)</Label>
                    <select bind:value={form.appointment_id} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value={null}>-- General (Sin cita) --</option>
                        {#each patient.appointments || [] as appt}
                            <option value={appt.id}>{appt.date} - {appt.reason}</option>
                        {/each}
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Observación</Label>
                    <Textarea bind:value={form.content} rows={4} placeholder="Escribe aquí tu observación..." required />
                </div>
                <Button type="submit" disabled={form.processing} class="w-full bg-blue-600 hover:bg-blue-700">
                    <Save class="mr-2 h-4 w-4" /> Guardar
                </Button>
            </form>
        </CardContent>
    </Card>

    <!-- Historial de Observaciones -->
    <Card class="lg:col-span-2">
        <CardHeader>
            <CardTitle>Historial de Observaciones</CardTitle>
        </CardHeader>
        <CardContent>
            {#if !patient.observations || patient.observations.length === 0}
                <div class="p-8 text-center text-muted-foreground border-2 border-dashed rounded-lg">
                    <MessageSquareQuote class="h-8 w-8 mx-auto mb-2 text-slate-400" />
                    <p>No hay observaciones registradas.</p>
                </div>
            {:else}
                <div class="space-y-4">
                    {#each patient.observations as obs}
                        <div class="relative p-4 border rounded-xl shadow-sm {obs.appointment_id ? 'bg-slate-50' : 'bg-white'}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    {#if obs.appointment_id}
                                        <span class="inline-flex items-center text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">
                                            <CalendarPlus class="w-3 h-3 mr-1" />
                                            Asociado a Cita
                                        </span>
                                    {:else}
                                        <span class="inline-flex items-center text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">
                                            General
                                        </span>
                                    {/if}
                                    <span class="text-xs text-muted-foreground">
                                        {new Date(obs.created_at).toLocaleString()}
                                    </span>
                                </div>
                                <button onclick={() => deleteObservation(obs.id)} class="text-red-500 hover:bg-red-50 p-1 rounded">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                            <p class="text-sm mt-1 whitespace-pre-wrap">{obs.content}</p>
                            <div class="mt-3 text-xs text-muted-foreground border-t pt-2 flex items-center justify-between">
                                <span>Registrado por: <strong>{obs.user?.name || 'Sistema'}</strong></span>
                                {#if obs.appointment}
                                    <span>Cita: {obs.appointment.date}</span>
                                {/if}
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </CardContent>
    </Card>
</div>
