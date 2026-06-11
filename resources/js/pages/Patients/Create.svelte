<script module lang="ts">
    import { index as patientsIndex } from '@/routes/patients';

    export const layout = {
        breadcrumbs: [
            { title: 'Pacientes', href: patientsIndex() },
            { title: 'Nuevo Paciente', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Save, Search, UserRoundPlus } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ReniecSearch from '@/components/ReniecSearch.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import { store } from '@/routes/patients';

    const form = useForm({
        dni: '',
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        date_of_birth: '',
        gender: '',
        address: '',
    });

    function submit(e: Event) {
        e.preventDefault();
        form.post(store());
    }
</script>

<AppHead title="Nuevo Paciente" />

<div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
    <div class="flex items-center gap-2">
        <UserRoundPlus class="h-8 w-8 text-blue-600" />
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Registro de Paciente</h1>
            <p class="text-muted-foreground">Ingresa los datos personales del nuevo paciente.</p>
        </div>
    </div>

    <form onsubmit={submit}>
        <Card class="border-0 shadow-lg">
            <CardHeader class="border-b bg-muted/20">
                <CardTitle>Información Personal</CardTitle>
                <CardDescription>Los campos marcados con * son obligatorios.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 pt-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <div class="flex justify-end gap-4 border-t pt-6 mt-4">
                    <Button type="button" variant="outline" onclick={() => history.back()}>
                        Cancelar
                    </Button>
                    <Button type="submit" disabled={form.processing} class="bg-blue-600 hover:bg-blue-700">
                        <Save class="h-4 w-4 mr-2" />
                        Guardar Paciente
                    </Button>
                </div>
            </CardContent>
        </Card>
    </form>
</div>
