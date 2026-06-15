<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Trash2, Star } from 'lucide-svelte';
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
    import {
        Dialog,
        DialogContent,
        DialogHeader,
        DialogTitle,
        DialogDescription,
    } from '@/components/ui/dialog';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Toast } from '@/lib/utils/toast';

    let { ratings } = $props();

    let selectedRating = $state<any | null>(null);
    let isCommentModalOpen = $state(false);

    function openCommentModal(rating: any) {
        if (!rating.comment) return;
        selectedRating = rating;
        isCommentModalOpen = true;
    }

    function deleteRating(id: number) {
        Toast.confirm(
            '¿Estás seguro de eliminar esta valoración?',
            () => {
                router.delete(`/ratings/${id}`);
            },
            { type: 'destructive', confirmText: 'Eliminar' }
        );
    }

    function formatDate(dateString: string) {
        return new Date(dateString).toLocaleDateString('es-PE', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
</script>

<AppHead title="Valoraciones de Pacientes" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Valoraciones
            </h1>
            <p class="text-muted-foreground mt-1">
                Visualiza las calificaciones y comentarios dejados por los pacientes.
            </p>
        </div>
    </div>

    <div
        class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col flex-1"
    >
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Fecha</TableHead>
                        <TableHead>Paciente</TableHead>
                        <TableHead>Calificación</TableHead>
                        <TableHead>Comentario</TableHead>
                        <TableHead>Origen</TableHead>
                        <TableHead class="text-right">Acción</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if ratings.length === 0}
                        <TableRow>
                            <TableCell
                                colspan="6"
                                class="text-center h-24 text-muted-foreground"
                            >
                                No hay valoraciones registradas.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each ratings as rating}
                            <TableRow>
                                <TableCell class="whitespace-nowrap">
                                    {formatDate(rating.created_at)}
                                </TableCell>
                                <TableCell class="font-medium">
                                    {#if rating.patient}
                                        {rating.patient.first_name} {rating.patient.last_name}
                                    {:else}
                                        Anónimo
                                    {/if}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1 text-yellow-500">
                                        {#each Array(5) as _, i}
                                            <Star class="h-4 w-4 {i < rating.score ? 'fill-current' : 'text-gray-300'}" />
                                        {/each}
                                        <span class="ml-2 text-sm font-medium text-foreground">{rating.score}/5</span>
                                    </div>
                                </TableCell>
                                <TableCell 
                                    class="max-w-[200px] md:max-w-md truncate cursor-pointer hover:bg-gray-50 transition-colors"
                                    ondblclick={() => openCommentModal(rating)}
                                    title="Doble clic para ver comentario completo"
                                >
                                    {rating.comment || '-'}
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {rating.source || 'Bot'}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                        onclick={() => deleteRating(rating.id)}
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

<Dialog bind:open={isCommentModalOpen}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Detalle de Valoración</DialogTitle>
        </DialogHeader>
        {#if selectedRating}
        <div class="mt-4 space-y-4 text-sm">
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-gray-700">Paciente:</span>
                <span class="text-gray-900">{selectedRating.patient ? `${selectedRating.patient.first_name} ${selectedRating.patient.last_name}` : 'Anónimo'}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-gray-700">Fecha:</span>
                <span class="text-gray-900">{formatDate(selectedRating.created_at)}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-gray-700">Calificación:</span>
                <div class="flex items-center gap-1 text-yellow-500">
                    {#each Array(5) as _, i}
                        <Star class="h-4 w-4 {i < selectedRating.score ? 'fill-current' : 'text-gray-300'}" />
                    {/each}
                    <span class="ml-2 font-medium text-gray-900">{selectedRating.score}/5</span>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-gray-700">Origen:</span>
                <span class="inline-flex items-center w-fit px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {selectedRating.source || 'Bot'}
                </span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-gray-700">Comentario:</span>
                <div class="bg-gray-50 p-3 rounded-md text-gray-800 whitespace-pre-wrap border border-gray-100">
                    {selectedRating.comment}
                </div>
            </div>
        </div>
        {/if}
    </DialogContent>
</Dialog>
