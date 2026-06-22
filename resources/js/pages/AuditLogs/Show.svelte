<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Auditoría', href: '/audits' },
            { title: 'Detalles del Evento', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import {
        ArrowLeft,
        Clock,
        Globe,
        Monitor,
        UserRound,
        ShieldCheck,
        Terminal,
        Network,
        AlertCircle,
        FileJson
    } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Toast } from '@/lib/utils/toast';

    let { auditLog, relatedEvents } = $props();

    function markAsReviewed() {
        router.post(`/audits/${auditLog.id}/review`, {}, {
            preserveScroll: true,
            onSuccess: () => Toast.success('Evento marcado como revisado.')
        });
    }

    function formatJson(data: any) {
        if (!data) return '';
        return JSON.stringify(data, null, 2);
    }

    function getSeverityColor(severity: string) {
        switch (severity) {
            case 'info': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'warning': return 'bg-amber-100 text-amber-800 border-amber-200';
            case 'critical': return 'bg-red-100 text-red-800 border-red-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }
</script>

<AppHead title={`Detalle de Auditoría #${auditLog.id}`} />

<div class="flex flex-col gap-6 p-6 max-w-[1600px] mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" onclick={() => router.get('/audits')}>
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                    Evento #{auditLog.id}
                    <span class={`text-sm px-3 py-1 rounded-full border ${getSeverityColor(auditLog.severity)}`}>
                        {auditLog.severity.toUpperCase()}
                    </span>
                    {#if auditLog.status !== 'success'}
                        <span class="text-sm px-3 py-1 rounded-full bg-red-100 text-red-800 border border-red-200">
                            FALLÓ ({auditLog.status_code})
                        </span>
                    {/if}
                </h1>
                <p class="text-muted-foreground mt-1 flex items-center gap-2">
                    <Clock class="h-4 w-4" /> {new Date(auditLog.created_at).toLocaleString()}
                    {#if auditLog.duration}
                        <span class="opacity-50">|</span> 
                        <span>{auditLog.duration}ms</span>
                    {/if}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            {#if auditLog.reviewed_at}
                <div class="flex items-center gap-2 text-green-700 bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                    <ShieldCheck class="h-5 w-5" />
                    <span class="text-sm font-medium">Revisado el {new Date(auditLog.reviewed_at).toLocaleDateString()}</span>
                </div>
            {:else}
                <Button variant="default" class="bg-blue-600 hover:bg-blue-700" onclick={markAsReviewed}>
                    <ShieldCheck class="mr-2 h-4 w-4" />
                    Marcar como Revisado
                </Button>
            {/if}
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- User Info -->
        <div class="bg-card rounded-xl shadow-sm border p-5">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
                <UserRound class="h-5 w-5 text-blue-600" />
                Información del Usuario
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">ID:</span>
                    <span class="font-medium">{auditLog.user_id || 'N/A'}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Nombre:</span>
                    <span class="font-medium">{auditLog.user_name || 'Sistema / Invitado'}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Email:</span>
                    <span class="font-medium">{auditLog.user_email || 'N/A'}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Rol:</span>
                    <span class="font-medium">{auditLog.user_role || 'N/A'}</span>
                </div>
            </div>
        </div>

        <!-- Request Info -->
        <div class="bg-card rounded-xl shadow-sm border p-5">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
                <Globe class="h-5 w-5 text-indigo-600" />
                Contexto de Petición
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Módulo:</span>
                    <span class="font-medium bg-slate-100 px-2 rounded">{auditLog.module}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Acción:</span>
                    <span class="font-medium">{auditLog.action}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Método:</span>
                    <span class="font-medium font-mono">{auditLog.http_method || 'N/A'}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Endpoint:</span>
                    <span class="font-medium truncate max-w-[200px]" title={auditLog.endpoint}>
                        {auditLog.endpoint || 'N/A'}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tech Info -->
        <div class="bg-card rounded-xl shadow-sm border p-5">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
                <Monitor class="h-5 w-5 text-emerald-600" />
                Información Técnica
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">IP:</span>
                    <span class="font-medium font-mono">{auditLog.ip_address || 'N/A'}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">Navegador:</span>
                    <span class="font-medium">{auditLog.browser || 'N/A'}</span>
                </div>
                <div class="flex justify-between border-b pb-1">
                    <span class="text-muted-foreground">OS:</span>
                    <span class="font-medium">{auditLog.os || 'N/A'}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-muted-foreground mb-1">User Agent:</span>
                    <span class="font-mono text-xs text-muted-foreground bg-muted p-2 rounded truncate" title={auditLog.user_agent}>
                        {auditLog.user_agent || 'N/A'}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="bg-card rounded-xl shadow-sm border p-5">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
            <FileJson class="h-5 w-5 text-gray-600" />
            Descripción del Evento
        </h3>
        <p class="text-lg">{auditLog.description}</p>
    </div>

    <!-- Diff Viewer -->
    {#if auditLog.old_values || auditLog.new_values}
        <div class="bg-card rounded-xl shadow-sm border p-5">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
                <Terminal class="h-5 w-5 text-gray-800" />
                Cambios en los datos
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm font-semibold text-red-700 bg-red-50 p-2 rounded-t-lg border border-red-100 border-b-0">
                        Valores Anteriores (old_values)
                    </h4>
                    <pre class="bg-slate-900 text-slate-50 p-4 rounded-b-lg text-sm overflow-x-auto h-[300px]">
{formatJson(auditLog.old_values) || 'Ningún valor registrado.'}
                    </pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-green-700 bg-green-50 p-2 rounded-t-lg border border-green-100 border-b-0">
                        Nuevos Valores (new_values)
                    </h4>
                    <pre class="bg-slate-900 text-slate-50 p-4 rounded-b-lg text-sm overflow-x-auto h-[300px]">
{formatJson(auditLog.new_values) || 'Ningún valor registrado.'}
                    </pre>
                </div>
            </div>
        </div>
    {/if}

    <!-- Errors Info -->
    {#if auditLog.error_details}
        <div class="bg-red-50 rounded-xl shadow-sm border border-red-200 p-5">
            <h3 class="text-lg font-semibold text-red-800 mb-4 flex items-center gap-2 border-b border-red-200 pb-2">
                <AlertCircle class="h-5 w-5" />
                Detalles del Error Capturado
            </h3>
            <div class="space-y-4">
                <div>
                    <strong class="text-red-900">Mensaje:</strong>
                    <p class="text-red-700 font-mono mt-1 bg-white p-2 rounded border border-red-100">
                        {auditLog.error_details.message || 'Sin mensaje de error'}
                    </p>
                </div>
                {#if auditLog.error_details.file}
                    <div class="text-sm text-red-800">
                        <strong>Archivo:</strong> {auditLog.error_details.file} (Línea {auditLog.error_details.line})
                    </div>
                {/if}
                {#if auditLog.error_details.trace}
                    <div>
                        <strong class="text-red-900 text-sm">Stack Trace (Solo Administrador):</strong>
                        <pre class="bg-slate-900 text-slate-300 p-4 rounded-lg text-xs overflow-x-auto h-[250px] mt-2">
{auditLog.error_details.trace}
                        </pre>
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    <!-- Timeline -->
    {#if relatedEvents && relatedEvents.length > 0}
        <div class="bg-card rounded-xl shadow-sm border p-5">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2 border-b pb-2">
                <Network class="h-5 w-5 text-purple-600" />
                Línea de tiempo (Correlation ID: {auditLog.correlation_id})
            </h3>
            <div class="space-y-4 pl-4 border-l-2 border-purple-100">
                <div class="relative">
                    <div class="absolute -left-[25px] top-1 h-4 w-4 rounded-full bg-blue-600 ring-4 ring-white"></div>
                    <div class="text-sm">
                        <span class="font-semibold text-blue-600">Este evento</span>
                        <span class="text-muted-foreground ml-2">{new Date(auditLog.created_at).toLocaleTimeString()}</span>
                        <p class="text-muted-foreground mt-1">{auditLog.action} - {auditLog.description}</p>
                    </div>
                </div>

                {#each relatedEvents as event}
                    <div class="relative">
                        <div class="absolute -left-[25px] top-1 h-3 w-3 rounded-full bg-purple-300 ring-4 ring-white"></div>
                        <div class="text-sm">
                            <a href={`/audits/${event.id}`} class="font-semibold text-purple-600 hover:underline">
                                Evento #{event.id}
                            </a>
                            <span class="text-muted-foreground ml-2">{new Date(event.created_at).toLocaleTimeString()}</span>
                            <p class="text-muted-foreground mt-1 text-xs">{event.action} - {event.description}</p>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {/if}
</div>
