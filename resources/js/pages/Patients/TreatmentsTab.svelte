<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Save, ClipboardList, Trash2 } from 'lucide-svelte';

    let { patient, treatments = [] } = $props();

    let searchFocused = $state(false);
    let filteredTreatments = $derived(
        treatments.filter((t: any) => t.name.toLowerCase().includes(form.name.toLowerCase()))
    );

    const form = useForm({
        patient_id: patient.id,
        name: '',
        start_date: '',
        estimated_end_date: '',
        status: 'Activo',
        objectives: '',
        description: '',
    });

    function saveTreatment(e: Event) {
        e.preventDefault();
        form.post('/patient-treatments', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('name', 'start_date', 'estimated_end_date', 'objectives', 'description');
            }
        });
    }

    function deleteTreatment(id: number) {
        if (confirm('¿Estás seguro de eliminar este proceso terapéutico?')) {
            router.delete(`/patient-treatments/${id}`, { preserveScroll: true });
        }
    }

    function getStatusBadgeClass(status: string) {
        switch(status) {
            case 'Activo': return 'bg-emerald-100 text-emerald-700';
            case 'Pausado': return 'bg-amber-100 text-amber-700';
            case 'Finalizado': return 'bg-blue-100 text-blue-700';
            case 'Cancelado': return 'bg-red-100 text-red-700';
            default: return 'bg-slate-100 text-slate-700';
        }
    }

    function formatDate(dateString: string | null) {
        if (!dateString) return 'No definido';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
        } catch (e) {
            return dateString;
        }
    }
</script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulario Nuevo Tratamiento -->
    <Card class="lg:col-span-1">
        <CardHeader>
            <CardTitle>Nuevo Tratamiento</CardTitle>
            <CardDescription>Registra un nuevo proceso terapéutico a largo plazo.</CardDescription>
        </CardHeader>
        <CardContent>
            <form onsubmit={saveTreatment} class="space-y-4">
                <div class="space-y-2 relative">
                    <Label>Nombre del Tratamiento / Proceso</Label>
                    <div class="relative">
                        <Input 
                            bind:value={form.name} 
                            placeholder="Buscar en catálogo o escribir nombre..." 
                            required 
                            onfocus={() => searchFocused = true}
                            onblur={() => setTimeout(() => searchFocused = false, 200)}
                            autocomplete="off"
                        />
                        {#if searchFocused && filteredTreatments.length > 0}
                            <div class="absolute z-50 w-full mt-1 max-h-48 overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md outline-none">
                                {#each filteredTreatments as t}
                                    <button 
                                        type="button" 
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" 
                                        onclick={() => { form.name = t.name; searchFocused = false; }}
                                    >
                                        {t.name}
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-2">
                        <Label>Fecha Inicio</Label>
                        <Input type="date" bind:value={form.start_date} />
                    </div>
                    <div class="space-y-2">
                        <Label>Fin Estimado</Label>
                        <Input type="date" bind:value={form.estimated_end_date} />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Estado</Label>
                    <select bind:value={form.status} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="Activo">Activo</option>
                        <option value="Pausado">Pausado</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Objetivos</Label>
                    <Textarea bind:value={form.objectives} rows={2} placeholder="Objetivos del proceso..." />
                </div>
                <div class="space-y-2">
                    <Label>Descripción Adicional</Label>
                    <Textarea bind:value={form.description} rows={2} placeholder="Notas..." />
                </div>
                
                <Button type="submit" disabled={form.processing} class="w-full bg-blue-600 hover:bg-blue-700">
                    <Save class="mr-2 h-4 w-4" /> Crear Tratamiento
                </Button>
            </form>
        </CardContent>
    </Card>

    <!-- Lista de Tratamientos -->
    <Card class="lg:col-span-2">
        <CardHeader>
            <CardTitle>Procesos Terapéuticos</CardTitle>
        </CardHeader>
        <CardContent>
            {#if !patient.patient_treatments || patient.patient_treatments.length === 0}
                <div class="p-8 text-center text-muted-foreground border-2 border-dashed rounded-lg">
                    <ClipboardList class="h-8 w-8 mx-auto mb-2 text-slate-400" />
                    <p>No hay procesos terapéuticos registrados.</p>
                </div>
            {:else}
                <div class="space-y-4">
                    {#each patient.patient_treatments as p_treatment}
                        <div class="border rounded-xl shadow-sm overflow-hidden bg-white">
                            <div class="bg-slate-50 border-b px-4 py-3 flex items-center justify-between">
                                <h4 class="font-bold text-slate-800">{p_treatment.name}</h4>
                                <div class="flex items-center gap-3">
                                    <span class={`text-xs px-2 py-0.5 rounded-full font-medium ${getStatusBadgeClass(p_treatment.status)}`}>
                                        {p_treatment.status}
                                    </span>
                                    <button onclick={() => deleteTreatment(p_treatment.id)} class="text-red-500 hover:bg-red-50 p-1 rounded" title="Eliminar">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-muted-foreground text-xs font-medium uppercase tracking-wider mb-1">Cronograma</p>
                                    <p><strong>Inicio:</strong> {formatDate(p_treatment.start_date)}</p>
                                    <p><strong>Fin Estimado:</strong> {formatDate(p_treatment.estimated_end_date)}</p>
                                </div>
                                {#if p_treatment.objectives}
                                    <div>
                                        <p class="text-muted-foreground text-xs font-medium uppercase tracking-wider mb-1">Objetivos</p>
                                        <p class="text-slate-700 italic">"{p_treatment.objectives}"</p>
                                    </div>
                                {/if}
                                {#if p_treatment.description}
                                    <div class="md:col-span-2">
                                        <p class="text-muted-foreground text-xs font-medium uppercase tracking-wider mb-1">Descripción</p>
                                        <p class="text-slate-700 whitespace-pre-wrap">{p_treatment.description}</p>
                                    </div>
                                {/if}
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </CardContent>
    </Card>
</div>
