<script module lang="ts">
    import { index as appointmentsIndex } from '@/routes/appointments';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Calendario de Citas',
                href: appointmentsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Calendar } from '@fullcalendar/core';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow
    } from '@/components/ui/table';
    import { Badge } from '@/components/ui/badge';
    import { store } from '@/routes/appointments';

    let {
        appointmentsList,
        patients = [],
        dentists = [],
        treatments = [],
        initialPatientId = null,
    } = $props();

    let calendarEl: HTMLElement;
    let calendar: Calendar | null = null;
    let isDialogOpen = $state(false);
    let isLoadingHours = $state(false);
    let availableSlots = $state<string[]>([]);

    async function fetchAvailableHours() {
        if (!form.dentist_id || !form.date) return;
        isLoadingHours = true;
        try {
            const res = await fetch(`/api/dentists/${form.dentist_id}/available-hours?date=${form.date}`);
            const data = await res.json();
            availableSlots = data.available_slots || [];
            if (data.requires_date_change && data.available_date) {
                form.date = data.available_date;
                // Form date changed, so it might re-trigger if we put a watcher, 
                // but since we updated availableSlots, we are fine.
                form.start_time = '';
            } else if (!availableSlots.includes(form.start_time)) {
                form.start_time = '';
            }
        } catch (error) {
            console.error(error);
            availableSlots = [];
        } finally {
            isLoadingHours = false;
        }
    }

    $effect(() => {
        // Watch for changes in dentist_id or date
        if (form.dentist_id && form.date) {
            fetchAvailableHours();
        }
    });

    let patientSearchQuery = $state('');
    let showPatientDropdown = $state(false);
    let filteredPatients = $derived(
        patients.filter((p) => {
            if (!patientSearchQuery) return true;
            const q = patientSearchQuery.toLowerCase();
            return (
                p.first_name?.toLowerCase().includes(q) ||
                p.last_name?.toLowerCase().includes(q) ||
                p.dni?.includes(q) ||
                p.phone?.includes(q)
            );
        }),
    );

    const form = useForm({
        id: null as number | null,
        patient_id: '',
        dentist_id: dentists.length > 0 ? dentists[0].id.toString() : '',
        date: '',
        start_time: '',
        duration: '30',
        treatment: '',
        status: 'pending',
        notes: '',
    });

    onMount(() => {
        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            allDaySlot: false,
            selectable: true,
            events: function (info, successCallback, failureCallback) {
                fetch(
                    `/appointments?start=${info.startStr}&end=${info.endStr}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                )
                    .then((res) => res.json())
                    .then((data) => {
                        const mappedEvents = data.map((appointment: any) => {
                            const dateOnly = appointment.date
                                ? appointment.date.split('T')[0]
                                : '';
                            const startString = `${dateOnly}T${appointment.start_time}`;
                            const startObj = new Date(startString);
                            const endObj = new Date(
                                startObj.getTime() +
                                    (appointment.duration || 30) * 60000,
                            );

                            return {
                                id: appointment.id,
                                title: `${appointment.patient?.first_name} - ${appointment.treatment}`,
                                start: startString,
                                end: isNaN(endObj.getTime())
                                    ? undefined
                                    : endObj.toISOString(),
                                backgroundColor:
                                    appointment.status === 'confirmed'
                                        ? '#16a34a'
                                        : appointment.status === 'completed'
                                          ? '#6b7280'
                                          : appointment.status === 'cancelled'
                                            ? '#ef4444'
                                            : '#3b82f6',
                                extendedProps: { ...appointment },
                            };
                        });
                        successCallback(mappedEvents);
                    })
                    .catch((err) => failureCallback(err));
            },
            select: handleDateSelect,
            eventClick: handleEventClick,
            locale: 'es',
        });
        calendar.render();

        setTimeout(() => {
            if (initialPatientId) {
                openNewCita();
                form.patient_id = initialPatientId.toString();
                const p = patients.find(
                    (pt) => pt.id.toString() === initialPatientId.toString(),
                );
                if (p) {
                    patientSearchQuery = `${p.first_name} ${p.last_name} (${p.dni})`;
                }
            }
        }, 150);

        return () => {
            calendar?.destroy();
        };
    });

    $effect(() => {
        if (form.patient_id) {
            const p = patients.find(
                (pt) => pt.id.toString() === form.patient_id,
            );
            if (p && !showPatientDropdown) {
                patientSearchQuery = `${p.first_name} ${p.last_name} (${p.dni})`;
            }
        } else if (!showPatientDropdown) {
            patientSearchQuery = '';
        }
    });

    function handleDateSelect(selectInfo: any) {
        const dateStr = selectInfo.startStr.split('T')[0];
        const timeStr =
            selectInfo.startStr.split('T')[1]?.substring(0, 5) || '09:00';

        form.date = dateStr;
        form.start_time = timeStr;
        isDialogOpen = true;
    }

    let isDetailsModalOpen = $state(false);
    let selectedAppointment = $state<any>(null);

    function handleEventClick(clickInfo: any) {
        selectedAppointment = clickInfo.event.extendedProps;
        isDetailsModalOpen = true;
    }

    function openNewCita() {
        form.reset();
        form.id = null;
        const now = new Date();
        form.date = now.toISOString().split('T')[0];
        form.start_time = '09:00';
        isDialogOpen = true;
    }

    function submit(e: Event) {
        e.preventDefault();
        if (form.id) {
            form.put(`/appointments/${form.id}`, {
                onSuccess: () => {
                    isDialogOpen = false;
                    form.reset();
                    calendar?.refetchEvents();
                },
            });
        } else {
            form.post(store(), {
                onSuccess: () => {
                    isDialogOpen = false;
                    form.reset();
                    calendar?.refetchEvents();
                },
            });
        }
    }

    function deleteCita() {
        if (form.id && confirm('¿Estás seguro de eliminar esta cita?')) {
            router.delete(`/appointments/${form.id}`, {
                onSuccess: () => {
                    isDialogOpen = false;
                    form.reset();
                    calendar?.refetchEvents();
                },
            });
        }
    }

    function openEditForm() {
        if (!selectedAppointment) return;
        form.id = selectedAppointment.id;
        form.patient_id = selectedAppointment.patient_id?.toString() || '';
        form.dentist_id = selectedAppointment.dentist_id?.toString() || '';
        form.date = selectedAppointment.date ? selectedAppointment.date.split('T')[0] : '';
        form.start_time = selectedAppointment.start_time;
        form.duration = selectedAppointment.duration?.toString() || '30';
        form.treatment = selectedAppointment.treatment;
        form.status = selectedAppointment.status || 'pending';
        form.notes = selectedAppointment.notes || '';
        isDetailsModalOpen = false;
        isDialogOpen = true;
    }

    function updateStatus(newStatus: string) {
        if (!selectedAppointment) return;
        router.put(
            `/appointments/${selectedAppointment.id}`,
            {
                patient_id: selectedAppointment.patient_id,
                dentist_id: selectedAppointment.dentist_id,
                date: selectedAppointment.date,
                start_time: selectedAppointment.start_time,
                duration: selectedAppointment.duration,
                treatment: selectedAppointment.treatment,
                status: newStatus,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isDetailsModalOpen = false;
                    calendar?.refetchEvents();
                },
            },
        );
    }

    function projectorAction(action: string) {
        if (!selectedAppointment) return;
        router.post(`/appointments/${selectedAppointment.id}/${action}`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                isDetailsModalOpen = false;
                calendar?.refetchEvents();
            }
        });
    }

    let isSendingWa = $state(false);
    function sendReminder() {
        if (!selectedAppointment) return;
        isSendingWa = true;
        router.post(
            `/appointments/${selectedAppointment.id}/remind`,
            {},
            {
                preserveScroll: true,
                onFinish: () => (isSendingWa = false),
            },
        );
    }

    function editFromList(apt: any) {
        selectedAppointment = apt;
        isDetailsModalOpen = true;
    }
</script>

<AppHead title="Citas" />

<div class="flex h-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Gestión de Citas</h1>
            <p class="text-muted-foreground">Administra la agenda y disponibilidad de los dentistas.</p>
        </div>
        <Button onclick={openNewCita} class="bg-blue-600 hover:bg-blue-700">
            Nueva Cita
        </Button>
    </div>

    <Tabs value="calendar" class="w-full flex-1 flex flex-col min-h-0">
        <div class="flex justify-center mb-2">
            <TabsList class="grid w-full max-w-md grid-cols-2">
                <TabsTrigger value="calendar">Calendario</TabsTrigger>
                <TabsTrigger value="list">Lista de Citas</TabsTrigger>
            </TabsList>
        </div>

        <TabsContent value="calendar" class="flex-1 flex flex-col m-0 p-0 overflow-hidden outline-none data-[state=active]:flex data-[state=inactive]:hidden">
            <div class="flex-1 overflow-auto rounded-xl border bg-card p-4 shadow-sm min-h-[500px]">
                <div bind:this={calendarEl} class="h-full"></div>
            </div>
        </TabsContent>

        <TabsContent value="list" class="flex-1 overflow-auto m-0 p-0 outline-none data-[state=active]:block data-[state=inactive]:hidden">
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha y Hora</TableHead>
                            <TableHead>Paciente</TableHead>
                            <TableHead>Dentista</TableHead>
                            <TableHead>Tratamiento</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {#if appointmentsList?.data?.length > 0}
                            {#each appointmentsList.data as apt}
                                <TableRow>
                                    <TableCell>
                                        <div class="font-medium">{apt.date ? apt.date.split('T')[0] : ''}</div>
                                        <div class="text-xs text-muted-foreground">{apt.start_time}</div>
                                    </TableCell>
                                    <TableCell>
                                        {apt.patient?.first_name} {apt.patient?.last_name}
                                    </TableCell>
                                    <TableCell>
                                        Dr/a. {apt.dentist?.name}
                                    </TableCell>
                                    <TableCell>
                                        {apt.treatment}
                                    </TableCell>
                                    <TableCell>
                                        {#if apt.status === 'confirmed'}
                                            <Badge variant="outline" class="bg-emerald-100 text-emerald-800">Confirmada</Badge>
                                        {:else if apt.status === 'cancelled'}
                                            <Badge variant="outline" class="bg-red-100 text-red-800">Cancelada</Badge>
                                        {:else if apt.status === 'completed'}
                                            <Badge variant="outline" class="bg-blue-100 text-blue-800">Completada</Badge>
                                        {:else}
                                            <Badge variant="outline" class="bg-amber-100 text-amber-800">Pendiente</Badge>
                                        {/if}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" onclick={() => editFromList(apt)} title="Ver detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            {/each}
                        {:else}
                            <TableRow>
                                <TableCell colspan={6} class="text-center py-8 text-muted-foreground">
                                    No hay citas registradas.
                                </TableCell>
                            </TableRow>
                        {/if}
                    </TableBody>
                </Table>
            </div>
            
            {#if appointmentsList?.links}
                <div class="flex items-center justify-between px-2 py-4">
                    <div class="text-sm text-muted-foreground">
                        Mostrando {appointmentsList.from || 0} a {appointmentsList.to || 0} de {appointmentsList.total} citas
                    </div>
                    <div class="flex gap-1">
                        {#each appointmentsList.links as link}
                            <Button 
                                variant={link.active ? "default" : "outline"}
                                size="sm"
                                disabled={!link.url}
                                class="h-8 min-w-8 px-2"
                                onclick={() => {
                                    if(link.url) router.get(link.url, {}, { preserveState: true });
                                }}
                            >
                                {@html link.label}
                            </Button>
                        {/each}
                    </div>
                </div>
            {/if}
        </TabsContent>
    </Tabs>
</div>

<!-- Nueva Cita Modal -->
<Dialog bind:open={isDialogOpen}>
    <DialogContent class="sm:max-w-[550px] p-0 overflow-hidden">
        <DialogHeader class="px-6 pt-6 pb-4 border-b">
            <DialogTitle class="text-xl font-semibold">
                {form.id ? 'Editar' : 'Programar Nueva'} Cita
            </DialogTitle>
            <DialogDescription class="text-sm text-gray-500 mt-1">
                Completa los detalles para agendar la cita.
            </DialogDescription>
        </DialogHeader>

        <form id="cita-form" onsubmit={submit} class="flex flex-col">
            <div class="px-6 py-5 space-y-5">
                <!-- Búsqueda de Paciente con dropdown -->
                <div class="space-y-2 relative">
                    <Label class="text-sm font-medium">Paciente *</Label>
                    <div class="relative">
                        <Input
                            type="text"
                            placeholder="Buscar por nombre, DNI o número..."
                            bind:value={patientSearchQuery}
                            class="h-11 rounded-xl pr-8"
                            onfocus={() => (showPatientDropdown = true)}
                            onblur={() =>
                                setTimeout(
                                    () => (showPatientDropdown = false),
                                    200,
                                )}
                        />
                        {#if form.patient_id}
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                onclick={() => {
                                    form.patient_id = '';
                                    patientSearchQuery = '';
                                }}
                            >
                                ×
                            </button>
                        {/if}
                    </div>
                    {#if showPatientDropdown}
                        <div
                            class="absolute z-50 w-full mt-1 max-h-60 overflow-auto rounded-xl border bg-popover text-popover-foreground shadow-md outline-none"
                        >
                            {#if filteredPatients.length === 0}
                                <div
                                    class="p-3 text-sm text-gray-500 text-center"
                                >
                                    No se encontraron pacientes.
                                </div>
                            {:else}
                                {#each filteredPatients as pt}
                                    <button
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-accent hover:text-accent-foreground flex flex-col"
                                        onclick={() => {
                                            form.patient_id = pt.id.toString();
                                            patientSearchQuery = `${pt.first_name} ${pt.last_name} (${pt.dni})`;
                                            showPatientDropdown = false;
                                        }}
                                    >
                                        <span class="font-medium"
                                            >{pt.first_name}
                                            {pt.last_name}</span
                                        >
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >DNI: {pt.dni}
                                            {pt.phone
                                                ? `• Tel: ${pt.phone}`
                                                : ''}</span
                                        >
                                    </button>
                                {/each}
                            {/if}
                        </div>
                    {/if}
                    {#if form.errors.patient_id}
                        <p class="text-xs text-red-500">
                            {form.errors.patient_id}
                        </p>
                    {/if}
                </div>

                <!-- Dentista y Fecha en grid de 2 columnas -->
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Dentista *</Label>
                        <Select bind:value={form.dentist_id} required>
                            <SelectTrigger class="h-11 rounded-xl w-full">
                                {form.dentist_id
                                    ? 'Dra./Dr. ' +
                                      dentists.find(
                                          (d) =>
                                              d.id.toString() ===
                                              form.dentist_id,
                                      )?.name
                                    : 'Seleccionar dentista'}
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                {#each dentists as dentist}
                                    <SelectItem
                                        value={dentist.id.toString()}
                                        class="rounded-lg"
                                    >
                                        Dra./Dr. {dentist.name}
                                    </SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        {#if form.errors.dentist_id}
                            <p class="text-xs text-red-500">
                                {form.errors.dentist_id}
                            </p>
                        {/if}
                    </div>

                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Fecha *</Label>
                        <Input
                            id="fecha"
                            type="date"
                            bind:value={form.date}
                            required
                            class="h-11 rounded-xl w-full"
                        />
                        {#if form.errors.date}
                            <p class="text-xs text-red-500">
                                {form.errors.date}
                            </p>
                        {/if}
                    </div>
                </div>

                <!-- Hora y Tratamiento en grid de 2 columnas -->
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Hora *</Label>
                        {#if isLoadingHours}
                            <div class="h-11 rounded-xl w-full border flex items-center px-3 text-sm text-slate-500 bg-slate-50">Cargando horarios...</div>
                        {:else}
                            <Select bind:value={form.start_time} required disabled={availableSlots.length === 0}>
                                <SelectTrigger class="h-11 rounded-xl w-full">
                                    {form.start_time ? form.start_time : (availableSlots.length > 0 ? 'Seleccionar hora' : 'Sin horarios disponibles')}
                                </SelectTrigger>
                                <SelectContent class="rounded-xl">
                                    {#each availableSlots as slot}
                                        <SelectItem value={slot} class="rounded-lg">{slot}</SelectItem>
                                    {/each}
                                </SelectContent>
                            </Select>
                        {/if}
                        {#if form.errors.start_time}
                            <p class="text-xs text-red-500">
                                {form.errors.start_time}
                            </p>
                        {/if}
                    </div>

                    <div class="space-y-2 relative">
                        <Label class="text-sm font-medium">Tratamiento *</Label>
                        <div class="relative">
                            <Input
                                bind:value={form.treatment}
                                placeholder="Buscar o escribir tratamiento..."
                                class="h-11 rounded-xl w-full pr-10"
                                required
                            />
                            <!-- Simple custom dropdown for search -->
                            {#if form.treatment && treatments.filter(t => t.name.toLowerCase().includes(form.treatment.toLowerCase()) && t.name !== form.treatment).length > 0}
                                <div class="absolute z-10 w-full mt-1 bg-white border rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    {#each treatments.filter(t => t.name.toLowerCase().includes(form.treatment.toLowerCase())) as treatment}
                                        <button 
                                            type="button"
                                            class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 focus:bg-blue-50 outline-none transition-colors"
                                            onclick={() => {
                                                form.treatment = treatment.name;
                                            }}
                                        >
                                            {treatment.name}
                                        </button>
                                    {/each}
                                </div>
                            {/if}
                        </div>
                        {#if form.errors.treatment}
                            <p class="text-xs text-red-500">
                                {form.errors.treatment}
                            </p>
                        {/if}
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-between px-6 py-4 border-t bg-gray-50/50"
            >
                {#if form.id}
                    <Button
                        type="button"
                        variant="destructive"
                        onclick={deleteCita}
                        size="sm"
                        class="gap-2"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 6h18" /><path
                                d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                            /><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        </svg>
                        Eliminar
                    </Button>
                {:else}
                    <div></div>
                {/if}
                <div class="flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onclick={() => (isDialogOpen = false)}
                        size="sm"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        disabled={form.processing}
                        size="sm"
                        class="bg-blue-600 hover:bg-blue-700 gap-2 min-w-[120px]"
                    >
                        {#if form.processing}
                            <Loader2 class="w-4 h-4 animate-spin" />
                            {form.id ? 'Actualizando...' : 'Guardando...'}
                        {:else}
                            {form.id ? 'Actualizar' : 'Guardar'} Cita
                        {/if}
                    </Button>
                </div>
            </div>
        </form>
    </DialogContent>
</Dialog>

<!-- Modal Detalles de Cita -->
<Dialog bind:open={isDetailsModalOpen}>
    <DialogContent class="sm:max-w-[450px] p-0 overflow-hidden">
        <DialogHeader class="px-6 pt-6 pb-4 border-b">
            <DialogTitle
                class="text-xl font-semibold flex items-center justify-between"
            >
                Detalles de Cita
                {#if selectedAppointment?.status === 'confirmed'}
                    <span
                        class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-medium"
                        >Confirmada</span
                    >
                {:else if selectedAppointment?.status === 'pending'}
                    <span
                        class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-medium"
                        >Pendiente</span
                    >
                {:else if selectedAppointment?.status === 'completed'}
                    <span
                        class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 font-medium"
                        >Completada</span
                    >
                {:else if selectedAppointment?.status === 'cancelled'}
                    <span
                        class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 font-medium"
                        >Cancelada</span
                    >
                {/if}
            </DialogTitle>
        </DialogHeader>

        {#if selectedAppointment}
            <div class="px-6 py-5 space-y-4 text-sm">
                <!-- Información del Paciente -->
                <div class="bg-gray-50/50 rounded-xl p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0"
                        >
                            <i class="fa-solid fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">
                                {selectedAppointment.patient?.first_name}
                                {selectedAppointment.patient?.last_name}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Paciente
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Detalles de la Cita -->
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-tooth text-gray-400 mt-0.5 w-5"
                        ></i>
                        <div>
                            <p class="font-medium text-gray-900">
                                {selectedAppointment.treatment}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Tratamiento
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <i
                            class="fa-solid fa-user-doctor text-gray-400 mt-0.5 w-5"
                        ></i>
                        <div>
                            <p class="font-medium text-gray-900">
                                Dra./Dr. {selectedAppointment.dentist
                                    ?.first_name}
                                {selectedAppointment.dentist?.last_name}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Dentista
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-calendar text-gray-400 mt-0.5 w-5"
                        ></i>
                        <div>
                            <p class="font-medium text-gray-900">
                                {selectedAppointment.date ? selectedAppointment.date.split('T')[0] : ''}
                            </p>
                            <p class="text-xs text-muted-foreground">Fecha</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-clock text-gray-400 mt-0.5 w-5"
                        ></i>
                        <div>
                            <p class="font-medium text-gray-900">
                                {selectedAppointment.start_time}
                                <span class="text-xs text-muted-foreground ml-1"
                                    >({selectedAppointment.duration} min)</span
                                >
                            </p>
                            <p class="text-xs text-muted-foreground">Hora</p>
                        </div>
                    </div>

                    {#if selectedAppointment.notes}
                        <div class="flex items-start gap-3">
                            <i
                                class="fa-solid fa-note-sticky text-gray-400 mt-0.5 w-5"
                            ></i>
                            <div>
                                <p class="text-gray-600">
                                    {selectedAppointment.notes}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Notas
                                </p>
                            </div>
                        </div>
                    {/if}
                </div>

                <!-- Acciones Rápidas -->
                <div class="pt-4 border-t">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Proyector / Llamado
                    </p>
                    <div class="flex gap-2 mb-4">
                        {#if !selectedAppointment?.projector_status || selectedAppointment?.projector_status === 'waiting'}
                            <Button variant="outline" size="sm" class="flex-1 bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200" onclick={() => projectorAction('call')}>
                                <i class="fa-solid fa-bullhorn mr-1"></i> Llamar
                            </Button>
                        {:else if selectedAppointment?.projector_status === 'calling'}
                            <Button variant="outline" size="sm" class="flex-1 bg-red-50 text-red-700 hover:bg-red-100 border-red-200 animate-pulse" onclick={() => projectorAction('start')}>
                                <i class="fa-solid fa-play mr-1"></i> Iniciar
                            </Button>
                        {:else if selectedAppointment?.projector_status === 'in_progress'}
                            <Button variant="outline" size="sm" class="flex-1 bg-green-50 text-green-700 hover:bg-green-100 border-green-200" onclick={() => projectorAction('finish')}>
                                <i class="fa-solid fa-check-double mr-1"></i> Finalizar
                            </Button>
                        {:else if selectedAppointment?.projector_status === 'finished'}
                            <div class="flex-1 text-center text-sm font-medium text-slate-500 bg-slate-100 py-1.5 rounded-md border">
                                Terminado
                            </div>
                        {/if}
                    </div>

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Acciones Rápidas
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        {#if selectedAppointment?.status === 'confirmed'}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-11 rounded-xl w-full flex items-center justify-center gap-2 border-orange-200 text-orange-700 hover:bg-orange-50 hover:text-orange-800"
                                onclick={() => updateStatus('pending')}
                            >
                                <i class="fa-solid fa-clock text-sm"></i>
                                Mover a Pendiente
                            </Button>
                        {:else}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-11 rounded-xl w-full flex items-center justify-center gap-2 border-emerald-200 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800"
                                onclick={() => updateStatus('confirmed')}
                            >
                                <i class="fa-solid fa-check text-sm"></i>
                                Confirmar
                            </Button>
                        {/if}

                        <Button
                            type="button"
                            size="sm"
                            class="h-11 rounded-xl w-full flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white"
                            onclick={sendReminder}
                            disabled={isSendingWa}
                        >
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            {isSendingWa ? 'Enviando...' : 'WhatsApp'}
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-11 rounded-xl w-full flex items-center justify-center gap-2 border-blue-200 text-blue-700 hover:bg-blue-50 hover:text-blue-800"
                            onclick={() => updateStatus('completed')}
                        >
                            <i class="fa-solid fa-clipboard-check text-sm"></i>
                            Completada
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-11 rounded-xl w-full flex items-center justify-center gap-2 border-red-200 text-red-700 hover:bg-red-50 hover:text-red-800"
                            onclick={() => updateStatus('cancelled')}
                        >
                            <i class="fa-solid fa-ban text-sm"></i>
                            Cancelar
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Footer con botones consistentes -->
            <div
                class="flex items-center justify-between px-6 py-4 border-t bg-gray-50/50"
            >
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-9 rounded-lg"
                    onclick={() => (isDetailsModalOpen = false)}
                >
                    Cerrar
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="h-9 rounded-lg bg-blue-600 hover:bg-blue-700 gap-2"
                    onclick={openEditForm}
                >
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                    Editar Detalles
                </Button>
            </div>
        {/if}
    </DialogContent>
</Dialog>
