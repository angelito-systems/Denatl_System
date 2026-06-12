<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Plus, Trash2, Edit, Gift, Users, Eye } from 'lucide-svelte';
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
    import RaffleFormModal from '@/components/RaffleFormModal.svelte';

    let { raffles } = $props();

    let isModalOpen = $state(false);
    let modalRaffle = $state(null);

    function openCreateModal() {
        modalRaffle = null;
        isModalOpen = true;
    }

    function openEditModal(raffle) {
        modalRaffle = raffle;
        isModalOpen = true;
    }

    function deleteRaffle(id: number) {
        if (confirm('¿Estás seguro de eliminar este sorteo? Se eliminarán todos los participantes y premios.')) {
            router.delete(`/raffles/${id}`);
        }
    }

    function goToRaffle(id: number) {
        router.get(`/raffles/${id}`);
    }

    function formatDate(dateString: string) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('es-PE', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
</script>

<AppHead title="Gestión de Sorteos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Sorteos y Concursos
            </h1>
            <p class="text-muted-foreground mt-1">
                Administra los eventos, premios y determina a los ganadores automáticamente.
            </p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openCreateModal}>
            <Plus class="mr-2 h-4 w-4" />
            Crear Sorteo
        </Button>
    </div>

    <div
        class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col flex-1"
    >
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Evento</TableHead>
                        <TableHead>Fecha Sorteo</TableHead>
                        <TableHead>Participantes</TableHead>
                        <TableHead>Ganadores (Config)</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="text-right">Acción</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if raffles.length === 0}
                        <TableRow>
                            <TableCell
                                colspan="6"
                                class="text-center h-24 text-muted-foreground"
                            >
                                No hay sorteos registrados.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each raffles as raffle}
                            <TableRow class="cursor-pointer hover:bg-muted/50 transition-colors" onclick={() => goToRaffle(raffle.id)}>
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                            <Gift class="h-4 w-4" />
                                        </div>
                                        <div>
                                            {raffle.name}
                                            {#if raffle.description}
                                                <div class="text-xs text-muted-foreground font-normal truncate max-w-[200px]">{raffle.description}</div>
                                            {/if}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>{formatDate(raffle.draw_date)}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1 font-medium">
                                        <Users class="h-4 w-4 text-slate-400" />
                                        {raffle.participants_count}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {raffle.winner_count} 
                                    <span class="text-xs text-muted-foreground ml-1">
                                        ({raffle.winning_logic === 'random' ? 'Aleatorio' : (raffle.winning_logic === 'first' ? 'Primeros' : 'N-ésimo')})
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                        {raffle.status === 'active' ? 'bg-green-100 text-green-800' : 
                                        (raffle.status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')}">
                                        {raffle.status === 'active' ? 'Activo' : (raffle.status === 'completed' ? 'Completado' : 'Borrador')}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-slate-600 hover:text-blue-700 hover:bg-blue-50"
                                            onclick={(e) => { e.stopPropagation(); goToRaffle(raffle.id); }}
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                            onclick={(e) => { e.stopPropagation(); openEditModal(raffle); }}
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                            onclick={(e) => { e.stopPropagation(); deleteRaffle(raffle.id); }}
                                        >
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
</div>

<RaffleFormModal bind:isOpen={isModalOpen} raffle={modalRaffle} />
