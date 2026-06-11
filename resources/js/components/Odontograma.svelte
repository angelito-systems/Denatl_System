<script lang="ts">
    import { createEventDispatcher } from 'svelte';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { Label } from '@/components/ui/label';
    import { Badge } from '@/components/ui/badge';

    const dispatch = createEventDispatcher();

    // Tooth state types
    type SurfaceStatus = 'sano' | 'caries' | 'obturacion' | 'sellante';
    type ToothStatus = 'sano' | 'ausente' | 'extraccion' | 'endodoncia' | 'corona';

    // Status colors
    const colors = {
        sano: '#ffffff', // White
        caries: '#ef4444', // Red
        obturacion: '#3b82f6', // Blue
        sellante: '#f59e0b', // Amber
    };

    // Global modifiers
    const modifiers = {
        ausente: 'stroke-red-500 stroke-2 cross-path',
        extraccion: 'stroke-red-500 stroke-2 line-path',
        endodoncia: 'fill-blue-500 stroke-blue-700',
        corona: 'stroke-yellow-500 stroke-[3px]',
    };

    // Generar dientes permanentes (18-11, 21-28, 48-41, 31-38)
    const upperRight = [18, 17, 16, 15, 14, 13, 12, 11];
    const upperLeft = [21, 22, 23, 24, 25, 26, 27, 28];
    const lowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
    const lowerLeft = [31, 32, 33, 34, 35, 36, 37, 38];

    // Initialize state
    let state = $state<{
        [key: number]: {
            status: ToothStatus;
            surfaces: { top: SurfaceStatus, bottom: SurfaceStatus, left: SurfaceStatus, right: SurfaceStatus, center: SurfaceStatus }
        }
    }>({});

    const allTeeth = [...upperRight, ...upperLeft, ...lowerRight, ...lowerLeft];
    allTeeth.forEach(t => {
        state[t] = {
            status: 'sano',
            surfaces: { top: 'sano', bottom: 'sano', left: 'sano', right: 'sano', center: 'sano' }
        };
    });

    let currentAction = $state<SurfaceStatus | ToothStatus>('caries');

    function handleSurfaceClick(toothId: number, surface: 'top' | 'bottom' | 'left' | 'right' | 'center') {
        if (['caries', 'sano', 'obturacion', 'sellante'].includes(currentAction)) {
            state[toothId].surfaces[surface] = currentAction as SurfaceStatus;
            dispatch('change', state);
        }
    }

    function handleToothClick(toothId: number) {
        if (['ausente', 'extraccion', 'endodoncia', 'corona', 'sano'].includes(currentAction)) {
            state[toothId].status = currentAction as ToothStatus;
            if (currentAction === 'sano') {
                state[toothId].surfaces = { top: 'sano', bottom: 'sano', left: 'sano', right: 'sano', center: 'sano' };
            }
            dispatch('change', state);
        }
    }
</script>

<div class="flex flex-col gap-6 w-full">
    <div class="flex items-center gap-4 bg-muted/30 p-4 rounded-xl border">
        <div class="space-y-1">
            <Label>Herramienta Actual:</Label>
            <Select bind:value={currentAction} type="single">
                <SelectTrigger class="w-[200px]">
                    {currentAction || 'Selecciona...'}
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="sano">🟢 Sano (Restaurar)</SelectItem>
                    <SelectItem value="caries">🔴 Caries (Superficie)</SelectItem>
                    <SelectItem value="obturacion">🔵 Obturación (Superficie)</SelectItem>
                    <SelectItem value="sellante">🟠 Sellante (Superficie)</SelectItem>
                    <SelectItem value="ausente">❌ Ausente (Diente)</SelectItem>
                    <SelectItem value="extraccion">➖ Extracción (Diente)</SelectItem>
                    <SelectItem value="endodoncia">🟣 Endodoncia (Diente)</SelectItem>
                    <SelectItem value="corona">🟡 Corona (Diente)</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="flex flex-col">
            <span class="text-sm font-medium">Leyenda</span>
            <div class="flex gap-2 flex-wrap mt-1">
                <Badge variant="outline" class="border-red-500 text-red-500">Caries</Badge>
                <Badge variant="outline" class="border-blue-500 text-blue-500">Obturación</Badge>
                <Badge variant="outline" class="border-amber-500 text-amber-500">Sellante</Badge>
            </div>
        </div>
    </div>

    <!-- Odontograma Grid -->
    <div class="flex flex-col items-center justify-center gap-8 bg-white dark:bg-slate-900 p-8 rounded-xl border overflow-x-auto min-w-max">
        
        <!-- Maxilar Superior -->
        <div class="flex gap-16">
            <!-- Cuadrante 1 (Derecho) -->
            <div class="flex gap-2">
                {#each upperRight as tooth}
                    <div class="flex flex-col items-center gap-2 cursor-pointer">
                        <span class="text-sm font-bold text-slate-500">{tooth}</span>
                        <!-- Tooth SVG -->
                        <svg width="40" height="40" viewBox="0 0 40 40" class="group">
                            <g class={state[tooth].status === 'ausente' ? 'opacity-30' : ''}>
                                <!-- Top -->
                                <polygon points="0,0 40,0 30,10 10,10" fill={colors[state[tooth].surfaces.top]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'top')} />
                                <!-- Bottom -->
                                <polygon points="10,30 30,30 40,40 0,40" fill={colors[state[tooth].surfaces.bottom]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'bottom')} />
                                <!-- Left -->
                                <polygon points="0,0 10,10 10,30 0,40" fill={colors[state[tooth].surfaces.left]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'left')} />
                                <!-- Right -->
                                <polygon points="40,0 30,10 30,30 40,40" fill={colors[state[tooth].surfaces.right]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'right')} />
                                <!-- Center -->
                                <rect x="10" y="10" width="20" height="20" fill={colors[state[tooth].surfaces.center]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'center')} />
                            </g>
                            
                            <!-- Tooth Modifiers (Ausente, Corona, Endodoncia) overlay -->
                            <rect width="40" height="40" fill="transparent" stroke={state[tooth].status === 'corona' ? '#eab308' : 'transparent'} stroke-width="4" on:click={() => handleToothClick(tooth)} />
                            {#if state[tooth].status === 'ausente'}
                                <line x1="0" y1="0" x2="40" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                                <line x1="40" y1="0" x2="0" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'extraccion'}
                                <line x1="0" y1="20" x2="40" y2="20" stroke="red" stroke-width="4" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'endodoncia'}
                                <line x1="20" y1="0" x2="20" y2="40" stroke="purple" stroke-width="4" pointer-events="none"/>
                            {/if}
                        </svg>
                    </div>
                {/each}
            </div>
            
            <!-- Cuadrante 2 (Izquierdo) -->
            <div class="flex gap-2">
                {#each upperLeft as tooth}
                    <div class="flex flex-col items-center gap-2 cursor-pointer">
                        <span class="text-sm font-bold text-slate-500">{tooth}</span>
                        <svg width="40" height="40" viewBox="0 0 40 40">
                            <!-- Similar inner structure for left quadrant -->
                            <g class={state[tooth].status === 'ausente' ? 'opacity-30' : ''}>
                                <polygon points="0,0 40,0 30,10 10,10" fill={colors[state[tooth].surfaces.top]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'top')} />
                                <polygon points="10,30 30,30 40,40 0,40" fill={colors[state[tooth].surfaces.bottom]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'bottom')} />
                                <polygon points="0,0 10,10 10,30 0,40" fill={colors[state[tooth].surfaces.left]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'left')} />
                                <polygon points="40,0 30,10 30,30 40,40" fill={colors[state[tooth].surfaces.right]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'right')} />
                                <rect x="10" y="10" width="20" height="20" fill={colors[state[tooth].surfaces.center]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'center')} />
                            </g>
                            <rect width="40" height="40" fill="transparent" stroke={state[tooth].status === 'corona' ? '#eab308' : 'transparent'} stroke-width="4" on:click={() => handleToothClick(tooth)} />
                            {#if state[tooth].status === 'ausente'}
                                <line x1="0" y1="0" x2="40" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                                <line x1="40" y1="0" x2="0" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'extraccion'}
                                <line x1="0" y1="20" x2="40" y2="20" stroke="red" stroke-width="4" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'endodoncia'}
                                <line x1="20" y1="0" x2="20" y2="40" stroke="purple" stroke-width="4" pointer-events="none"/>
                            {/if}
                        </svg>
                    </div>
                {/each}
            </div>
        </div>

        <!-- Separator -->
        <div class="h-1 w-full bg-slate-200 dark:bg-slate-800 rounded-full my-4"></div>

        <!-- Maxilar Inferior -->
        <div class="flex gap-16">
            <!-- Cuadrante 4 (Derecho) -->
            <div class="flex gap-2">
                {#each lowerRight as tooth}
                    <div class="flex flex-col items-center gap-2 cursor-pointer">
                        <svg width="40" height="40" viewBox="0 0 40 40">
                            <g class={state[tooth].status === 'ausente' ? 'opacity-30' : ''}>
                                <polygon points="0,0 40,0 30,10 10,10" fill={colors[state[tooth].surfaces.top]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'top')} />
                                <polygon points="10,30 30,30 40,40 0,40" fill={colors[state[tooth].surfaces.bottom]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'bottom')} />
                                <polygon points="0,0 10,10 10,30 0,40" fill={colors[state[tooth].surfaces.left]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'left')} />
                                <polygon points="40,0 30,10 30,30 40,40" fill={colors[state[tooth].surfaces.right]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'right')} />
                                <rect x="10" y="10" width="20" height="20" fill={colors[state[tooth].surfaces.center]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'center')} />
                            </g>
                            <rect width="40" height="40" fill="transparent" stroke={state[tooth].status === 'corona' ? '#eab308' : 'transparent'} stroke-width="4" on:click={() => handleToothClick(tooth)} />
                            {#if state[tooth].status === 'ausente'}
                                <line x1="0" y1="0" x2="40" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                                <line x1="40" y1="0" x2="0" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'extraccion'}
                                <line x1="0" y1="20" x2="40" y2="20" stroke="red" stroke-width="4" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'endodoncia'}
                                <line x1="20" y1="0" x2="20" y2="40" stroke="purple" stroke-width="4" pointer-events="none"/>
                            {/if}
                        </svg>
                        <span class="text-sm font-bold text-slate-500">{tooth}</span>
                    </div>
                {/each}
            </div>

            <!-- Cuadrante 3 (Izquierdo) -->
            <div class="flex gap-2">
                {#each lowerLeft as tooth}
                    <div class="flex flex-col items-center gap-2 cursor-pointer">
                        <svg width="40" height="40" viewBox="0 0 40 40">
                            <g class={state[tooth].status === 'ausente' ? 'opacity-30' : ''}>
                                <polygon points="0,0 40,0 30,10 10,10" fill={colors[state[tooth].surfaces.top]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'top')} />
                                <polygon points="10,30 30,30 40,40 0,40" fill={colors[state[tooth].surfaces.bottom]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'bottom')} />
                                <polygon points="0,0 10,10 10,30 0,40" fill={colors[state[tooth].surfaces.left]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'left')} />
                                <polygon points="40,0 30,10 30,30 40,40" fill={colors[state[tooth].surfaces.right]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'right')} />
                                <rect x="10" y="10" width="20" height="20" fill={colors[state[tooth].surfaces.center]} stroke="#94a3b8" stroke-width="1" class="hover:brightness-90 transition-all" on:click={() => handleSurfaceClick(tooth, 'center')} />
                            </g>
                            <rect width="40" height="40" fill="transparent" stroke={state[tooth].status === 'corona' ? '#eab308' : 'transparent'} stroke-width="4" on:click={() => handleToothClick(tooth)} />
                            {#if state[tooth].status === 'ausente'}
                                <line x1="0" y1="0" x2="40" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                                <line x1="40" y1="0" x2="0" y2="40" stroke="red" stroke-width="3" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'extraccion'}
                                <line x1="0" y1="20" x2="40" y2="20" stroke="red" stroke-width="4" pointer-events="none"/>
                            {/if}
                            {#if state[tooth].status === 'endodoncia'}
                                <line x1="20" y1="0" x2="20" y2="40" stroke="purple" stroke-width="4" pointer-events="none"/>
                            {/if}
                        </svg>
                        <span class="text-sm font-bold text-slate-500">{tooth}</span>
                    </div>
                {/each}
            </div>
        </div>
    </div>
</div>
