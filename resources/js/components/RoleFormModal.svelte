<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { useForm } from '@inertiajs/svelte';
    import { Save, Loader2, Check } from 'lucide-svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { toast } from 'svelte-sonner';
    import { untrack } from 'svelte';

    let { isOpen = $bindable(false), role = null, permissions = [] } = $props();

    let isEditMode = $derived(!!role);

    const form = useForm({
        _method: 'POST',
        name: '',
        permissions: [] as string[]
    });

    $effect(() => {
        const currentIsOpen = isOpen;
        const currentRole = role;
        
        if (currentIsOpen) {
            untrack(() => {
                form.clearErrors();
                if (currentRole) {
                    form.name = currentRole.name || '';
                    form.permissions = currentRole.permissions ? currentRole.permissions.map(p => p.name) : [];
                    form._method = 'PUT';
                } else {
                    form.reset();
                    form._method = 'POST';
                }
            });
        }
    });

    function togglePermission(permissionName: string) {
        if (form.permissions.includes(permissionName)) {
            form.permissions = form.permissions.filter(p => p !== permissionName);
        } else {
            form.permissions = [...form.permissions, permissionName];
        }
    }

    function submit(e: Event) {
        e.preventDefault();
        
        const targetUrl = isEditMode ? `/roles/${role.id}` : '/roles';
        
        form.post(targetUrl, {
            preserveScroll: true,
            onSuccess: () => {
                isOpen = false;
                toast.success(isEditMode ? 'Rol actualizado exitosamente' : 'Rol creado exitosamente');
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
            <DialogTitle>{isEditMode ? 'Editar Rol' : 'Crear Nuevo Rol'}</DialogTitle>
            <DialogDescription>
                Asigna un nombre al rol y selecciona los permisos que le correspondan.
            </DialogDescription>
        </DialogHeader>

        <form onsubmit={submit} class="grid gap-6 pt-4">
            <div class="space-y-2">
                <Label for="name">Nombre del Rol *</Label>
                <Input id="name" type="text" bind:value={form.name} required placeholder="Ej. Dentista, Recepcionista..." />
                <InputError message={form.errors.name} />
            </div>

            <div class="border-t pt-4">
                <h4 class="font-medium mb-3 text-sm">Permisos Disponibles</h4>
                {#if permissions.length === 0}
                    <p class="text-sm text-muted-foreground italic">No hay permisos registrados en el sistema.</p>
                {:else}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        {#each permissions as permission}
                            <!-- svelte-ignore a11y_click_events_have_key_events -->
                            <!-- svelte-ignore a11y_no_static_element_interactions -->
                            <div 
                                class="flex items-center gap-2 p-2 border rounded-md cursor-pointer hover:bg-slate-50 transition-colors
                                {form.permissions.includes(permission.name) ? 'border-blue-200 bg-blue-50/50' : 'border-slate-200'}"
                                onclick={() => togglePermission(permission.name)}
                            >
                                <div class="h-4 w-4 rounded border flex items-center justify-center
                                    {form.permissions.includes(permission.name) ? 'bg-blue-600 border-blue-600' : 'border-slate-300'}">
                                    {#if form.permissions.includes(permission.name)}
                                        <Check class="h-3 w-3 text-white" />
                                    {/if}
                                </div>
                                <span class="text-xs font-medium select-none text-slate-700">{permission.name}</span>
                            </div>
                        {/each}
                    </div>
                {/if}
                <InputError message={form.errors.permissions} />
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
