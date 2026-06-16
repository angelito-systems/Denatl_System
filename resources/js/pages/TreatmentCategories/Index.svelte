<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Clínica', href: '#clinica' },
            { title: 'Categorías de Tratamientos', href: '#categorias' },
        ],
    };
</script>

<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Plus, Search, FileEdit, Trash2 } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow
    } from '@/components/ui/table';
    import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Toast } from '@/lib/utils/toast';
    import { Loader2 } from 'lucide-svelte';

    const { categories = [] } = $props();

    let search = $state('');
    let filteredCategories = $derived(
        categories.filter((c: any) => c.name.toLowerCase().includes(search.toLowerCase()))
    );

    let isModalOpen = $state(false);
    const categoryForm = useForm({
        id: null as number | null,
        name: '',
    });

    function openCreateModal() {
        categoryForm.reset();
        categoryForm.id = null;
        isModalOpen = true;
    }

    function editCategory(category: any) {
        categoryForm.id = category.id;
        categoryForm.name = category.name;
        isModalOpen = true;
    }

    function saveCategory(e: Event) {
        e.preventDefault();
        if (categoryForm.id) {
            categoryForm.put(`/treatment-categories/${categoryForm.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    isModalOpen = false;
                    Toast.success('Éxito', 'Categoría actualizada');
                }
            });
        } else {
            categoryForm.post('/treatment-categories', {
                preserveScroll: true,
                onSuccess: () => {
                    isModalOpen = false;
                    categoryForm.reset();
                    Toast.success('Éxito', 'Categoría creada');
                }
            });
        }
    }

    function deleteCategory(id: number) {
        Toast.confirm(
            '¿Seguro que deseas eliminar esta categoría?',
            () => {
                router.delete(`/treatment-categories/${id}`, {
                    preserveScroll: true,
                    onSuccess: () => Toast.success('Éxito', 'Categoría eliminada'),
                    onError: () => Toast.error('Error', 'No se puede eliminar la categoría porque tiene tratamientos asociados.')
                });
            },
            { type: 'destructive', confirmText: 'Eliminar' }
        );
    }
</script>

<AppHead title="Categorías de Tratamientos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight">Categorías de Tratamientos</h1>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openCreateModal}>
            <Plus class="h-4 w-4 mr-2" />
            Nueva Categoría
        </Button>
    </div>

    <div class="flex items-center gap-4 bg-card p-4 rounded-lg shadow-sm border">
        <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input 
                type="text" 
                placeholder="Buscar categoría..." 
                class="pl-9"
                bind:value={search}
            />
        </div>
    </div>

    <div class="bg-card rounded-lg shadow-sm border overflow-hidden">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {#if filteredCategories.length === 0}
                    <TableRow>
                        <TableCell colspan="2" class="text-center h-24 text-muted-foreground">
                            No se encontraron categorías.
                        </TableCell>
                    </TableRow>
                {:else}
                    {#each filteredCategories as category}
                        <TableRow>
                            <TableCell class="font-medium">{category.name}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600" onclick={() => editCategory(category)}>
                                        <FileEdit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-600" onclick={() => deleteCategory(category.id)}>
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

<Dialog bind:open={isModalOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{categoryForm.id ? 'Editar' : 'Nueva'} Categoría</DialogTitle>
        </DialogHeader>
        <form onsubmit={saveCategory} class="space-y-4 pt-4">
            <div class="space-y-2">
                <Label>Nombre de la Categoría *</Label>
                <Input type="text" bind:value={categoryForm.name} required />
                {#if categoryForm.errors.name}<p class="text-xs text-red-500">{categoryForm.errors.name}</p>{/if}
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => isModalOpen = false}>Cancelar</Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={categoryForm.processing}>
                    {#if categoryForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        {categoryForm.id ? 'Actualizar' : 'Guardar'}
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
