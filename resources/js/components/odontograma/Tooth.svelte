<script lang="ts">
    import type { SurfaceId, ToothData } from './types';
    import { COLORS } from './types';

    interface Props {
        id: number;
        tooth: ToothData;
        onSurfaceClick: (toothId: number, surface: SurfaceId) => void;
        onToothClick: (toothId: number) => void;
    }

    let { id, tooth, onSurfaceClick, onToothClick }: Props = $props();

    // Determinar si es superior o inferior
    const isUpper = id < 30;
    
    // Nombres correctos para la superficie interna/externa
    const topSurface: SurfaceId = isUpper ? 'vestibular' : 'lingual';
    const bottomSurface: SurfaceId = isUpper ? 'palatino' : 'vestibular';
    // Molares (oclusal) vs Incisivos (incisal)
    const centerSurface: SurfaceId = tooth.surfaces.oclusal !== undefined ? 'oclusal' : 'incisal';

    let isAbsent = $derived(tooth.status === 'extraccion' || tooth.status === 'ausente');
    let hasEndo = $derived(tooth.status === 'endodoncia');
    let hasCrown = $derived(tooth.status === 'corona');
    let hasImplant = $derived(tooth.status === 'implante');

    function getColor(surface: SurfaceId | undefined) {
        if (!surface || !tooth.surfaces[surface]) return COLORS.sano;
        return COLORS[tooth.surfaces[surface] as keyof typeof COLORS] || COLORS.sano;
    }

    // Prevención de burbujeo para clicks precisos
    function handleClickSurface(e: MouseEvent, surface: SurfaceId) {
        e.stopPropagation();
        onSurfaceClick(id, surface);
    }
</script>

<div class="flex flex-col items-center gap-1 group relative print:break-inside-avoid">
    
    <!-- Tooltip FDI -->
    <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-6 bg-slate-800 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-10 pointer-events-none print:hidden">
        Pieza {id}
    </div>

    <!-- Etiqueta del ID -->
    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 select-none print:text-[10px] print:text-black">
        {id}
    </span>

    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="relative w-12 h-16 cursor-pointer" onclick={() => onToothClick(id)}>
        
        <!-- SVG Completo del Diente Anatómico Simplificado -->
        <svg viewBox="0 0 50 80" class="w-full h-full drop-shadow-sm transition-all duration-300 {isAbsent ? 'opacity-20' : ''}">
            
            <!-- ====== ZONA DE LA RAÍZ ====== -->
            <g class="root-group" stroke="#cbd5e1" stroke-width="1.5" fill="none">
                {#if isUpper}
                    <!-- Raíz Superior -->
                    <path d="M 15 25 C 10 5, 25 0, 25 25" class="transition-colors" />
                    <path d="M 35 25 C 40 5, 25 0, 25 25" class="transition-colors" />
                {:else}
                    <!-- Raíz Inferior -->
                    <path d="M 15 55 C 10 75, 25 80, 25 55" class="transition-colors" />
                    <path d="M 35 55 C 40 75, 25 80, 25 55" class="transition-colors" />
                {/if}
                
                <!-- Implante sobre la raíz -->
                {#if hasImplant}
                    {#if isUpper}
                        <path d="M 18 10 L 32 10 M 20 15 L 30 15 M 22 20 L 28 20" stroke="#64748b" stroke-width="3" pointer-events="none" />
                    {:else}
                        <path d="M 18 70 L 32 70 M 20 65 L 30 65 M 22 60 L 28 60" stroke="#64748b" stroke-width="3" pointer-events="none" />
                    {/if}
                {/if}

                <!-- Endodoncia (Canal Radicular pintado) -->
                {#if hasEndo}
                    {#if isUpper}
                        <path d="M 25 5 L 25 25" stroke="#a855f7" stroke-width="3" pointer-events="none" />
                    {:else}
                        <path d="M 25 55 L 25 75" stroke="#a855f7" stroke-width="3" pointer-events="none" />
                    {/if}
                {/if}
            </g>

            <!-- ====== ZONA DE LA CORONA (Superficies) ====== -->
            <!-- Trasladar la corona al centro -->
            <g transform="translate(5, 20)" stroke="#94a3b8" stroke-width="1" class="transition-colors">
                
                <!-- Superficie Top -->
                <path 
                    d="M 0 0 Q 20 -5 40 0 L 30 10 L 10 10 Z" 
                    fill={getColor(topSurface)} 
                    class="hover:brightness-90 cursor-pointer transition-colors"
                    onclick={(e) => handleClickSurface(e, topSurface)} 
                />
                
                <!-- Superficie Bottom -->
                <path 
                    d="M 10 30 L 30 30 L 40 40 Q 20 45 0 40 Z" 
                    fill={getColor(bottomSurface)} 
                    class="hover:brightness-90 cursor-pointer transition-colors"
                    onclick={(e) => handleClickSurface(e, bottomSurface)} 
                />
                
                <!-- Superficie Left (Mesial/Distal) -->
                <path 
                    d="M 0 0 L 10 10 L 10 30 L 0 40 Q -5 20 0 0 Z" 
                    fill={getColor('mesial')} 
                    class="hover:brightness-90 cursor-pointer transition-colors"
                    onclick={(e) => handleClickSurface(e, 'mesial')} 
                />
                
                <!-- Superficie Right (Distal/Mesial) -->
                <path 
                    d="M 40 0 L 30 10 L 30 30 L 40 40 Q 45 20 40 0 Z" 
                    fill={getColor('distal')} 
                    class="hover:brightness-90 cursor-pointer transition-colors"
                    onclick={(e) => handleClickSurface(e, 'distal')} 
                />
                
                <!-- Superficie Center (Oclusal/Incisal) -->
                <rect 
                    x="10" y="10" width="20" height="20" rx="2" 
                    fill={getColor(centerSurface)} 
                    class="hover:brightness-90 cursor-pointer transition-colors"
                    onclick={(e) => handleClickSurface(e, centerSurface)} 
                />
            </g>

            <!-- ====== MODIFICADORES GLOBALES ====== -->
            
            <!-- Corona (Marco en la corona) -->
            {#if hasCrown}
                <rect x="3" y="18" width="44" height="44" rx="8" fill="none" stroke="#eab308" stroke-width="3" pointer-events="none" />
            {/if}

            <!-- Extracción (Cruz Roja en todo el diente) -->
            {#if isAbsent}
                <line x1="5" y1="20" x2="45" y2="60" stroke="#ef4444" stroke-width="4" pointer-events="none"/>
                <line x1="45" y1="20" x2="5" y2="60" stroke="#ef4444" stroke-width="4" pointer-events="none"/>
            {/if}

        </svg>
    </div>
</div>
