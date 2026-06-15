<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { useForm } from '@inertiajs/svelte';
    import { Save, Loader2 } from 'lucide-svelte';
    import InputError from '@/components/InputError.svelte';
    import ReniecSearch from '@/components/ReniecSearch.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { store, update } from '@/routes/patients';
    import { Toast } from '@/lib/utils/toast';
    import { untrack } from 'svelte';

    let { isOpen = $bindable(false), patient = null } = $props();

    let isEditMode = $derived(!!patient);

    const form = useForm({
        _method: 'POST',
        dni: '',
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        date_of_birth: '',
        gender: '',
        address: '',
    });

    $effect(() => {
        const currentIsOpen = isOpen;
        const currentPatient = patient;
        
        if (currentIsOpen) {
            untrack(() => {
                form.clearErrors();
                if (currentPatient) {
                    form.dni = currentPatient.dni || '';
                    form.first_name = currentPatient.first_name || '';
                    form.last_name = currentPatient.last_name || '';
                    form.phone = currentPatient.phone || '';
                    form.email = currentPatient.email || '';
                    form.date_of_birth = currentPatient.date_of_birth ? currentPatient.date_of_birth.substring(0, 10) : '';
                    form.gender = currentPatient.gender || '';
                    form.address = currentPatient.address || '';
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
        
        const targetUrl = isEditMode ? update(patient.id) : store();
        
        form.post(targetUrl, {
            preserveScroll: true,
            onSuccess: () => {
                isOpen = false;
                Toast.success('Éxito', isEditMode ? 'Paciente actualizado exitosamente' : 'Paciente registrado exitosamente');
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
            <DialogTitle>{isEditMode ? 'Editar Paciente' : 'Registrar Nuevo Paciente'}</DialogTitle>
            <DialogDescription>
                {isEditMode ? 'Actualiza los datos personales del paciente.' : 'Ingresa los datos personales del nuevo paciente. Los campos con * son obligatorios.'}
            </DialogDescription>
        </DialogHeader>

        <form onsubmit={submit} class="grid gap-6 pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="dni">DNI *</Label>
                    <ReniecSearch 
                        bind:dni={form.dni} 
                        bind:firstName={form.first_name} 
                        bind:lastName={form.last_name} 
                    />
                    <InputError message={form.errors.dni} />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="nombres">Nombres *</Label>
                    <Input id="nombres" type="text" bind:value={form.first_name} required />
                    <InputError message={form.errors.first_name} />
                </div>
                <div class="space-y-2">
                    <Label for="apellidos">Apellidos *</Label>
                    <Input id="apellidos" type="text" bind:value={form.last_name} required />
                    <InputError message={form.errors.last_name} />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <Label for="telefono">Teléfono</Label>
                    <Input id="telefono" type="text" bind:value={form.phone} />
                    <InputError message={form.errors.phone} />
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" type="email" bind:value={form.email} />
                    <InputError message={form.errors.email} />
                </div>
                <div class="space-y-2">
                    <Label for="genero">Género</Label>
                    <Select
                        type="single"
                        bind:value={form.gender}
                    >
                        <SelectTrigger>
                            {form.gender || 'Seleccionar'}
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Masculino">Masculino</SelectItem>
                            <SelectItem value="Femenino">Femenino</SelectItem>
                            <SelectItem value="Otro">Otro</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.gender} />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="fecha_nacimiento">Fecha de Nacimiento</Label>
                    <Input id="fecha_nacimiento" type="date" bind:value={form.date_of_birth} />
                    <InputError message={form.errors.date_of_birth} />
                </div>
                <div class="space-y-2">
                    <Label for="direccion">Dirección</Label>
                    <Input id="direccion" type="text" bind:value={form.address} />
                    <InputError message={form.errors.address} />
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
