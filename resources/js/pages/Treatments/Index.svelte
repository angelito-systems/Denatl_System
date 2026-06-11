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
    import { router } from '@inertiajs/svelte';
    import { index } from '@/routes/treatments';

    let { treatments, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;

    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(index(), { search }, { preserveState: true, replace: true });
        }, 300);
    }
</script>

<AppHead title="Catálogo de Tratamientos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight">Catálogo de Tratamientos</h1>
        <Button class="bg-blue-600 hover:bg-blue-700">
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
                            </TableCell>
                            <TableCell>S/ {Number(treatment.base_price).toFixed(2)}</TableCell>
                            <TableCell>{treatment.estimated_duration_minutes} min</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600">
                                        <FileEdit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-600">
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
