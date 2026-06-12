<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Gift, Users, Trophy, ChevronLeft, Calendar, Play } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { toast } from 'svelte-sonner';

    let { raffle } = $props();

    function drawRaffle() {
        if (confirm('¿Estás seguro de realizar el sorteo AHORA? Esto seleccionará a los ganadores y enviará un mensaje de WhatsApp a cada uno.')) {
            router.post(`/raffles/${raffle.id}/draw`, {}, {
                onSuccess: () => {
                    toast.success('¡Sorteo finalizado con éxito!');
                },
                onError: (errors) => {
                    if (errors.error) toast.error(errors.error);
                }
            });
        }
    }

    function formatDate(dateString: string) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('es-PE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatTime(dateString: string) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleTimeString('es-PE', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
</script>

<AppHead title={`Sorteo: ${raffle.name}`} />

<div class="flex flex-col gap-6 p-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" onclick={() => router.get('/raffles')}>
            <ChevronLeft class="h-4 w-4" />
        </Button>
        <div class="flex-1">
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                {raffle.name}
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {raffle.status === 'active' ? 'bg-green-100 text-green-800' : 
                    (raffle.status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')}">
                    {raffle.status === 'active' ? 'Activo' : (raffle.status === 'completed' ? 'Completado' : 'Borrador')}
                </span>
            </h1>
            {#if raffle.description}
                <p class="text-muted-foreground mt-1">{raffle.description}</p>
            {/if}
        </div>
        
        {#if raffle.status === 'active'}
            <Button size="lg" class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white shadow-md border-0" onclick={drawRaffle}>
                <Play class="mr-2 h-5 w-5 fill-white" />
                Realizar Sorteo
            </Button>
        {/if}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Info y Premios -->
        <div class="space-y-6">
            <div class="bg-card rounded-lg shadow-sm border p-5">
                <h3 class="font-semibold text-lg flex items-center gap-2 mb-4">
                    <Calendar class="h-5 w-5 text-blue-500" /> Detalles del Evento
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-muted-foreground">Fecha Programada</div>
                        <div class="font-medium">{formatDate(raffle.draw_date)}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Configuración de Ganadores</div>
                        <div class="font-medium">{raffle.winner_count} ganador(es)</div>
                        <div class="text-sm">
                            Lógica: 
                            {#if raffle.winning_logic === 'random'}
                                Aleatoria
                            {:else if raffle.winning_logic === 'first'}
                                Los primeros en participar
                            {:else}
                                Posición #{raffle.winning_nth_position}
                            {/if}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg shadow-sm border p-5">
                <h3 class="font-semibold text-lg flex items-center gap-2 mb-4">
                    <Gift class="h-5 w-5 text-purple-500" /> Premios a Sortear
                </h3>
                
                {#if raffle.prizes.length === 0}
                    <p class="text-sm text-muted-foreground">No hay premios configurados.</p>
                {:else}
                    <ul class="space-y-3">
                        {#each raffle.prizes as prize}
                            <li class="flex items-center gap-3 p-3 bg-muted/50 rounded-md">
                                <div class="h-8 w-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                                    #{prize.position}
                                </div>
                                <span class="font-medium">{prize.name}</span>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </div>
        </div>

        <!-- Columna Derecha: Participantes y Ganadores -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Panel de Ganadores (Si ya finalizó) -->
            {#if raffle.status === 'completed'}
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg shadow-sm border border-amber-200 p-6">
                    <h3 class="font-bold text-xl flex items-center gap-2 mb-4 text-amber-800">
                        <Trophy class="h-6 w-6 text-amber-500 fill-amber-500" /> Ganadores del Sorteo
                    </h3>
                    
                    {#if raffle.winners.length === 0}
                        <p class="text-sm text-amber-700">No hubo participantes o no se pudieron elegir ganadores.</p>
                    {:else}
                        <div class="grid gap-4 sm:grid-cols-2">
                            {#each raffle.winners as winner}
                                <div class="bg-white rounded-lg p-4 shadow-sm border border-amber-100 flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-700">
                                            <Trophy class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="font-bold">
                                                {#if winner.patient}
                                                    {winner.patient.first_name} {winner.patient.last_name}
                                                {:else}
                                                    {winner.phone_number}
                                                {/if}
                                            </div>
                                            <div class="text-sm text-muted-foreground">{winner.phone_number}</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-amber-50 text-sm font-medium text-amber-800 flex items-center gap-2">
                                        <Gift class="h-4 w-4" /> 
                                        {winner.prize ? winner.prize.name : 'Premio General'}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col">
                <div class="p-5 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-lg flex items-center gap-2">
                        <Users class="h-5 w-5 text-blue-500" /> Lista de Participantes
                    </h3>
                    <span class="bg-muted px-2.5 py-0.5 rounded-full text-sm font-medium">
                        Total: {raffle.participants.length}
                    </span>
                </div>
                
                <div class="max-h-[500px] overflow-y-auto">
                    {#if raffle.participants.length === 0}
                        <div class="p-8 text-center text-muted-foreground">
                            Aún no hay participantes inscritos en este sorteo.
                        </div>
                    {:else}
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50 sticky top-0">
                                <tr>
                                    <th class="py-3 px-4 text-left font-medium text-muted-foreground">N°</th>
                                    <th class="py-3 px-4 text-left font-medium text-muted-foreground">Participante</th>
                                    <th class="py-3 px-4 text-left font-medium text-muted-foreground">WhatsApp</th>
                                    <th class="py-3 px-4 text-left font-medium text-muted-foreground">Fecha de Inscripción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                {#each raffle.participants as participant, i}
                                    <tr class="hover:bg-muted/30 {participant.is_winner ? 'bg-amber-50/50' : ''}">
                                        <td class="py-3 px-4 text-muted-foreground">{i + 1}</td>
                                        <td class="py-3 px-4 font-medium flex items-center gap-2">
                                            {#if participant.is_winner}
                                                <Trophy class="h-4 w-4 text-amber-500" />
                                            {/if}
                                            {#if participant.patient}
                                                {participant.patient.first_name} {participant.patient.last_name}
                                            {:else}
                                                <span class="italic text-muted-foreground">No Registrado</span>
                                            {/if}
                                        </td>
                                        <td class="py-3 px-4">{participant.phone_number}</td>
                                        <td class="py-3 px-4 text-muted-foreground">
                                            {formatDate(participant.created_at)} {formatTime(participant.created_at)}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
