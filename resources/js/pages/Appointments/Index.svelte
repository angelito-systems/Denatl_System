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
    import { store } from '@/routes/appointments';

    let {
        appointments = [],
        patients = [],
        dentists = [],
    } = $props();

    let calendarEl: HTMLElement;
    let calendar: Calendar;

    // Map appointments to FullCalendar event format
    let events = $derived(appointments.map(appointment => ({
        id: appointment.id,
        title: `${appointment.patient?.first_name} - ${appointment.treatment}`,
        start: `${appointment.date}T${appointment.start_time}`,
        // Simplification for end time based on duration (minutes)
        end: new Date(new Date(`${appointment.date}T${appointment.start_time}`).getTime() + appointment.duration * 60000).toISOString(),
        backgroundColor: appointment.status === 'confirmed' ? '#16a34a' : '#3b82f6',
        extendedProps: { ...appointment }
    })));

    let isDialogOpen = $state(false);

    const form = useForm({
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
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            allDaySlot: false,
            selectable: true,
            events: events,
            select: handleDateSelect,
            eventClick: handleEventClick,
            locale: 'es'
        });
        calendar.render();

        return () => {
            calendar.destroy();
        };
    });

    // Update events when they change
    $effect(() => {
        if (calendar) {
            calendar.removeAllEvents();
            calendar.addEventSource(events);
        }
    });

    function handleDateSelect(selectInfo: any) {
        // Formatear la fecha para el input
        const dateStr = selectInfo.startStr.split('T')[0];
        const timeStr = selectInfo.startStr.split('T')[1]?.substring(0, 5) || '09:00';
        
        form.date = dateStr;
        form.start_time = timeStr;
        isDialogOpen = true;
    }

    function handleEventClick(clickInfo: any) {
        // En una implementación real, esto abriría un modal para editar la cita
        alert('Cita: ' + clickInfo.event.title + '\nTratamiento: ' + clickInfo.event.extendedProps.treatment);
    }

    function openNewCita() {
        const now = new Date();
        form.date = now.toISOString().split('T')[0];
        form.start_time = '09:00';
        isDialogOpen = true;
    }

    function submit(e: Event) {
        e.preventDefault();
        form.post(store(), {
            onSuccess: () => {
                isDialogOpen = false;
                form.reset();
            }
        });
    }
</script>

<AppHead title="Calendario de Citas" />

<div class="flex h-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Calendario de Citas</h1>
            <p class="text-muted-foreground">Gestiona la agenda y disponibilidad de los dentistas.</p>
        </div>
        <Button onclick={openNewCita} class="bg-blue-600 hover:bg-blue-700">
            Nueva Cita
        </Button>
    </div>

    <!-- Calendar Container -->
    <div class="rounded-xl border bg-card text-card-foreground shadow-sm p-6 flex-1 min-h-[600px] overflow-x-auto">
        <div class="min-w-[800px] h-full" bind:this={calendarEl}>
            <!-- Vanilla FullCalendar will mount here -->
        </div>
    </div>
</div>

<!-- Nueva Cita Modal -->
<Dialog bind:open={isDialogOpen}>
    <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
            <DialogTitle>Programar Nueva Cita</DialogTitle>
            <DialogDescription>
                Completa los detalles para agendar la cita.
            </DialogDescription>
        </DialogHeader>
        
        <form id="cita-form" onsubmit={submit}>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="paciente" class="text-right">Paciente *</Label>
                    <div class="col-span-3">
                        <Select bind:value={form.patient_id} required>
                            <SelectTrigger>
                                {form.patient_id ? patients.find(p => p.id.toString() === form.patient_id)?.first_name + ' ' + patients.find(p => p.id.toString() === form.patient_id)?.last_name : 'Seleccionar paciente'}
                            </SelectTrigger>
                            <SelectContent>
                                {#each patients as patient}
                                    <SelectItem value={patient.id.toString()}>
                                        {patient.first_name} {patient.last_name} ({patient.dni})
                                    </SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.patient_id} />
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="dentista" class="text-right">Dentista *</Label>
                    <div class="col-span-3">
                        <Select bind:value={form.dentist_id} required>
                            <SelectTrigger>
                                {form.dentist_id ? 'Dra./Dr. ' + dentists.find(d => d.id.toString() === form.dentist_id)?.name : 'Seleccionar dentista'}
                            </SelectTrigger>
                            <SelectContent>
                                {#each dentists as dentist}
                                    <SelectItem value={dentist.id.toString()}>
                                        Dra./Dr. {dentist.name}
                                    </SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.dentist_id} />
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="fecha" class="text-right">Fecha *</Label>
                    <div class="col-span-3">
                        <Input id="fecha" type="date" bind:value={form.date} required />
                        <InputError message={form.errors.date} />
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="hora_inicio" class="text-right">Hora *</Label>
                    <div class="col-span-3">
                        <Input id="hora_inicio" type="time" bind:value={form.start_time} required />
                        <InputError message={form.errors.start_time} />
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="tratamiento" class="text-right">Tratamiento *</Label>
                    <div class="col-span-3">
                        <Input id="tratamiento" bind:value={form.treatment} placeholder="Ej: Limpieza dental, Ortodoncia" required />
                        <InputError message={form.errors.treatment} />
                    </div>
                </div>
            </div>
            
            <DialogFooter>
                <Button type="button" variant="outline" onclick={() => isDialogOpen = false}>Cancelar</Button>
                <Button type="submit" disabled={form.processing} class="bg-blue-600">Guardar Cita</Button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>
