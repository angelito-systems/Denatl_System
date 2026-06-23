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
        Eye,
    } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PatientFormModal from '@/components/PatientFormModal.svelte';
    import OdontogramaPro from '@/components/odontograma/OdontogramaPro.svelte';
    import OdontogramaHistoryModal from '@/components/odontograma/OdontogramaHistoryModal.svelte';
    import ContratosDocumentos from './ContratosDocumentos.svelte';
    import ContractsTab from './ContractsTab.svelte';
    import ClinicalImagesTab from './ClinicalImagesTab.svelte';
    import ObservationsTab from './ObservationsTab.svelte';
    import TreatmentsTab from './TreatmentsTab.svelte';
    import SendWhatsappButton from '@/components/SendWhatsappButton.svelte';
    import confetti from 'canvas-confetti';
    import { onMount } from 'svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
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

    let { patient, treatments = [], latestOdontogram = {} } = $props();

    let isSaving = $state(false);
    let activeTab = $state('general');
    let isModalOpen = $state(false);

    let daysUntilBirthday = $state<number | null>(null);
    let isBirthdayToday = $state(false);

    onMount(() => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab && ['general', 'odontogram', 'history', 'contracts', 'images', 'observations', 'treatments'].includes(tab)) {
            activeTab = tab;
        }

        if (patient.date_of_birth) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Tomamos solo la parte YYYY-MM-DD por si viene con timezone u horas
            const dobString = patient.date_of_birth.substring(0, 10);
            const birthDate = new Date(dobString + 'T00:00:00');
            
            const nextBirthday = new Date(today.getFullYear(), birthDate.getMonth(), birthDate.getDate());
            
            if (nextBirthday.getTime() < today.getTime()) {
                nextBirthday.setFullYear(today.getFullYear() + 1);
            }
            
            const diffTime = nextBirthday.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0 || diffDays === 365 || diffDays === 366) {
                isBirthdayToday = true;
                daysUntilBirthday = 0;
                setTimeout(() => {
                    confetti({
                        particleCount: 150,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }, 500);
            } else {
                daysUntilBirthday = diffDays;
            }
        }
    });

    const form = useForm({
        _method: 'PUT',
        first_name: patient.first_name,
        last_name: patient.last_name,
        dni: patient.dni,
        email: patient.email || '',
        phone: patient.phone || '',
        date_of_birth: patient.date_of_birth ? patient.date_of_birth.substring(0, 10) : '',
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
        // Simulando una pequeña carga visual (opcional)
        await new Promise((resolve) => setTimeout(resolve, 300));
        isSaving = false;
        router.visit(`${appointmentsIndex().url}?patient_id=${patient.id}`);
    }

    let currentOdontogramState = $state({});
    let showHistoryModal = $state(false);
    let selectedHistoryData = $state(null);

    function viewOdontogramHistory(data: any) {
        selectedHistoryData = data;
        showHistoryModal = true;
    }

    function handleOdontogramaChange(event: CustomEvent) {
        currentOdontogramState = event.detail;
    }

    const evolutionForm = useForm({
        description: '',
        appointment_id: null as number | null,
        attach_odontogram: false,
    });

    function saveEvolution(forceAttachOdontogram = false) {
        let attach = forceAttachOdontogram || evolutionForm.attach_odontogram;
        let payload = attach 
            ? (Object.keys(currentOdontogramState).length > 0 ? currentOdontogramState : latestOdontogram) 
            : null;
            
        evolutionForm.transform((data) => ({
            description: data.description,
            appointment_id: data.appointment_id,
            odontogram_data: payload
        })).post(`/pacientes/${patient.id}/evolutions`, {
            preserveScroll: true,
            onSuccess: () => {
                evolutionForm.reset('description', 'appointment_id', 'attach_odontogram');
                // optionally close a modal or just show success
            }
        });
    }

    function savePatientInfo(e: Event) {
        e.preventDefault();
        form.put(update({ patient: patient.id }), {
            preserveScroll: true,
            onSuccess: () => {
                isSaving = false;
            }
        });
    }

    $effect(() => {
        if (activeTab) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', activeTab);
            window.history.replaceState({}, '', url);
        }
    });
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
                <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                    {patient.first_name} {patient.last_name}
                    <button onclick={() => isModalOpen = true} class="text-muted-foreground hover:text-blue-600 transition-colors">
                        <Edit class="h-5 w-5" />
                    </button>
                </h1>
                <div class="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1">
                        <FileText class="h-4 w-4" /> DNI: {patient.dni}
                    </span>
                    <span class="flex items-center gap-1">
                        <UserRound class="h-4 w-4" /> Edad: {patient.age || '-'} años
                    </span>
                    {#if isBirthdayToday}
                        <span class="flex items-center gap-1 text-pink-600 font-bold bg-pink-100 px-2 py-0.5 rounded-full text-xs">
                            🎉 ¡Feliz Cumpleaños! 🎉
                        </span>
                    {:else if daysUntilBirthday !== null}
                        <span class="flex items-center gap-1 text-orange-600 font-medium bg-orange-100 px-2 py-0.5 rounded-full text-xs">
                            🎂 Faltan {daysUntilBirthday} días para su cumple
                        </span>
                    {/if}
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
            <TabsList class="mb-4 flex h-auto flex-wrap w-full lg:w-auto justify-start gap-2">
                <TabsTrigger class="flex-1 min-w-[120px]" value="general">Info. General</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="treatments">Tratamientos</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="observations">Observaciones</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="odontogram">Odontograma</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="history">Evoluciones</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="contracts">Contratos y Financiamiento</TabsTrigger>
                <TabsTrigger class="flex-1 min-w-[120px]" value="images">Imágenes Clínicas</TabsTrigger>
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
                                        <Label>Fecha de Nacimiento:</Label>
                                        <Input type="date" bind:value={form.date_of_birth} />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Género:</Label>
                                        <select bind:value={form.gender} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="">Seleccionar</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Otro">Otro</option>
                                        </select>
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
                    <div class="flex gap-2">
                        <Button variant="outline" type="button" class="border-blue-600 text-blue-600 hover:bg-blue-50" onclick={() => window.open(`/pacientes/${patient.id}/pdf/historia`, '_blank')}>
                            <Printer class="mr-2 h-4 w-4" />
                            Imprimir Historia
                        </Button>
                        <SendWhatsappButton 
                            phone={patient.phone} 
                            patient_id={patient.id} 
                            type="historia" 
                            caption="Aquí tienes tu Historia Clínica de Dental System."
                            buttonText="WhatsApp Historia"
                            variant="outline"
                            class="border-green-600 text-green-600 hover:bg-green-50"
                        />
                    </div>
                    <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={form.processing}>
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                </div>
            </form>
        </TabsContent>

        <TabsContent value="odontogram" class="mt-6">
            <Card class="border-0 shadow-lg">
                <CardHeader>
                    <CardTitle>Odontograma Inicial</CardTitle>
                    <CardDescription>
                        Registra el estado actual de la dentadura del paciente.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-6">
                    <OdontogramaPro 
                        patientId={patient.id} 
                        initialData={latestOdontogram}
                        on:change={handleOdontogramaChange} 
                    />

                    <!-- Formulario para Guardar Evolución -->
                    <div class="p-6 bg-slate-50 border rounded-xl flex flex-col gap-4 shadow-sm print:hidden">
                        <div>
                            <h3 class="font-bold text-lg text-slate-800">Guardar como Evolución Clínica</h3>
                            <p class="text-sm text-slate-500">
                                Congela el estado actual del odontograma en el historial del paciente.
                            </p>
                        </div>
                        
                        <form onsubmit={(e) => { e.preventDefault(); saveEvolution(true); }} class="flex flex-col md:flex-row gap-4 items-start">
                            <div class="flex-1 w-full">
                                <Label for="desc" class="mb-1 block">Descripción Médica</Label>
                                <Input 
                                    id="desc" 
                                    bind:value={evolutionForm.description} 
                                    placeholder="Ej: Se detectaron caries en piezas 11 y 16..." 
                                    class="bg-white"
                                />
                                {#if evolutionForm.errors.description}
                                    <span class="text-red-500 text-xs mt-1">{evolutionForm.errors.description}</span>
                                {/if}
                            </div>
                            
                            <div class="flex items-end h-full pt-6">
                                <Button type="submit" disabled={evolutionForm.processing} class="whitespace-nowrap">
                                    {#if evolutionForm.processing}
                                        <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                                    {:else}
                                        <Save class="w-4 h-4 mr-2" />
                                    {/if}
                                    Guardar Odontograma
                                </Button>
                            </div>
                        </form>
                    </div>
                </CardContent>
            </Card>
        </TabsContent>

        <TabsContent value="history" class="mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Formulario para nueva evolución -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>Añadir Evolución</CardTitle>
                        <CardDescription>Registra una nueva atención. El estado actual del Odontograma se guardará automáticamente.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onsubmit={(e) => { e.preventDefault(); saveEvolution(); }} class="space-y-4">
                            <div class="space-y-2">
                                <Label>Cita Asociada (Opcional)</Label>
                                <select bind:value={evolutionForm.appointment_id} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value={null}>-- Ninguna --</option>
                                    {#each patient.appointments || [] as appt}
                                        <option value={appt.id}>{appt.date} - {appt.reason}</option>
                                    {/each}
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Descripción / Notas</Label>
                                <Textarea bind:value={evolutionForm.description} rows={4} placeholder="Detalles del procedimiento realizado..." required />
                            </div>
                            
                            <div class="flex items-center space-x-2 pt-2 pb-2">
                                <input type="checkbox" id="attach-odontogram" bind:checked={evolutionForm.attach_odontogram} class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <Label for="attach-odontogram" class="font-normal cursor-pointer">Adjuntar estado actual del Odontograma</Label>
                            </div>
                            
                            <Button type="submit" disabled={evolutionForm.processing} class="w-full bg-blue-600 hover:bg-blue-700">
                                <Save class="mr-2 h-4 w-4" /> Guardar Evolución
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Columna Derecha: Historial de evoluciones -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Historia Clínica</CardTitle>
                        <CardDescription>Registro cronológico de las atenciones y procedimientos.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {#if !patient.evolutions || patient.evolutions.length === 0}
                            <div class="p-8 text-center text-muted-foreground border-2 border-dashed rounded-lg">
                                <FileText class="h-8 w-8 mx-auto mb-2 text-slate-400" />
                                <p>No hay registros clínicos.</p>
                            </div>
                        {:else}
                            <div class="space-y-6">
                                {#each patient.evolutions as evolution}
                                    <div class="relative pl-6 border-l-2 border-blue-100 dark:border-slate-800 pb-2">
                                        <div class="absolute w-3 h-3 bg-blue-500 rounded-full -left-[7px] top-1"></div>
                                        <div class="mb-1 flex items-center justify-between">
                                            <div class="font-medium text-sm text-blue-700 dark:text-blue-400">
                                                {new Date(evolution.created_at).toLocaleDateString()} a las {new Date(evolution.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                Dr. {evolution.dentist?.name || 'Dentista'}
                                            </div>
                                        </div>
                                        <p class="text-sm mt-2 text-foreground whitespace-pre-wrap">{evolution.description}</p>
                                        {#if evolution.appointment}
                                            <div class="mt-2 inline-flex items-center text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">
                                                <CalendarPlus class="w-3 h-3 mr-1" />
                                                Cita: {evolution.appointment.date}
                                            </div>
                                        {/if}
                                        {#if evolution.odontogram_data && Object.keys(evolution.odontogram_data).length > 0}
                                            <div class="mt-2 text-xs text-blue-600 flex items-center gap-4">
                                                <span><Activity class="w-3 h-3 inline mr-1" /> Odontograma guardado en esta sesión</span>
                                                <Button variant="outline" size="sm" class="h-6 text-xs px-2 hover:bg-blue-50" onclick={() => viewOdontogramHistory(evolution.odontogram_data)}>
                                                    <Eye class="w-3 h-3 mr-1" /> Ver
                                                </Button>
                                            </div>
                                        {/if}
                                    </div>
                                {/each}
                            </div>
                        {/if}
                    </CardContent>
                </Card>
            </div>
        </TabsContent>

        <TabsContent value="contracts" class="mt-6 space-y-8">
            <ContractsTab {patient} {treatments} />
            <div class="pt-6 border-t">
                <h3 class="text-lg font-medium mb-4">Documentos Generales</h3>
                <ContratosDocumentos {patient} />
            </div>
        </TabsContent>

        <TabsContent value="images" class="mt-6">
            <ClinicalImagesTab {patient} />
        </TabsContent>

        <TabsContent value="observations" class="mt-6">
            <ObservationsTab {patient} />
        </TabsContent>

        <TabsContent value="treatments" class="mt-6">
            <TreatmentsTab {patient} {treatments} />
        </TabsContent>
    </Tabs>
</div>

<OdontogramaHistoryModal 
    bind:isOpen={showHistoryModal} 
    patientId={patient.id} 
    odontogramData={selectedHistoryData} 
/>

<PatientFormModal bind:isOpen={isModalOpen} patient={patient} />
