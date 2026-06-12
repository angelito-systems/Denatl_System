<script lang="ts">
    import { createEventDispatcher, onMount } from 'svelte';
    import Toolbar from './Toolbar.svelte';
    import Tooth from './Tooth.svelte';
    import PdfViewerModal from '@/components/PdfViewerModal.svelte';
    import { createOdontograma } from './useOdontograma.svelte';
    import { UPPER_TEETH, LOWER_TEETH, type OdontogramState } from './types';
    import { Loader2 } from 'lucide-svelte';

    interface Props {
        patientId: number;
        initialData?: OdontogramState;
        readonly?: boolean;
    }

    let { patientId, initialData = {}, readonly = false }: Props = $props();
    const dispatch = createEventDispatcher<{ change: OdontogramState }>();

    let showPdfModal = $state(false);
    let pdfUrl = $state('');
    let isPrinting = $state(false);

    // Inicializar el motor reactivo
    const odonto = createOdontograma(initialData);

    // Agrupar dientes para el renderizado (FDI)
    const upperRight = UPPER_TEETH.filter(t => t < 20).sort((a,b) => b - a); // 18 a 11
    const upperLeft = UPPER_TEETH.filter(t => t >= 20).sort((a,b) => a - b); // 21 a 28
    
    const lowerRight = LOWER_TEETH.filter(t => t > 40).sort((a,b) => b - a); // 48 a 41
    const lowerLeft = LOWER_TEETH.filter(t => t < 40).sort((a,b) => a - b); // 31 a 38

    async function printOdontograma() {
        // Enviar el HTML del grid del odontograma al backend para generar el PDF
        isPrinting = true;
        
        try {
            const gridElement = document.getElementById('odontograma-grid-container');
            const htmlContent = gridElement ? gridElement.outerHTML : '';

            const response = await fetch(`/pacientes/${patientId}/pdf/odontograma`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''
                },
                body: JSON.stringify({ html: htmlContent })
            });

            if (response.ok) {
                const result = await response.json();
                pdfUrl = `data:application/pdf;base64,${result.base64}`;
                showPdfModal = true;
            } else {
                alert('Error al generar el PDF del odontograma.');
            }
        } catch (error) {
            console.error('Error generando PDF:', error);
            alert('Error de conexión al generar PDF.');
        } finally {
            isPrinting = false;
        }
    }

    // Un watcher interno que despache cada vez que 'odonto.state' muta
    // Svelte 5 usa $effect para observar el proxy reactivo profundamente
    $effect(() => {
        // Hacemos deep clone rápido para gatillar lectura de todo y aislar el valor emitido
        const snapshot = JSON.parse(JSON.stringify(odonto.state));
        dispatch('change', snapshot);
    });

</script>

<!-- Contenedor Principal (Con @media print) -->
<div class="flex flex-col gap-6 w-full max-w-6xl mx-auto print:m-0 print:p-0 print:w-full print:max-w-none">
    
    {#if !readonly}
        <Toolbar 
            currentTool={odonto.currentTool} 
            onSetTool={odonto.setTool} 
            onPrint={printOdontograma} 
        />
    {/if}

    <!-- Título solo para impresión -->
    <h2 class="hidden print:block text-xl font-bold text-center mb-6 border-b pb-2">Odontograma Clínico</h2>

    <!-- GRID ANATÓMICO -->
    <div id="odontograma-grid-container" class="flex flex-col items-center justify-center gap-12 bg-slate-50 print:bg-white p-8 md:p-12 rounded-2xl border border-slate-200 shadow-inner overflow-x-auto print:overflow-visible print:border-none print:shadow-none">
        
        <!-- Maxilar Superior -->
        <div class="flex flex-col items-center gap-6">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest print:text-black">Arcada Superior (Maxilar)</span>
            
            <div class="flex gap-8 md:gap-16">
                <!-- Cuadrante Superior Derecho (1) -->
                <div class="flex gap-1 md:gap-2">
                    {#each upperRight as id}
                        <Tooth 
                            {id} 
                            tooth={odonto.state[id]} 
                            onSurfaceClick={(tId, s) => odonto.toggleSurface(tId, s)} 
                            onToothClick={(tId) => odonto.toggleTooth(tId)} 
                        />
                    {/each}
                </div>
                
                <!-- Cuadrante Superior Izquierdo (2) -->
                <div class="flex gap-1 md:gap-2">
                    {#each upperLeft as id}
                        <Tooth 
                            {id} 
                            tooth={odonto.state[id]} 
                            onSurfaceClick={(tId, s) => odonto.toggleSurface(tId, s)} 
                            onToothClick={(tId) => odonto.toggleTooth(tId)} 
                        />
                    {/each}
                </div>
            </div>
        </div>

        <!-- Línea media horizontal -->
        <div class="h-0.5 w-full max-w-5xl bg-gradient-to-r from-transparent via-slate-300 to-transparent my-2 print:via-slate-800"></div>

        <!-- Arcada Inferior -->
        <div class="flex flex-col items-center gap-6">
            
            <div class="flex gap-8 md:gap-16">
                <!-- Cuadrante Inferior Derecho (4) -->
                <div class="flex gap-1 md:gap-2">
                    {#each lowerRight as id}
                        <Tooth 
                            {id} 
                            tooth={odonto.state[id]} 
                            onSurfaceClick={(tId, s) => odonto.toggleSurface(tId, s)} 
                            onToothClick={(tId) => odonto.toggleTooth(tId)} 
                        />
                    {/each}
                </div>

                <!-- Cuadrante Inferior Izquierdo (3) -->
                <div class="flex gap-1 md:gap-2">
                    {#each lowerLeft as id}
                        <Tooth 
                            {id} 
                            tooth={odonto.state[id]} 
                            onSurfaceClick={(tId, s) => odonto.toggleSurface(tId, s)} 
                            onToothClick={(tId) => odonto.toggleTooth(tId)} 
                        />
                    {/each}
                </div>
            </div>

            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2 print:text-black">Arcada Inferior (Mandíbula)</span>
        </div>

    </div>
</div>

{#if isPrinting}
    <div class="fixed inset-0 bg-black/20 z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-xl flex flex-col items-center gap-4">
            <Loader2 class="w-8 h-8 animate-spin text-blue-600" />
            <span class="font-medium">Generando PDF del Odontograma...</span>
        </div>
    </div>
{/if}

<PdfViewerModal bind:isOpen={showPdfModal} url={pdfUrl} title="Visor Odontograma Clínico" />

<style>
    @media print {
        :global(body) {
            background-color: white !important;
        }
        /* Ocultar elementos globales innecesarios durante impresión de odontograma */
        :global(nav), :global(header), :global(footer), :global(.sidebar) {
            display: none !important;
        }
    }
</style>
