<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            {
                title: 'Auditoría',
                href: '/audits',
            },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { 
        ShieldAlert, 
        Search, 
        Download, 
        Eye, 
        Filter, 
        Info, 
        AlertTriangle, 
        ShieldBan,
        Activity
    } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import { onMount } from 'svelte';
    import Chart from 'chart.js/auto';

    let { logs, filters, stats, chartData } = $props();

    let searchQuery = $state(filters?.search || '');
    let severityFilter = $state(filters?.severity || '');
    let searchTimeout: ReturnType<typeof setTimeout>;
    
    let chartCanvas: HTMLCanvasElement;

    function applyFilters() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(
                '/audits',
                { search: searchQuery, severity: severityFilter },
                { preserveState: true, replace: true }
            );
        }, 300);
    }

    function viewDetails(id: number) {
        router.get(`/audits/${id}`);
    }

    function exportData(format: 'csv' | 'xlsx') {
        const url = new URL(window.location.origin + '/audits');
        url.searchParams.append('export', 'true');
        url.searchParams.append('format', format);
        if (searchQuery) url.searchParams.append('search', searchQuery);
        if (severityFilter) url.searchParams.append('severity', severityFilter);
        
        window.open(url.toString(), '_blank');
    }

    onMount(() => {
        if (chartCanvas && chartData) {
            const labels = Object.keys(chartData);
            const data = Object.values(chartData);
            
            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Eventos por día',
                        data: data,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    });

    function getSeverityColor(severity: string) {
        switch (severity) {
            case 'info': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'warning': return 'bg-amber-100 text-amber-800 border-amber-200';
            case 'critical': return 'bg-red-100 text-red-800 border-red-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }

    function getSeverityIcon(severity: string) {
        switch (severity) {
            case 'info': return Info;
            case 'warning': return AlertTriangle;
            case 'critical': return ShieldBan;
            default: return Activity;
        }
    }
</script>

<AppHead title="Sistema de Auditoría Avanzado" />

<div class="flex flex-col gap-6 p-6 max-w-[1600px] mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                <ShieldAlert class="h-8 w-8 text-blue-600" />
                Auditoría del Sistema
            </h1>
            <p class="text-muted-foreground mt-1">
                Monitoreo centralizado y trazabilidad completa de actividades. Solo Administrador.
            </p>
        </div>
        <div class="flex gap-2">
            <Button variant="outline" onclick={() => exportData('csv')}>
                <Download class="mr-2 h-4 w-4" /> CSV
            </Button>
            <Button variant="outline" onclick={() => exportData('xlsx')}>
                <Download class="mr-2 h-4 w-4" /> Excel
            </Button>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-card p-4 rounded-xl shadow-sm border flex flex-col justify-between">
            <span class="text-sm font-medium text-muted-foreground">Total Eventos</span>
            <span class="text-3xl font-bold">{stats.total}</span>
        </div>
        <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-100 flex flex-col justify-between">
            <span class="text-sm font-medium text-blue-600 flex items-center gap-1"><Info class="h-4 w-4"/> Info</span>
            <span class="text-3xl font-bold text-blue-900">{stats.info}</span>
        </div>
        <div class="bg-amber-50 p-4 rounded-xl shadow-sm border border-amber-100 flex flex-col justify-between">
            <span class="text-sm font-medium text-amber-600 flex items-center gap-1"><AlertTriangle class="h-4 w-4"/> Warnings</span>
            <span class="text-3xl font-bold text-amber-900">{stats.warning}</span>
        </div>
        <div class="bg-red-50 p-4 rounded-xl shadow-sm border border-red-100 flex flex-col justify-between">
            <span class="text-sm font-medium text-red-600 flex items-center gap-1"><ShieldBan class="h-4 w-4"/> Críticos</span>
            <span class="text-3xl font-bold text-red-900">{stats.critical}</span>
        </div>
        <div class="bg-card p-4 rounded-xl shadow-sm border flex flex-col justify-between">
            <span class="text-sm font-medium text-muted-foreground">Hoy</span>
            <span class="text-3xl font-bold">{stats.today}</span>
        </div>
    </div>

    <!-- Chart & Filters row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-card rounded-xl shadow-sm border p-4 h-[250px]">
            <h3 class="text-sm font-medium text-muted-foreground mb-4">Tendencia (Últimos 7 días)</h3>
            <div class="relative h-[180px] w-full">
                <canvas bind:this={chartCanvas}></canvas>
            </div>
        </div>
        
        <div class="bg-card rounded-xl shadow-sm border p-4 flex flex-col gap-4">
            <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                <Filter class="h-4 w-4" /> Filtros Activos
            </h3>
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    type="text"
                    placeholder="Buscar evento, usuario o módulo..."
                    class="pl-9"
                    bind:value={searchQuery}
                    oninput={applyFilters}
                />
            </div>
            <div>
                <label class="text-xs text-muted-foreground font-medium mb-1 block">Nivel de Severidad</label>
                <select 
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    bind:value={severityFilter}
                    onchange={applyFilters}
                >
                    <option value="">Todos los niveles</option>
                    <option value="info">Info (Operaciones normales)</option>
                    <option value="warning">Warning (Requiere atención)</option>
                    <option value="critical">Critical (Errores graves)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden flex flex-col flex-1">
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow class="bg-muted/50">
                        <TableHead class="w-[180px]">Fecha</TableHead>
                        <TableHead>Nivel</TableHead>
                        <TableHead>Usuario</TableHead>
                        <TableHead>Módulo</TableHead>
                        <TableHead>Acción</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="text-right">Detalle</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if logs.data.length === 0}
                        <TableRow>
                            <TableCell colspan="7" class="text-center h-24 text-muted-foreground">
                                No se encontraron registros de auditoría.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each logs.data as log}
                            <TableRow class="hover:bg-muted/30">
                                <TableCell class="font-medium text-xs text-muted-foreground">
                                    {new Date(log.created_at).toLocaleString()}
                                </TableCell>
                                <TableCell>
                                    <span class={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${getSeverityColor(log.severity)}`}>
                                        {log.severity.toUpperCase()}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-sm">{log.user_name || 'Sistema'}</span>
                                        {#if log.user_role}
                                            <span class="text-xs text-muted-foreground">{log.user_role}</span>
                                        {/if}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-block bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs font-mono">
                                        {log.module}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="truncate max-w-[250px] text-sm" title={log.description}>
                                        {log.description}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span class={`text-xs font-bold ${log.status === 'success' ? 'text-green-600' : 'text-red-600'}`}>
                                        {log.status === 'success' ? 'ÉXITO' : 'FALLO'}
                                        {#if log.status_code}
                                            <span class="opacity-70 ml-1">({log.status_code})</span>
                                        {/if}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onclick={() => viewDetails(log.id)}
                                        class="hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <Eye class="h-4 w-4 mr-1" /> Ver más
                                    </Button>
                                </TableCell>
                            </TableRow>
                        {/each}
                    {/if}
                </TableBody>
            </Table>
        </div>

        <div class="p-4 border-t flex flex-col sm:flex-row items-center justify-between text-sm text-muted-foreground gap-4">
            <div>
                Mostrando {logs.from || 0} al {logs.to || 0} de {logs.total} registros
            </div>
            <div class="flex gap-2">
                {#each logs.links as link}
                    <Button
                        variant={link.active ? 'default' : 'outline'}
                        size="sm"
                        disabled={!link.url}
                        onclick={() => link.url && router.get(link.url)}
                        class={link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : ''}
                    >
                        {@html link.label}
                    </Button>
                {/each}
            </div>
        </div>
    </div>
</div>
