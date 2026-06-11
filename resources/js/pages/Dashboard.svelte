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
    import {
        Activity,
        AlertCircle,
        Calendar,
        Clock,
        DollarSign,
        TrendingUp,
        Users,
    } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    // Importaciones ordenadas correctamente
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { usePage } from '@inertiajs/svelte';

    let { kpis, next_appointments, appointments_by_status } = $props();

    const page = usePage<any>();
    const userName = $derived(page.props.auth?.user?.first_name || 'Admin');
    
    // Saludo dinámico según la hora
    const currentHour = new Date().getHours();
    let greeting = 'Buenos días';
    if (currentHour >= 12 && currentHour < 18) {
        greeting = 'Buenas tardes';
    } else if (currentHour >= 18) {
        greeting = 'Buenas noches';
    }

    // Métricas del consultorio dental (con IDs para keys)
    const metrics = $derived([
        {
            id: 1,
            title: 'Pacientes Activos',
            value: kpis?.total_patients || 0,
            change: '1 2 este mes',
            icon: Users,
            color: 'from-blue-500 to-blue-600',
        },
        {
            id: 2,
            title: 'Citas Hoy',
            value: kpis?.appointments_today || 0,
            change: '0 pendientes',
            icon: Calendar,
            color: 'from-green-500 to-green-600',
        },
        {
            id: 3,
            title: 'Ingresos del Mes',
            value: `S/ ${kpis?.revenue_this_month || 0}`,
            change: 'Primer mes de datos',
            icon: DollarSign,
            color: 'from-purple-500 to-purple-600',
        },
        {
            id: 4,
            title: 'Deuda Pendiente',
            value: `S/ ${kpis?.pending_payments || 0}`,
            change: 'Por cobrar',
            icon: Activity,
            color: 'from-orange-500 to-orange-600',
        },
    ]);

    // Próximas citas desde DB
    const upcomingAppointments = $derived((next_appointments || []).map((app: any) => ({
        id: app.id,
        patient: app.patient ? `${app.patient.first_name} ${app.patient.last_name}` : 'Desconocido',
        time: app.start_time ? app.start_time.substring(0, 5) : '',
        treatment: app.treatment || 'Consulta',
        status: app.status === 'confirmed' ? 'confirmada' : 'pendiente',
        avatar: app.patient ? app.patient.first_name.substring(0,2).toUpperCase() : 'NA',
    })));

    // Tratamientos más comunes
    const topTreatments = [
        {
            id: 1,
            name: 'Limpieza Dental',
            count: 156,
            revenue: '$12,480',
            trend: '+15%',
        },
        {
            id: 2,
            name: 'Ortodoncia',
            count: 89,
            revenue: '$89,000',
            trend: '+8%',
        },
        {
            id: 3,
            name: 'Endodoncia',
            count: 67,
            revenue: '$53,600',
            trend: '+12%',
        },
        {
            id: 4,
            name: 'Implantes',
            count: 34,
            revenue: '$102,000',
            trend: '+20%',
        },
    ];

    // Próximos cumpleaños
    const upcomingBirthdays = [
        { id: 1, name: 'Sofía Ramírez', date: 'Mañana', age: 34 },
        { id: 2, name: 'José Martínez', date: 'En 3 días', age: 45 },
        { id: 3, name: 'Carmen Vega', date: 'En 5 días', age: 28 },
    ];

    // Alertas del sistema
    const alerts = [
        {
            id: 1,
            type: 'inventory',
            message: 'Materiales para endodoncia bajo stock',
            severity: 'warning',
        },
        {
            id: 2,
            type: 'payment',
            message: 'Pagos pendientes: $3,450',
            severity: 'info',
        },
        {
            id: 3,
            type: 'appointment',
            message: '3 pacientes no han confirmado cita',
            severity: 'warning',
        },
    ];

    // Actividad reciente
    const recentActivities = [
        {
            id: 1,
            title: 'Nuevo paciente registrado',
            description: 'Paciente: Sofía Ramírez - Tratamiento: Ortodoncia',
            time: 'Hace 2 horas',
        },
        {
            id: 2,
            title: 'Cita completada',
            description: 'Carlos López - Endodoncia exitosa',
            time: 'Hace 4 horas',
        },
        {
            id: 3,
            title: 'Pago registrado',
            description: 'María González - $1,200 por tratamiento',
            time: 'Ayer',
        },
    ];
</script>

<AppHead title="Panel Dental" />

<div
    class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-linear-to-br from-slate-50 to-white p-6 dark:from-slate-950 dark:to-slate-900"
>
    <!-- Header con bienvenida personalizada -->
    <div
        class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
        <div>
            <h1
                class="bg-linear-to-r from-slate-900 to-slate-600 bg-clip-text text-3xl font-bold tracking-tight text-transparent dark:from-slate-100 dark:to-slate-400"
            >
                {greeting}, {userName}
            </h1>
            <p class="mt-1 text-muted-foreground">
                Aquí tienes el resumen del día
            </p>
        </div>
        <div class="flex gap-2">
            <Button
                class="bg-linear-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800"
            >
                <Calendar class="mr-2 h-4 w-4" />
                Nueva Cita
            </Button>
            <Button variant="outline">
                <Users class="mr-2 h-4 w-4" />
                Nuevo Paciente
            </Button>
        </div>
    </div>

    <!-- Métricas Principales - Key añadida -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {#each metrics as metric (metric.id)}
            <Card
                class="border-0 shadow-lg transition-shadow duration-300 hover:shadow-xl"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground"
                    >
                        {metric.title}
                    </CardTitle>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-linear-to-r {metric.color}"
                    >
                        <metric.icon class="h-4 w-4 text-white" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{metric.value}</div>
                    <p
                        class="mt-1 flex items-center text-xs text-green-600 dark:text-green-400"
                    >
                        <TrendingUp class="mr-1 h-3 w-3" />
                        {metric.change} desde el mes pasado
                    </p>
                </CardContent>
            </Card>
        {/each}
    </div>

    <!-- Contenido Principal Grid -->
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Próximas Citas - Ocupa 2 columnas en desktop -->
        <div class="lg:col-span-2">
            <Card class="border-0 shadow-lg">
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5 text-blue-600" />
                            Próximas Citas
                        </CardTitle>
                        <Badge variant="outline" class="text-xs">
                            Hoy: 5 citas programadas
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y">
                        {#each upcomingAppointments as appointment (appointment.id)}
                            <div
                                class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-10 w-10">
                                        <AvatarFallback
                                            class="bg-linear-to-br from-blue-500 to-cyan-500 text-white"
                                        >
                                            {appointment.avatar}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="font-medium">
                                            {appointment.patient}
                                        </p>
                                        <div
                                            class="flex items-center gap-2 text-sm text-muted-foreground"
                                        >
                                            <Clock class="h-3 w-3" />
                                            <span>{appointment.time} AM</span>
                                            <span>•</span>
                                            <span>{appointment.treatment}</span>
                                        </div>
                                    </div>
                                </div>
                                <Badge
                                    variant={appointment.status === 'confirmada'
                                        ? 'default'
                                        : 'secondary'}
                                >
                                    {appointment.status === 'confirmada'
                                        ? '✓ Confirmada'
                                        : '⏳ Pendiente'}
                                </Badge>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Sidebar Derecha -->
        <div class="space-y-6">
            <!-- Tratamientos Populares -->
            <Card class="border-0 shadow-lg">
                <CardHeader class="border-b">
                    <CardTitle class="flex items-center gap-2">
                        <span class="h-5 w-5 text-purple-600">🦷</span>
                        Tratamientos Populares
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y">
                        {#each topTreatments as treatment (treatment.id)}
                            <div class="p-4">
                                <div
                                    class="mb-1 flex items-center justify-between"
                                >
                                    <span class="font-medium"
                                        >{treatment.name}</span
                                    >
                                    <Badge variant="secondary" class="text-xs">
                                        {treatment.count} casos
                                    </Badge>
                                </div>
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-muted-foreground"
                                        >{treatment.revenue}</span
                                    >
                                    <span
                                        class="flex items-center text-xs text-green-600"
                                    >
                                        <TrendingUp class="mr-1 h-2 w-2" />
                                        {treatment.trend}
                                    </span>
                                </div>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>

            <!-- Próximos Cumpleaños -->
            <Card
                class="border-0 shadow-lg bg-linear-to-r from-pink-50 to-rose-50 dark:from-pink-950/20 dark:to-rose-950/20"
            >
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <span>🎂</span>
                        Próximos Cumpleaños
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        {#each upcomingBirthdays as birthday (birthday.id)}
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium">
                                        {birthday.name}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {birthday.age} años
                                    </p>
                                </div>
                                <Badge variant="outline" class="text-xs">
                                    {birthday.date}
                                </Badge>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>

            <!-- Alertas y Recordatorios -->
            <Card class="border-0 shadow-lg bg-amber-50 dark:bg-amber-950/20">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <AlertCircle class="h-4 w-4 text-amber-600" />
                        Recordatorios
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        {#each alerts as alert (alert.id)}
                            <div class="flex items-start gap-2 text-sm">
                                <div
                                    class="mt-1.5 h-2 w-2 rounded-full bg-amber-500"
                                ></div>
                                <p class="text-muted-foreground">
                                    {alert.message}
                                </p>
                            </div>
                        {/each}
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <Card class="border-0 shadow-lg">
        <CardHeader class="border-b">
            <CardTitle class="flex items-center gap-2">
                <Activity class="h-5 w-5 text-green-600" />
                Actividad Reciente
            </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div class="divide-y">
                {#each recentActivities as activity (activity.id)}
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium">{activity.title}</p>
                            <p class="text-sm text-muted-foreground">
                                {activity.description}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground"
                            >{activity.time}</span
                        >
                    </div>
                {/each}
            </div>
        </CardContent>
    </Card>
</div>
