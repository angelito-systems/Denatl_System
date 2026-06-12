<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Plus, Trash2, Edit } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import PromotionFormModal from '@/components/PromotionFormModal.svelte';

    let { promotions } = $props();

    let isModalOpen = $state(false);
    let modalPromotion = $state(null);

    function openCreateModal() {
        modalPromotion = null;
        isModalOpen = true;
    }

    function openEditModal(promo) {
        modalPromotion = promo;
        isModalOpen = true;
    }

    function deletePromotion(id: number) {
        if (confirm('¿Estás seguro de eliminar esta promoción?')) {
            router.delete(`/promotions/${id}`);
        }
    }

    function formatDate(dateString: string) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('es-PE');
    }
</script>

<AppHead title="Promociones y Ofertas" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Promociones y Cupones
            </h1>
            <p class="text-muted-foreground mt-1">
                Administra las ofertas que el bot enviará a los pacientes.
            </p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openCreateModal}>
            <Plus class="mr-2 h-4 w-4" />
            Nueva Promoción
        </Button>
    </div>

    <div
        class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col flex-1"
    >
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Título</TableHead>
                        <TableHead>Descuento</TableHead>
                        <TableHead>Inicio</TableHead>
                        <TableHead>Fin</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="text-right">Acción</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if promotions.length === 0}
                        <TableRow>
                            <TableCell
                                colspan="6"
                                class="text-center h-24 text-muted-foreground"
                            >
                                No hay promociones registradas.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each promotions as promo}
                            <TableRow>
                                <TableCell class="font-medium">
                                    {promo.title}
                                    <div class="text-xs text-muted-foreground">{promo.description || ''}</div>
                                </TableCell>
                                <TableCell>
                                    {#if promo.discount_value}
                                        {promo.discount_type === 'percentage' ? `${Math.floor(promo.discount_value)}%` : `S/ ${promo.discount_value}`}
                                    {:else}
                                        -
                                    {/if}
                                </TableCell>
                                <TableCell>{formatDate(promo.start_date)}</TableCell>
                                <TableCell>{formatDate(promo.end_date)}</TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {promo.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                        {promo.is_active ? 'Activo' : 'Inactivo'}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                        onclick={() => openEditModal(promo)}
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                        onclick={() => deletePromotion(promo.id)}
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        {/each}
                    {/if}
                </TableBody>
            </Table>
        </div>
    </div>
</div>

<PromotionFormModal bind:isOpen={isModalOpen} promotion={modalPromotion} />
