<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Facturación', href: '#facturacion' },
            { title: 'Tratamientos', href: '#tratamientos' },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Plus, Search, FileEdit, Trash2 } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow
    } from '@/components/ui/table';
    import { router, useForm } from '@inertiajs/svelte';
    import { index } from '@/routes/treatments';
    import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import { Toast } from '@/lib/utils/toast';
    import { Loader2 } from 'lucide-svelte';

    let { treatments, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;

    let isTreatmentModalOpen = $state(false);
    const treatmentForm = useForm({
        id: null as number | null,
        name: '',
        category: 'Ortodoncia',
        base_price: '',
        estimated_duration_minutes: '30',
        is_per_tooth: false
    });

    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(index(), { search }, { preserveState: true, replace: true });
        }, 300);
    }

    function openNewModal() {
        treatmentForm.reset();
        treatmentForm.id = null;
        isTreatmentModalOpen = true;
    }

    function editTreatment(treatment: any) {
        treatmentForm.id = treatment.id;
        treatmentForm.name = treatment.name;
        treatmentForm.category = treatment.category;
        treatmentForm.base_price = treatment.base_price;
        treatmentForm.estimated_duration_minutes = treatment.estimated_duration_minutes;
        treatmentForm.is_per_tooth = !!treatment.is_per_tooth;
        isTreatmentModalOpen = true;
    }

    function saveTreatment(e: Event) {
        e.preventDefault();
        if (treatmentForm.id) {
            treatmentForm.put(`/treatments/${treatmentForm.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    isTreatmentModalOpen = false;
                    Toast.success('Éxito', 'Tratamiento actualizado');
                }
            });
        } else {
            treatmentForm.post('/treatments', {
                preserveScroll: true,
                onSuccess: () => {
                    isTreatmentModalOpen = false;
                    treatmentForm.reset();
                    Toast.success('Éxito', 'Tratamiento creado');
                }
            });
        }
    }

    function deleteTreatment(id: number) {
        Toast.confirm(
            '¿Seguro que deseas eliminar este tratamiento?',
            () => {
                router.delete(`/treatments/${id}`, {
                    preserveScroll: true,
                    onSuccess: () => Toast.success('Éxito', 'Tratamiento eliminado')
                });
            },
            { type: 'destructive', confirmText: 'Eliminar' }
        );
    }
</script>

<AppHead title="Catálogo de Tratamientos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight">Catálogo de Tratamientos</h1>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openNewModal}>
            <Plus class="h-4 w-4 mr-2" />
            Nuevo Tratamiento
        </Button>
    </div>

    <div class="flex items-center gap-4 bg-card p-4 rounded-lg shadow-sm border">
        <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input 
                type="text" 
                placeholder="Buscar tratamiento o categoría..." 
                class="pl-9"
                bind:value={search}
                oninput={handleSearch}
            />
        </div>
    </div>

    <div class="bg-card rounded-lg shadow-sm border overflow-hidden">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Categoría</TableHead>
                    <TableHead>Precio Base</TableHead>
                    <TableHead>Duración (min)</TableHead>
                    <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {#if treatments.data.length === 0}
                    <TableRow>
                        <TableCell colspan="5" class="text-center h-24 text-muted-foreground">
                            No se encontraron tratamientos.
                        </TableCell>
                    </TableRow>
                {:else}
                    {#each treatments.data as treatment}
                        <TableRow>
                            <TableCell class="font-medium">{treatment.name}</TableCell>
                            <TableCell>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                    {treatment.category}
                                </span>
                                {#if treatment.is_per_tooth}
                                    <span class="ml-2 px-2 py-1 bg-emerald-100 text-emerald-800 text-xs rounded-full" title="Se cobra por cada pieza dental">
                                        Por Diente
                                    </span>
                                {/if}
                            </TableCell>
                            <TableCell>S/ {Number(treatment.base_price).toFixed(2)}</TableCell>
                            <TableCell>{treatment.estimated_duration_minutes} min</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600" onclick={() => editTreatment(treatment)}>
                                        <FileEdit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-600" onclick={() => deleteTreatment(treatment.id)}>
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    {/each}
                {/if}
            </TableBody>
        </Table>
    </div>
</div>

<Dialog bind:open={isTreatmentModalOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{treatmentForm.id ? 'Editar' : 'Nuevo'} Tratamiento</DialogTitle>
        </DialogHeader>
        <form onsubmit={saveTreatment} class="space-y-4 pt-4">
            <div class="space-y-2">
                <Label>Nombre del Tratamiento *</Label>
                <Input type="text" bind:value={treatmentForm.name} required />
                {#if treatmentForm.errors.name}<p class="text-xs text-red-500">{treatmentForm.errors.name}</p>{/if}
            </div>
            
            <div class="space-y-2">
                <Label>Categoría *</Label>
                <select class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" bind:value={treatmentForm.category} required>
                    <option value="Ortodoncia">Ortodoncia</option>
                    <option value="Implantes">Implantes</option>
                    <option value="Endodoncia">Endodoncia</option>
                    <option value="Odontopediatría">Odontopediatría</option>
                    <option value="Estética">Estética</option>
                    <option value="Rehabilitación">Rehabilitación</option>
                    <option value="General">General</option>
                </select>
                {#if treatmentForm.errors.category}<p class="text-xs text-red-500">{treatmentForm.errors.category}</p>{/if}
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>Precio Base (S/) *</Label>
                    <Input type="number" step="0.01" min="0" bind:value={treatmentForm.base_price} required />
                    {#if treatmentForm.errors.base_price}<p class="text-xs text-red-500">{treatmentForm.errors.base_price}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Duración (min) *</Label>
                    <Input type="number" min="1" bind:value={treatmentForm.estimated_duration_minutes} required />
                    {#if treatmentForm.errors.estimated_duration_minutes}<p class="text-xs text-red-500">{treatmentForm.errors.estimated_duration_minutes}</p>{/if}
                </div>
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" id="is_per_tooth" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600" bind:checked={treatmentForm.is_per_tooth} />
                <Label for="is_per_tooth" class="cursor-pointer font-medium text-gray-700">Se cobra por diente (Cantidad)</Label>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => isTreatmentModalOpen = false}>Cancelar</Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={treatmentForm.processing}>
                    {#if treatmentForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        {treatmentForm.id ? 'Actualizar' : 'Guardar'}
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
