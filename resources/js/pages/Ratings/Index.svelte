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

    let { ratings } = $props();

    function deleteRating(id: number) {
        if (confirm('¿Estás seguro de eliminar esta valoración?')) {
            router.delete(`/ratings/${id}`);
        }
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
                                <TableCell class="max-w-md truncate">
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
