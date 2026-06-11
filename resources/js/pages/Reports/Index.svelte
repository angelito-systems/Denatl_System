<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Reportes', href: '/reportes' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { onMount } from 'svelte';
    import Chart from 'chart.js/auto';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
    import { Download, Calendar, Filter } from 'lucide-svelte';
    import { router } from '@inertiajs/svelte';
    import { index as reportsIndex } from '@/routes/reportes';
    
    let { revenueByMonth, treatmentsByCategory, paymentsByMethod, kpis } = $props();

    let revenueChartEl: HTMLCanvasElement;
    let treatmentsChartEl: HTMLCanvasElement;
    let activeTab = $state('graficos');
    
    let startDate = $state('');
    let endDate = $state('');

    function filterReports(e: Event) {
        e.preventDefault();
        router.get(reportsIndex(), { start_date: startDate, end_date: endDate }, { preserveState: true });
    }
    
    onMount(() => {
        // Render Revenue Chart
        if (revenueChartEl && revenueByMonth && revenueByMonth.length > 0) {
            new Chart(revenueChartEl, {
                type: 'bar',
                data: {
                    labels: revenueByMonth.map(r => r.month).reverse(),
                    datasets: [{
                        label: 'Ingresos (S/)',
                        data: revenueByMonth.map(r => r.revenue).reverse(),
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // Render Treatments Chart
        if (treatmentsChartEl && treatmentsByCategory && treatmentsByCategory.length > 0) {
            new Chart(treatmentsChartEl, {
                type: 'pie',
                data: {
                    labels: treatmentsByCategory.map(t => t.category),
                    datasets: [{
                        data: treatmentsByCategory.map(t => t.count),
                        backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#06b6d4', '#6366f1', '#d946ef']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    });
</script>

<AppHead title="Reportes y Estadísticas" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Reportes y Estadísticas</h1>
            <p class="text-muted-foreground mt-1">Analiza el rendimiento financiero y clínico de la clínica.</p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={() => window.print()}>
            <Download class="mr-2 h-4 w-4" />
            Exportar Reporte (PDF)
        </Button>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-4 bg-card p-4 rounded-lg shadow-sm border">
        <form onsubmit={filterReports} class="flex items-center gap-4 w-full">
            <div class="space-y-1">
                <Label class="text-xs">Fecha Inicio</Label>
                <div class="relative max-w-sm">
                    <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input type="date" bind:value={startDate} class="pl-9" />
                </div>
            </div>
            <div class="space-y-1">
                <Label class="text-xs">Fecha Fin</Label>
                <div class="relative max-w-sm">
                    <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input type="date" bind:value={endDate} class="pl-9" />
                </div>
            </div>
            <div class="pt-5">
                <Button type="submit" variant="outline">
                    <Filter class="mr-2 h-4 w-4" /> Filtrar
                </Button>
            </div>
        </form>
    </div>

    <!-- KPIs Principales -->
    <div class="grid gap-4 md:grid-cols-3">
        <Card class="shadow-sm">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Pacientes Totales</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-slate-800">{kpis.totalPatients}</div>
            </CardContent>
        </Card>
        <Card class="shadow-sm">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Ingresos Históricos</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-green-600">S/ {Number(kpis.totalRevenue).toFixed(2)}</div>
            </CardContent>
        </Card>
        <Card class="shadow-sm">
            <CardHeader class="pb-2">
                <CardTitle class="text-sm text-muted-foreground">Citas Programadas</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold text-blue-600">{kpis.totalAppointments}</div>
            </CardContent>
        </Card>
    </div>

    <!-- Pestañas de Gráficos -->
    <Tabs bind:value={activeTab} class="mt-4">
        <TabsList>
            <TabsTrigger value="graficos">Gráficos Financieros</TabsTrigger>
            <TabsTrigger value="tratamientos">Distribución de Tratamientos</TabsTrigger>
        </TabsList>
        <TabsContent value="graficos" class="mt-4">
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Ingresos por Mes (Últimos 6 meses)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="h-[400px]">
                        <canvas bind:this={revenueChartEl}></canvas>
                    </div>
                </CardContent>
            </Card>
        </TabsContent>
        <TabsContent value="tratamientos" class="mt-4">
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Tratamientos por Categoría</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="h-[400px]">
                        <canvas bind:this={treatmentsChartEl}></canvas>
                    </div>
                </CardContent>
            </Card>
        </TabsContent>
    </Tabs>
</div>
