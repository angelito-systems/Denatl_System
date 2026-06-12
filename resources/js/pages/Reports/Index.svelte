<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Reportes', href: '/reportes' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Chart from 'chart.js/auto';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Download, Calendar, Filter, Search } from 'lucide-svelte';
    import { router, usePage } from '@inertiajs/svelte';
    import { index as reportsIndex } from '@/routes/reportes';
    
    let { revenueByMonth, treatmentsByCategory, paymentsByMethod, kpis, filters, staffProductivity, topPatients, contractsChart, newPatientsByMonth } = $props();

    const page = usePage<any>();
    let errors = $derived(page.props.errors || {});

    // Elements
    let revenueChartEl = $state<HTMLCanvasElement | null>(null);
    let treatmentsChartEl = $state<HTMLCanvasElement | null>(null);
    let productivityChartEl = $state<HTMLCanvasElement | null>(null);
    let patientsChartEl = $state<HTMLCanvasElement | null>(null);
    let contractsChartEl = $state<HTMLCanvasElement | null>(null);

    // Instances
    let revenueChartInst: Chart | null = null;
    let treatmentsChartInst: Chart | null = null;
    let productivityChartInst: Chart | null = null;
    let patientsChartInst: Chart | null = null;
    let contractsChartInst: Chart | null = null;
    
    let activeTab = $state('graficos');
    
    let startDate = $state(filters?.start_date || '');
    let endDate = $state(filters?.end_date || '');
    let patientSearch = $state(filters?.patient_search || '');

    function filterReports(e: Event) {
        e.preventDefault();
        router.get(reportsIndex(), { start_date: startDate, end_date: endDate, patient_search: patientSearch }, { preserveState: true });
    }

    function exportPdf() {
        const url = new URL(window.location.origin + '/reportes/export');
        if (startDate) url.searchParams.append('start_date', startDate);
        if (endDate) url.searchParams.append('end_date', endDate);
        if (patientSearch) url.searchParams.append('patient_search', patientSearch);
        window.open(url.toString(), '_blank');
    }

    $effect(() => {
        if (revenueChartEl && revenueByMonth) {
            if (revenueChartInst) revenueChartInst.destroy();
            revenueChartInst = new Chart(revenueChartEl, {
                type: 'bar',
                data: {
                    labels: revenueByMonth.map((r: any) => r.month).reverse(),
                    datasets: [{
                        label: 'Ingresos Históricos (S/)',
                        data: revenueByMonth.map((r: any) => r.revenue).reverse(),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        if (treatmentsChartEl && treatmentsByCategory) {
            if (treatmentsChartInst) treatmentsChartInst.destroy();
            treatmentsChartInst = new Chart(treatmentsChartEl, {
                type: 'pie',
                data: {
                    labels: treatmentsByCategory.map((t: any) => t.category),
                    datasets: [{
                        data: treatmentsByCategory.map((t: any) => t.count),
                        backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#06b6d4', '#6366f1', '#d946ef']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        if (productivityChartEl && staffProductivity) {
            if (productivityChartInst) productivityChartInst.destroy();
            productivityChartInst = new Chart(productivityChartEl, {
                type: 'bar',
                data: {
                    labels: staffProductivity.map((p: any) => p.name),
                    datasets: [{
                        label: 'Citas Atendidas',
                        data: staffProductivity.map((p: any) => p.count),
                        backgroundColor: '#8b5cf6',
                        borderRadius: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y' }
            });
        }

        if (patientsChartEl && newPatientsByMonth) {
            if (patientsChartInst) patientsChartInst.destroy();
            patientsChartInst = new Chart(patientsChartEl, {
                type: 'line',
                data: {
                    labels: newPatientsByMonth.map((p: any) => p.month).reverse(),
                    datasets: [{
                        label: 'Nuevos Pacientes',
                        data: newPatientsByMonth.map((p: any) => p.count).reverse(),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        if (contractsChartEl && contractsChart) {
            if (contractsChartInst) contractsChartInst.destroy();
            contractsChartInst = new Chart(contractsChartEl, {
                type: 'doughnut',
                data: {
                    labels: contractsChart.map((c: any) => c.name),
                    datasets: [{
                        data: contractsChart.map((c: any) => c.value),
                        backgroundColor: ['#22c55e', '#ef4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%' }
            });
        }
    });
</script>

<AppHead title="Reportes y Estadísticas" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Reportes y Estadísticas</h1>
            <p class="text-muted-foreground mt-1">Analiza el rendimiento financiero y clínico.</p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={exportPdf}>
            <Download class="mr-2 h-4 w-4" />
            Exportar Reporte (PDF)
        </Button>
    </div>

    <!-- Filters -->
    <div class="bg-card p-4 rounded-lg shadow-sm border space-y-4">
        {#if errors.end_date}
            <div class="bg-red-50 text-red-600 p-3 rounded-md text-sm border border-red-200">
                {errors.end_date}
            </div>
        {/if}
        <form onsubmit={filterReports} class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <Label class="text-xs">Fecha Inicio</Label>
                <div class="relative">
                    <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input type="date" bind:value={startDate} class="pl-9" />
                </div>
            </div>
            <div class="space-y-1">
                <Label class="text-xs">Fecha Fin</Label>
                <div class="relative">
                    <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input type="date" bind:value={endDate} class="pl-9" />
                </div>
            </div>
            <div class="space-y-1">
                <Label class="text-xs">Buscar Paciente</Label>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input type="text" placeholder="Nombre o DNI..." bind:value={patientSearch} class="pl-9" />
                </div>
            </div>
            <div>
                <Button type="submit" variant="outline" class="w-full">
                    <Filter class="mr-2 h-4 w-4" /> Aplicar Filtros
                </Button>
            </div>
        </form>
    </div>

    <!-- KPIs Principales -->
    <div class="grid gap-4 md:grid-cols-4">
        <Card class="shadow-sm border-l-4 border-l-green-500">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Créditos (Pagado)</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-green-600">S/ {Number(kpis.totalRevenue).toFixed(2)}</div>
            </CardContent>
        </Card>
        
        <Card class="shadow-sm border-l-4 border-l-red-500">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Deudas (Pendiente)</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-red-600">S/ {Number(kpis.totalDebts).toFixed(2)}</div>
            </CardContent>
        </Card>
        
        <Card class="shadow-sm border-l-4 border-l-blue-500">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Pacientes Filtrados</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-slate-800">{kpis.totalPatients}</div>
            </CardContent>
        </Card>

        <Card class="shadow-sm border-l-4 border-l-purple-500">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Citas en Periodo</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-purple-600">{kpis.totalAppointments}</div>
            </CardContent>
        </Card>
    </div>

    <!-- Grids Analíticos -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mt-4">
        <!-- Ingresos por Mes -->
        <Card class="shadow-sm lg:col-span-2">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-trend-up text-blue-600"></i> Ingresos por Mes (Histórico)
                </CardTitle>
            </CardHeader>
            <CardContent class="p-6">
                <div class="h-[300px]">
                    <canvas bind:this={revenueChartEl}></canvas>
                </div>
            </CardContent>
        </Card>

        <!-- Productividad de Doctores -->
        <Card class="shadow-sm lg:col-span-1">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-user-doctor text-purple-600"></i> Productividad Médica
                </CardTitle>
            </CardHeader>
            <CardContent class="p-6">
                <div class="h-[300px]">
                    {#if !staffProductivity || staffProductivity.length === 0}
                        <div class="flex h-full items-center justify-center text-slate-500">Sin citas atendidas.</div>
                    {:else}
                        <canvas bind:this={productivityChartEl}></canvas>
                    {/if}
                </div>
            </CardContent>
        </Card>

        <!-- Nuevos Pacientes (Línea) -->
        <Card class="shadow-sm lg:col-span-2">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-users text-emerald-600"></i> Captación de Nuevos Pacientes
                </CardTitle>
            </CardHeader>
            <CardContent class="p-6">
                <div class="h-[300px]">
                    <canvas bind:this={patientsChartEl}></canvas>
                </div>
            </CardContent>
        </Card>

        <!-- Estado de Contratos -->
        <Card class="shadow-sm lg:col-span-1">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-file-contract text-orange-500"></i> Estado de Contratos
                </CardTitle>
            </CardHeader>
            <CardContent class="p-6">
                <div class="h-[300px]">
                    <canvas bind:this={contractsChartEl}></canvas>
                </div>
            </CardContent>
        </Card>

        <!-- Tratamientos Frecuentes -->
        <Card class="shadow-sm lg:col-span-1">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-tooth text-cyan-500"></i> Distribución de Tratamientos
                </CardTitle>
            </CardHeader>
            <CardContent class="p-6">
                <div class="h-[300px]">
                    {#if !treatmentsByCategory || treatmentsByCategory.length === 0}
                        <div class="flex h-full items-center justify-center text-slate-500 text-center text-sm">No hay tratamientos en este periodo.</div>
                    {:else}
                        <canvas bind:this={treatmentsChartEl}></canvas>
                    {/if}
                </div>
            </CardContent>
        </Card>

        <!-- Top Pacientes VIP -->
        <Card class="shadow-sm lg:col-span-2">
            <CardHeader class="border-b bg-white dark:bg-slate-900 rounded-t-xl">
                <CardTitle class="text-lg flex items-center gap-2">
                    <i class="fa-solid fa-crown text-yellow-500"></i> Top Pacientes (Mayor Facturación)
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-muted-foreground uppercase text-xs">
                            <tr>
                                <th class="px-6 py-4 font-medium">Paciente</th>
                                <th class="px-6 py-4 font-medium text-right">Monto Facturado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            {#if !topPatients || topPatients.length === 0}
                                <tr>
                                    <td colspan="2" class="px-6 py-8 text-center text-slate-500">No hay pagos registrados en este periodo.</td>
                                </tr>
                            {:else}
                                {#each topPatients as p, i}
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-medium flex items-center gap-3">
                                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">
                                                {i + 1}
                                            </div>
                                            {p.name}
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-green-600">S/ {Number(p.total).toFixed(2)}</td>
                                    </tr>
                                {/each}
                            {/if}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</div>
