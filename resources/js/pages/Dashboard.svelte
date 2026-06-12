<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Panel Dental',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, usePage, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { Chart, registerables } from 'chart.js';
    import AppHead from '@/components/AppHead.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import PatientFormModal from '@/components/PatientFormModal.svelte';

    Chart.register(...registerables);

    let { kpis, next_appointments, top_treatments, upcoming_birthdays, recent_activities, filters, sales_chart, zones_chart } = $props();

    const page = usePage<any>();
    const userName = $derived(page.props.auth?.user?.first_name || 'Admin');
    let isPatientModalOpen = $state(false);

    // Saludo dinámico según la hora
    const currentHour = new Date().getHours();
    let greeting = 'Buenos días';
    if (currentHour >= 12 && currentHour < 18) {
        greeting = 'Buenas tardes';
    } else if (currentHour >= 18) {
        greeting = 'Buenas noches';
    }

    // Filtros de fecha
    const applyFilter = (period: string) => {
        let start_date = new Date();
        let end_date = new Date();

        if (period === 'today') {
            // Ya es hoy
        } else if (period === 'week') {
            start_date.setDate(start_date.getDate() - start_date.getDay() + 1); // Lunes
        } else if (period === 'month') {
            start_date.setDate(1);
        } else if (period === 'year') {
            start_date.setMonth(0, 1);
        }

        router.get(dashboard(), {
            start_date: start_date.toISOString().split('T')[0],
            end_date: end_date.toISOString().split('T')[0]
        }, { preserveState: true, preserveScroll: true });
    };

    // Métricas del consultorio dental
    const metrics = $derived([
        {
            id: 1,
            title: 'Nuevos Pacientes',
            value: kpis?.new_patients || 0,
            change: `Total histórico: ${kpis?.total_patients || 0}`,
            trend: 'up',
            icon: 'fa-solid fa-users',
            color: 'from-blue-500 to-blue-600',
        },
        {
            id: 2,
            title: 'Citas Hoy',
            value: kpis?.appointments_today || 0,
            change: 'En el día de hoy',
            trend: 'neutral',
            icon: 'fa-solid fa-calendar-day',
            color: 'from-green-500 to-green-600',
        },
        {
            id: 3,
            title: 'Ingresos',
            value: `S/ ${kpis?.revenue_period ? parseFloat(kpis.revenue_period).toLocaleString('es-PE', {minimumFractionDigits:2}) : '0.00'}`,
            change: 'En el periodo seleccionado',
            trend: 'neutral',
            icon: 'fa-solid fa-sack-dollar',
            color: 'from-purple-500 to-purple-600',
        },
        {
            id: 4,
            title: 'Deuda Pendiente',
            value: `S/ ${kpis?.pending_payments ? parseFloat(kpis.pending_payments).toLocaleString('es-PE', {minimumFractionDigits:2}) : '0.00'}`,
            change: 'Por cobrar',
            trend: 'down',
            icon: 'fa-solid fa-file-invoice-dollar',
            color: 'from-orange-500 to-orange-600',
        },
    ]);

    // Próximas citas desde DB
    const upcomingAppointments = $derived((next_appointments || []).map((app: any) => {
        const firstName = app.patient?.first_name || '';
        const lastName = app.patient?.last_name || '';

        return {
            id: app.id,
            patient: `${firstName} ${lastName}`.trim() || 'Desconocido',
            time: app.start_time ? app.start_time.substring(0, 5) : '',
            treatment: app.treatment || 'Consulta',
            status: app.status === 'confirmed' ? 'confirmada' : 'pendiente',
            projector_status: app.projector_status,
            // Iniciales correctas para el avatar (ej: Juan Perez -> JP)
            avatar: `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() || 'NA',
        };
    }));

    // Gráficos
    let salesChartCanvas = $state<HTMLCanvasElement | null>(null);
    let zonesChartCanvas = $state<HTMLCanvasElement | null>(null);
    let salesChartInstance: Chart | null = null;
    let zonesChartInstance: Chart | null = null;

    $effect(() => {
        if (salesChartCanvas && sales_chart) {
            if (salesChartInstance) salesChartInstance.destroy();
            salesChartInstance = new Chart(salesChartCanvas, {
                type: 'line',
                data: {
                    labels: sales_chart.map((s: any) => s.date),
                    datasets: [{
                        label: 'Ingresos S/',
                        data: sales_chart.map((s: any) => s.total),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });

    $effect(() => {
        if (zonesChartCanvas && zones_chart) {
            if (zonesChartInstance) zonesChartInstance.destroy();
            const colors = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#4f46e5', '#818cf8'];
            zonesChartInstance = new Chart(zonesChartCanvas, {
                type: 'doughnut',
                data: {
                    labels: zones_chart.map((z: any) => z.name),
                    datasets: [{
                        data: zones_chart.map((z: any) => z.count),
                        backgroundColor: colors.slice(0, zones_chart.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }
    });
</script>

<AppHead title="Panel Dental" />

<div class="flex h-full flex-1 flex-col gap-6 overflow-y-auto overflow-x-hidden rounded-xl bg-slate-50/50 p-6 dark:bg-slate-950/50">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-3xl font-bold tracking-tight text-transparent dark:from-slate-100 dark:to-slate-400">
                {greeting}, {userName}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Aquí tienes el resumen del día
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 items-center">
            <div class="bg-white dark:bg-slate-900 p-1 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 flex text-sm">
                <button onclick={() => applyFilter('today')} class="px-3 py-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Hoy</button>
                <button onclick={() => applyFilter('week')} class="px-3 py-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Semana</button>
                <button onclick={() => applyFilter('month')} class="px-3 py-1.5 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-medium transition-colors">Mes</button>
                <button onclick={() => applyFilter('year')} class="px-3 py-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Año</button>
            </div>
            <Button variant="dental-primary"  class="shadow-sm cursor-pointer" onclick={() => router.visit('/appointments?create=true')}>
                    <i class="fa-solid fa-calendar-plus mr-2"></i>
                    Nueva Cita
            </Button>
            <Button variant="outline" class="shadow-sm" onclick={() => isPatientModalOpen = true}>
                <i class="fa-solid fa-user-plus mr-2"></i>
                Nuevo Paciente
            </Button>
        </div>
    </div>

    <PatientFormModal bind:isOpen={isPatientModalOpen} />

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {#each metrics as metric (metric.id)}
            <Card class="border-0 shadow-sm transition-all duration-300 hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {metric.title}
                    </CardTitle>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br {metric.color} shadow-sm">
                        <i class="{metric.icon} h-4 w-4 text-white flex items-center justify-center"></i>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold tracking-tight">{metric.value}</div>
                    <p class="mt-1 flex items-center text-xs {metric.trend === 'up' ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}">
                        {#if metric.trend === 'up'}
                            <i class="fa-solid fa-arrow-trend-up mr-1 text-[10px]"></i>
                        {/if}
                        {metric.change}
                    </p>
                </CardContent>
            </Card>
        {/each}
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <!-- Gráfico Lineal de Ventas -->
        <div class="lg:col-span-2">
            <Card class="border-0 shadow-sm h-full">
                <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <i class="fa-solid fa-chart-line text-blue-600"></i>
                        Ingresos Generados
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-6">
                    {#if !sales_chart || sales_chart.length === 0}
                        <div class="flex h-[300px] items-center justify-center text-slate-500">
                            No hay datos de ventas en este periodo.
                        </div>
                    {:else}
                        <div class="relative h-[300px] w-full">
                            <canvas bind:this={salesChartCanvas}></canvas>
                        </div>
                    {/if}
                </CardContent>
            </Card>
        </div>

        <!-- Gráfico Circular de Zonas -->
        <div class="lg:col-span-1">
            <Card class="border-0 shadow-sm h-full">
                <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <i class="fa-solid fa-map-location-dot text-indigo-600"></i>
                        Zonas Frecuentes
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-6">
                    {#if !zones_chart || zones_chart.length === 0}
                        <div class="flex h-[300px] items-center justify-center text-center text-slate-500 text-sm">
                            <p>No hay pacientes con dirección registrada.</p>
                        </div>
                    {:else}
                        <div class="relative h-[300px] w-full">
                            <canvas bind:this={zonesChartCanvas}></canvas>
                        </div>
                    {/if}
                </CardContent>
            </Card>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="lg:col-span-2 space-y-6">
            <Card class="border-0 shadow-sm">
                <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <i class="fa-solid fa-calendar-check text-blue-600"></i>
                            Próximas Citas
                        </CardTitle>
                        <Badge variant="secondary" class="font-normal">
                            Hoy: {upcomingAppointments.length} programadas
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y">
                        {#if upcomingAppointments.length === 0}
                            <div class="p-6 text-center text-slate-500">
                                <i class="fa-regular fa-calendar-xmark text-4xl mb-3 opacity-50"></i>
                                <p>No hay citas programadas próximamente.</p>
                            </div>
                        {/if}
                        {#each upcomingAppointments as appointment (appointment.id)}
                            <div class="flex items-center justify-between p-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-900/50">
                                <div class="flex items-center gap-4">
                                    <Avatar class="h-10 w-10 shadow-sm">
                                        <AvatarFallback class="bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700 dark:from-blue-900 dark:to-blue-800 dark:text-blue-200 font-medium">
                                            {appointment.avatar}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="font-medium text-sm md:text-base text-slate-900 dark:text-slate-100">
                                            {appointment.patient}
                                        </p>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>{appointment.time}</span>
                                            <span>•</span>
                                            <span class="truncate max-w-[120px] md:max-w-none">{appointment.treatment}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Badge class={appointment.status === 'confirmada'
                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400'}
                                           variant="outline">
                                        {#if appointment.status === 'confirmada'}
                                            <i class="fa-solid fa-check mr-1"></i> Confirmada
                                        {:else}
                                            <i class="fa-solid fa-hourglass-half mr-1"></i> Pendiente
                                        {/if}
                                    </Badge>
                                    <div class="flex items-center gap-1">
                                        {#if !appointment.projector_status || appointment.projector_status === 'waiting'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200" onclick={() => router.post(`/appointments/${appointment.id}/call`)} title="Llamar a pantalla">
                                                <i class="fa-solid fa-bullhorn mr-1"></i> Llamar
                                            </Button>
                                        {:else if appointment.projector_status === 'calling'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs bg-red-50 text-red-700 hover:bg-red-100 border-red-200 animate-pulse" onclick={() => router.post(`/appointments/${appointment.id}/start`)} title="Iniciar atención">
                                                <i class="fa-solid fa-play mr-1"></i> Iniciar
                                            </Button>
                                        {:else if appointment.projector_status === 'in_progress'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs bg-green-50 text-green-700 hover:bg-green-100 border-green-200" onclick={() => router.post(`/appointments/${appointment.id}/finish`)} title="Finalizar atención">
                                                <i class="fa-solid fa-check-double mr-1"></i> Finalizar
                                            </Button>
                                        {:else if appointment.projector_status === 'finished'}
                                            <Badge variant="secondary" class="h-7 text-xs bg-slate-100 text-slate-500">
                                                Terminado
                                            </Badge>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm">
                <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <i class="fa-solid fa-chart-line text-green-600"></i>
                        Actividad Reciente
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y">
                        {#if !recent_activities || recent_activities.length === 0}
                            <div class="p-6 text-center text-slate-500">
                                <p>No hay actividad reciente.</p>
                            </div>
                        {/if}
                        {#each (recent_activities || []) as activity (activity.id)}
                            <div class="flex items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1">
                                        {#if activity.type === 'payment'}
                                            <i class="fa-solid fa-money-bill-wave text-green-500"></i>
                                        {:else}
                                            <i class="fa-solid fa-tooth text-blue-500"></i>
                                        {/if}
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm">{activity.title}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{activity.description}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-muted-foreground whitespace-nowrap ml-4">{activity.time}</span>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="space-y-6">

            <Card class="border-0 shadow-sm">
                <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <i class="fa-solid fa-stethoscope text-purple-600"></i>
                        Tratamientos Top
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y">
                        {#if !top_treatments || top_treatments.length === 0}
                            <div class="p-6 text-center text-slate-500 text-sm">
                                <p>No hay tratamientos registrados en el periodo.</p>
                            </div>
                        {/if}
                        {#each (top_treatments || []) as treatment (treatment.id)}
                            <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="font-medium text-sm">{treatment.name}</span>
                                    <Badge variant="secondary" class="text-[10px] uppercase font-semibold tracking-wider">
                                        {treatment.count} casos
                                    </Badge>
                                </div>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>

            <Card class="border-0 shadow-sm bg-gradient-to-br from-pink-50 to-rose-50 dark:from-pink-950/20 dark:to-rose-950/20">
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base text-pink-900 dark:text-pink-200">
                        <i class="fa-solid fa-cake-candles text-pink-500"></i>
                        Próximos Cumpleaños
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        {#if !upcoming_birthdays || upcoming_birthdays.length === 0}
                            <div class="text-sm text-pink-700/70 dark:text-pink-300/70">No hay cumpleaños en los próximos 30 días.</div>
                        {/if}
                        {#each (upcoming_birthdays || []) as birthday (birthday.id)}
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                        {birthday.name}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        Cumple {birthday.age} años
                                    </p>
                                </div>
                                <Badge variant="outline" class="bg-white/50 dark:bg-black/20 text-xs border-pink-200 dark:border-pink-800 font-medium text-pink-700">
                                    <i class="fa-regular fa-calendar mr-1"></i> {birthday.date}
                                </Badge>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>

        </div>
    </div>
</div>
