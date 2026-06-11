<script module lang="ts">
    import { index as patientsIndex } from '@/routes/patients';

    export const layout = {
        breadcrumbs: [
            { title: 'Pacientes', href: patientsIndex() },
            { title: 'Perfil', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import {
        Edit,
        Activity,
        FileText,
        UserRound,
        Loader2,
        CalendarPlus,
        Save,
        Printer,
    } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Odontograma from '@/components/Odontograma.svelte';
    import ContratosDocumentos from './ContratosDocumentos.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import {
        Tabs,
        TabsContent,
        TabsList,
        TabsTrigger,
    } from '@/components/ui/tabs';
    import { index as appointmentsIndex } from '@/routes/appointments';
    import { update } from '@/routes/patients';

    let { patient } = $props();

    let isSaving = $state(false);
    let activeTab = $state('general');

    const form = useForm({
        _method: 'PUT',
        first_name: patient.first_name,
        last_name: patient.last_name,
        dni: patient.dni,
        email: patient.email || '',
        phone: patient.phone || '',
        date_of_birth: patient.date_of_birth || '',
        gender: patient.gender || 'Masculino',
        address: patient.address || '',
        occupation: patient.occupation || '',
        emergency_name: patient.emergency_name || '',
        emergency_phone: patient.emergency_phone || '',
        blood_type: patient.blood_type || '',
        allergies: patient.allergies || '',
        medical_notes: patient.medical_notes || '',
        family_history: patient.family_history || '',
        current_medication: patient.current_medication || '',
    });

    async function handleNewAppointment() {
        isSaving = true;
        await new Promise((resolve) => setTimeout(resolve, 1000));
        isSaving = false;
        router.visit(appointmentsIndex({ patient_id: patient.id }));
    }

    function handleOdontogramaChange(event: CustomEvent) {
        console.log('Odontograma state changed:', event.detail);
    }

    function savePatientInfo(e: Event) {
        e.preventDefault();
        form.post(update({ patient: patient.id }), {
            preserveScroll: true
        });
    }
</script>

<AppHead title={`Perfil - ${patient.first_name} ${patient.last_name}`} />

<div class="flex h-full flex-1 flex-col gap-6 p-6">
    <!-- Patient Header Summary -->
    <div
        class="flex flex-col items-start justify-between gap-4 rounded-xl border bg-card p-6 text-card-foreground shadow-sm md:flex-row md:items-center"
    >
        <div class="flex items-center gap-4">
            <div
                class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-700"
            >
                {patient.first_name.charAt(0)}{patient.last_name.charAt(0)}
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {patient.first_name} {patient.last_name}
                </h1>
                <div class="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1">
                        <FileText class="h-4 w-4" /> DNI: {patient.dni}
                    </span>
                    <span class="flex items-center gap-1">
                        <UserRound class="h-4 w-4" /> Edad: {patient.age || '-'} años
                    </span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- Botón Nueva Cita -->
            <Button
                variant="dental-success"
                onclick={handleNewAppointment}
                disabled={isSaving}
                class="shadow-md hover:shadow-lg transition-all duration-300 min-w-32.5"
            >
                {#if isSaving}
                    <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                    Cargando...
                {:else}
                    <CalendarPlus class="mr-2 h-4 w-4" />
                    Nueva Cita
                {/if}
            </Button>
        </div>
    </div>

    <!-- Tabs Content -->
    <Tabs bind:value={activeTab} class="w-full">
        <TabsList class="grid w-full max-w-2xl grid-cols-4">
            <TabsTrigger value="general">Info General</TabsTrigger>
            <TabsTrigger value="odontograma">Odontograma</TabsTrigger>
            <TabsTrigger value="historia">Historia Clínica</TabsTrigger>
            <TabsTrigger value="contratos">Contratos y Doc.</TabsTrigger>
        </TabsList>

        <TabsContent value="general" class="mt-6">
            <form onsubmit={savePatientInfo}>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de Contacto</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Teléfono:</Label>
                                        <Input bind:value={form.phone} />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Email:</Label>
                                        <Input type="email" bind:value={form.email} />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Dirección:</Label>
                                        <Input bind:value={form.address} />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Ocupación:</Label>
                                        <Input bind:value={form.occupation} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Contacto de Emergencia</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Nombre:</Label>
                                        <Input bind:value={form.emergency_name} />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Teléfono:</Label>
                                        <Input bind:value={form.emergency_phone} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos Médicos Básicos</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Tipo de Sangre:</Label>
                                        <Input bind:value={form.blood_type} placeholder="Ej. O+" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Alergias:</Label>
                                        <Input bind:value={form.allergies} placeholder="Alergias (separadas por coma)" />
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <Label>Notas Médicas:</Label>
                                    <Textarea bind:value={form.medical_notes} rows={3} />
                                </div>

                                <div class="space-y-2">
                                    <Label>Antecedentes Familiares:</Label>
                                    <Textarea bind:value={form.family_history} rows={3} />
                                </div>

                                <div class="space-y-2">
                                    <Label>Medicación Actual:</Label>
                                    <Textarea bind:value={form.current_medication} rows={3} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center bg-card p-4 rounded-xl border shadow-sm">
                    <Button variant="outline" type="button" class="border-blue-600 text-blue-600 hover:bg-blue-50" onclick={() => window.open(`/pacientes/${patient.id}/pdf/historia`, '_blank')}>
                        <Printer class="mr-2 h-4 w-4" />
                        Imprimir Historia
                    </Button>
                    <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={form.processing}>
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                </div>
            </form>
        </TabsContent>

        <TabsContent value="odontograma" class="mt-6">
            <Card>
                <CardHeader>
                    <CardTitle>Odontograma Interactivo</CardTitle>
                    <CardDescription
                        >Visualiza y registra el estado dental del paciente.
                        Selecciona la herramienta y haz click en las superficies
                        o en el diente completo.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <Odontograma on:change={handleOdontogramaChange} />
                </CardContent>
            </Card>
        </TabsContent>

        <TabsContent value="historia" class="mt-6">
            <Card>
                <CardHeader>
                    <CardTitle>Evolución e Historia Clínica</CardTitle>
                    <CardDescription
                        >Registro cronológico de las atenciones y
                        procedimientos.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        class="p-8 text-center text-muted-foreground border-2 border-dashed rounded-lg"
                    >
                        <FileText class="h-8 w-8 mx-auto mb-2 text-slate-400" />
                        <p>No hay registros clínicos.</p>
                        <Button variant="outline" class="mt-4"
                            >Añadir Evolución</Button
                        >
                    </div>
                </CardContent>
            </Card>
        </TabsContent>

        <TabsContent value="contratos" class="mt-6">
            <ContratosDocumentos {patient} />
        </TabsContent>
    </Tabs>
</div>
