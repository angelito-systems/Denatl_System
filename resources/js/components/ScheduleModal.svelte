<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { CalendarDays, Save, Loader2 } from 'lucide-svelte';
    import { useForm } from '@inertiajs/svelte';
    import { toast } from 'svelte-sonner';

    export let isOpen = false;
    export let user: any = null;

    const days = [
        { id: 1, name: 'Lunes' },
        { id: 2, name: 'Martes' },
        { id: 3, name: 'Miércoles' },
        { id: 4, name: 'Jueves' },
        { id: 5, name: 'Viernes' },
        { id: 6, name: 'Sábado' },
        { id: 7, name: 'Domingo' }
    ];

    let defaultSchedule = {
        start_time: '09:00',
        end_time: '18:00',
        break_start: '13:00',
        break_end: '14:00',
        is_working: false
    };

    let scheduleForm = useForm({
        schedules: [] as any[]
    });

    $: if (isOpen && user) {
        initForm();
    }

    function initForm() {
        let existing = user.schedules || [];
        
        scheduleForm.schedules = days.map(day => {
            let found = existing.find((s: any) => s.day_of_week === day.id);
            if (found) {
                // Strip seconds from times if present
                return {
                    day_of_week: day.id,
                    name: day.name,
                    start_time: found.start_time ? found.start_time.substring(0, 5) : '',
                    end_time: found.end_time ? found.end_time.substring(0, 5) : '',
                    break_start: found.break_start ? found.break_start.substring(0, 5) : '',
                    break_end: found.break_end ? found.break_end.substring(0, 5) : '',
                    is_working: found.is_working
                };
            }
            return {
                day_of_week: day.id,
                name: day.name,
                ...defaultSchedule,
                is_working: day.id >= 1 && day.id <= 5 // L-V working by default if none exist
            };
        });
    }

    function saveSchedule(e: Event) {
        e.preventDefault();
        scheduleForm.post(`/staff/${user.id}/schedule`, {
            preserveScroll: true,
            onSuccess: () => {
                isOpen = false;
                toast.success('Horario laboral actualizado');
            }
        });
    }
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <CalendarDays class="w-5 h-5 text-blue-600" />
                Horario de {user?.first_name} {user?.last_name}
            </DialogTitle>
            <DialogDescription>
                Configura los días laborales, horas de atención y recesos (almuerzo) de este doctor.
            </DialogDescription>
        </DialogHeader>

        <form onsubmit={saveSchedule} class="space-y-6 pt-4">
            
            <div class="rounded-md border divide-y">
                <div class="grid grid-cols-12 gap-4 p-3 bg-muted/50 font-medium text-xs text-muted-foreground uppercase">
                    <div class="col-span-3">Día</div>
                    <div class="col-span-3 text-center">Horario Atención</div>
                    <div class="col-span-6 text-center">Refrigerio / Almuerzo (Opcional)</div>
                </div>

                {#each scheduleForm.schedules as schedule, i}
                    <div class="grid grid-cols-12 gap-4 p-3 items-center {schedule.is_working ? '' : 'opacity-50 bg-slate-50 dark:bg-slate-900/50'}">
                        <div class="col-span-3 flex items-center space-x-2">
                            <Checkbox id="day-{schedule.day_of_week}" bind:checked={schedule.is_working} />
                            <Label for="day-{schedule.day_of_week}" class="font-medium cursor-pointer">{schedule.name}</Label>
                        </div>
                        
                        <div class="col-span-3 flex items-center justify-center gap-1">
                            {#if schedule.is_working}
                                <Input type="time" bind:value={schedule.start_time} class="h-8 px-2 w-[85px] text-xs" required />
                                <span class="text-muted-foreground">-</span>
                                <Input type="time" bind:value={schedule.end_time} class="h-8 px-2 w-[85px] text-xs" required />
                            {:else}
                                <span class="text-xs text-muted-foreground">No trabaja</span>
                            {/if}
                        </div>

                        <div class="col-span-6 flex items-center justify-center gap-1">
                            {#if schedule.is_working}
                                <span class="text-xs text-muted-foreground mr-1">Desde:</span>
                                <Input type="time" bind:value={schedule.break_start} class="h-8 px-2 w-[85px] text-xs" />
                                <span class="text-xs text-muted-foreground ml-2 mr-1">Hasta:</span>
                                <Input type="time" bind:value={schedule.break_end} class="h-8 px-2 w-[85px] text-xs" />
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <Button variant="outline" type="button" onclick={() => isOpen = false}>
                    Cancelar
                </Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={scheduleForm.processing}>
                    {#if scheduleForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        <Save class="w-4 h-4 mr-2" /> Guardar Horario
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
