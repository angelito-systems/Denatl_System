<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { useForm } from '@inertiajs/svelte';
    import { Save, Loader2, Plus, Trash2 } from 'lucide-svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { toast } from 'svelte-sonner';
    import { untrack } from 'svelte';

    let { isOpen = $bindable(false), raffle = null } = $props();

    let isEditMode = $derived(!!raffle);

    const form = useForm({
        _method: 'POST',
        name: '',
        description: '',
        status: 'draft',
        draw_date: '',
        winner_count: 1,
        winning_logic: 'random',
        winning_nth_position: '',
        prizes: [{ name: '', position: 1 }]
    });

    $effect(() => {
        const currentIsOpen = isOpen;
        const currentRaffle = raffle;
        
        if (currentIsOpen) {
            untrack(() => {
                form.clearErrors();
                if (currentRaffle) {
                    form.name = currentRaffle.name || '';
                    form.description = currentRaffle.description || '';
                    form.status = currentRaffle.status || 'draft';
                    form.draw_date = currentRaffle.draw_date ? currentRaffle.draw_date.substring(0, 10) : '';
                    form.winner_count = currentRaffle.winner_count || 1;
                    form.winning_logic = currentRaffle.winning_logic || 'random';
                    form.winning_nth_position = currentRaffle.winning_nth_position || '';
                    
                    if (currentRaffle.prizes && currentRaffle.prizes.length > 0) {
                        form.prizes = currentRaffle.prizes.map(p => ({
                            name: p.name,
                            position: p.position
                        }));
                    } else {
                        form.prizes = [{ name: '', position: 1 }];
                    }
                    
                    form._method = 'PUT';
                } else {
                    form.reset();
                    form.prizes = [{ name: '', position: 1 }];
                    form._method = 'POST';
                }
            });
        }
    });

    function addPrize() {
        form.prizes = [...form.prizes, { name: '', position: form.prizes.length + 1 }];
    }

    function removePrize(index: number) {
        if (form.prizes.length > 1) {
            form.prizes = form.prizes.filter((_, i) => i !== index);
        }
    }

    function submit(e: Event) {
        e.preventDefault();
        
        const targetUrl = isEditMode ? `/raffles/${raffle.id}` : '/raffles';
        
        form.post(targetUrl, {
            preserveScroll: true,
            onSuccess: () => {
                isOpen = false;
                toast.success(isEditMode ? 'Sorteo actualizado' : 'Sorteo creado');
                if (!isEditMode) {
                    form.reset();
                }
            }
        });
    }
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle>{isEditMode ? 'Editar Sorteo' : 'Nuevo Sorteo'}</DialogTitle>
            <DialogDescription>
                Configura los detalles del evento y los premios a sortear.
            </DialogDescription>
        </DialogHeader>

        <form onsubmit={submit} class="grid gap-6 pt-4">
            <div class="space-y-2">
                <Label for="name">Nombre del Sorteo *</Label>
                <Input id="name" type="text" bind:value={form.name} required placeholder="Ej. Sorteo Día de la Madre" />
                <InputError message={form.errors.name} />
            </div>

            <div class="space-y-2">
                <Label for="description">Descripción (opcional)</Label>
                <Input id="description" type="text" bind:value={form.description} placeholder="Detalles visibles en el bot" />
                <InputError message={form.errors.description} />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="draw_date">Fecha del Sorteo</Label>
                    <Input id="draw_date" type="date" bind:value={form.draw_date} />
                    <InputError message={form.errors.draw_date} />
                </div>
                <div class="space-y-2">
                    <Label for="status">Estado</Label>
                    <Select type="single" bind:value={form.status}>
                        <SelectTrigger>
                            {form.status === 'active' ? 'Activo (Visible en el bot)' : (form.status === 'completed' ? 'Completado' : 'Borrador')}
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Borrador</SelectItem>
                            <SelectItem value="active">Activo</SelectItem>
                            <SelectItem value="completed">Completado</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>
            </div>

            <div class="border-t pt-4">
                <h4 class="font-medium mb-3">Lógica de Ganadores</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="winner_count">Cantidad de Ganadores *</Label>
                        <Input id="winner_count" type="number" min="1" bind:value={form.winner_count} required />
                        <InputError message={form.errors.winner_count} />
                    </div>
                    <div class="space-y-2">
                        <Label for="winning_logic">Modo de Selección</Label>
                        <Select type="single" bind:value={form.winning_logic}>
                            <SelectTrigger>
                                {form.winning_logic === 'random' ? 'Aleatorio' : (form.winning_logic === 'first' ? 'Los primeros inscritos' : 'El n-ésimo inscrito')}
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="random">Aleatorio</SelectItem>
                                <SelectItem value="first">Los primeros inscritos</SelectItem>
                                <SelectItem value="nth">El n-ésimo inscrito</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.winning_logic} />
                    </div>
                </div>
                
                {#if form.winning_logic === 'nth'}
                <div class="mt-4 space-y-2">
                    <Label for="winning_nth_position">Posición ganadora (ej. el participante 100)</Label>
                    <Input id="winning_nth_position" type="number" min="1" bind:value={form.winning_nth_position} />
                    <InputError message={form.errors.winning_nth_position} />
                </div>
                {/if}
            </div>

            <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium">Premios</h4>
                    <Button type="button" variant="outline" size="sm" onclick={addPrize}>
                        <Plus class="h-4 w-4 mr-1" /> Agregar Premio
                    </Button>
                </div>
                
                <div class="space-y-3">
                    {#each form.prizes as prize, i}
                        <div class="flex items-end gap-3">
                            <div class="space-y-2 w-20">
                                <Label>Puesto</Label>
                                <Input type="number" bind:value={prize.position} min="1" required />
                            </div>
                            <div class="space-y-2 flex-1">
                                <Label>Nombre del Premio</Label>
                                <Input type="text" bind:value={prize.name} placeholder="Ej. Blanqueamiento Gratis" required />
                            </div>
                            <Button 
                                type="button" 
                                variant="destructive" 
                                size="icon" 
                                onclick={() => removePrize(i)}
                                disabled={form.prizes.length === 1}
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        <InputError message={form.errors[`prizes.${i}.name`]} />
                    {/each}
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t mt-2">
                <Button type="button" variant="outline" onclick={() => isOpen = false}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={form.processing} class="bg-blue-600 hover:bg-blue-700">
                    {#if form.processing}
                        <Loader2 class="h-4 w-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        <Save class="h-4 w-4 mr-2" /> Guardar
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
