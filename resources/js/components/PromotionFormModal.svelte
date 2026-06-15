<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { useForm } from '@inertiajs/svelte';
    import { Save, Loader2 } from 'lucide-svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { Toast } from '@/lib/utils/toast';
    import { untrack } from 'svelte';

    let { isOpen = $bindable(false), promotion = null } = $props();

    let isEditMode = $derived(!!promotion);

    const form = useForm({
        _method: 'POST',
        title: '',
        description: '',
        discount_type: 'percentage',
        discount_value: '',
        start_date: '',
        end_date: '',
        is_active: '1',
    });

    $effect(() => {
        const currentIsOpen = isOpen;
        const currentPromotion = promotion;
        
        if (currentIsOpen) {
            untrack(() => {
                form.clearErrors();
                if (currentPromotion) {
                    form.title = currentPromotion.title || '';
                    form.description = currentPromotion.description || '';
                    form.discount_type = currentPromotion.discount_type || 'percentage';
                    form.discount_value = currentPromotion.discount_value || '';
                    form.start_date = currentPromotion.start_date ? currentPromotion.start_date.substring(0, 10) : '';
                    form.end_date = currentPromotion.end_date ? currentPromotion.end_date.substring(0, 10) : '';
                    form.is_active = currentPromotion.is_active ? '1' : '0';
                    form._method = 'PUT';
                } else {
                    form.reset();
                    form._method = 'POST';
                }
            });
        }
    });

    function submit(e: Event) {
        e.preventDefault();
        
        const targetUrl = isEditMode ? `/promotions/${promotion.id}` : '/promotions';
        
        const data = { ...form };
        data.is_active = data.is_active === '1';
        
        form.transform((data) => ({
            ...data,
            is_active: data.is_active === '1'
        })).post(targetUrl, {
            preserveScroll: true,
            onSuccess: () => {
                isOpen = false;
                Toast.success('Éxito', isEditMode ? 'Promoción actualizada exitosamente' : 'Promoción registrada exitosamente');
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
            <DialogTitle>{isEditMode ? 'Editar Promoción' : 'Nueva Promoción'}</DialogTitle>
            <DialogDescription>
                {isEditMode ? 'Actualiza los datos de la promoción.' : 'Ingresa los datos para la nueva promoción.'}
            </DialogDescription>
        </DialogHeader>

        <form onsubmit={submit} class="grid gap-6 pt-4">
            <div class="space-y-2">
                <Label for="title">Título *</Label>
                <Input id="title" type="text" bind:value={form.title} required />
                <InputError message={form.errors.title} />
            </div>

            <div class="space-y-2">
                <Label for="description">Descripción</Label>
                <Input id="description" type="text" bind:value={form.description} />
                <InputError message={form.errors.description} />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="discount_type">Tipo de Descuento</Label>
                    <Select type="single" bind:value={form.discount_type}>
                        <SelectTrigger>
                            {form.discount_type === 'percentage' ? 'Porcentaje' : 'Fijo'}
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="percentage">Porcentaje</SelectItem>
                            <SelectItem value="fixed">Fijo</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.discount_type} />
                </div>
                <div class="space-y-2">
                    <Label for="discount_value">Valor del Descuento</Label>
                    <Input id="discount_value" type="number" step="0.01" bind:value={form.discount_value} />
                    <InputError message={form.errors.discount_value} />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="start_date">Fecha de Inicio</Label>
                    <Input id="start_date" type="date" bind:value={form.start_date} />
                    <InputError message={form.errors.start_date} />
                </div>
                <div class="space-y-2">
                    <Label for="end_date">Fecha de Fin</Label>
                    <Input id="end_date" type="date" bind:value={form.end_date} />
                    <InputError message={form.errors.end_date} />
                </div>
            </div>

            <div class="space-y-2">
                <Label for="is_active">Estado</Label>
                <Select type="single" bind:value={form.is_active}>
                    <SelectTrigger>
                        {form.is_active === '1' ? 'Activo' : 'Inactivo'}
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="1">Activo</SelectItem>
                        <SelectItem value="0">Inactivo</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={form.errors.is_active} />
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
