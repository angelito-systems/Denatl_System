import { COLORS, type OdontogramState, type SurfaceId, type SurfaceStatus, type ToolType, type ToothData, type ToothStatus, UPPER_TEETH, LOWER_TEETH } from './types';

export function createOdontograma(initialState: OdontogramState = {}) {
    // Inicializar estado usando Svelte 5 runes
    let state = $state<OdontogramState>({});
    let currentTool = $state<ToolType>('seleccionar');

    function initializeState() {
        if (Object.keys(initialState).length > 0) {
            state = JSON.parse(JSON.stringify(initialState));
            return;
        }

        const freshState: OdontogramState = {};
        const allTeeth = [...UPPER_TEETH, ...LOWER_TEETH];
        
        for (const id of allTeeth) {
            freshState[id] = {
                status: 'sano',
                surfaces: {
                    vestibular: 'sano',
                    mesial: 'sano',
                    distal: 'sano',
                    ...(id < 30 ? { palatino: 'sano' } : { lingual: 'sano' }),
                    // Incisivos/caninos (11-13, 21-23, 31-33, 41-43) tienen incisal, los demas oclusal
                    ...([11,12,13,21,22,23,31,32,33,41,42,43].includes(id) ? { incisal: 'sano' } : { oclusal: 'sano' })
                }
            };
        }
        state = freshState;
    }

    initializeState();

    function setTool(tool: ToolType) {
        currentTool = tool;
    }

    function toggleSurface(toothId: number, surface: SurfaceId) {
        if (!state[toothId]) return;
        
        // Si la herramienta es borrador, sanar la superficie
        if (currentTool === 'borrador') {
            state[toothId].surfaces[surface] = 'sano';
            return;
        }

        // Si es una herramienta de superficie
        if (['caries', 'restauracion'].includes(currentTool)) {
            // Lógica de Toggle
            if (state[toothId].surfaces[surface] === currentTool) {
                state[toothId].surfaces[surface] = 'sano'; // Quitar si ya tiene la misma
            } else {
                state[toothId].surfaces[surface] = currentTool as SurfaceStatus;
            }
            
            // Si el diente estaba ausente/extraído, revertir eso porque se está trabajando la superficie
            if (['extraccion', 'ausente'].includes(state[toothId].status)) {
                state[toothId].status = 'sano';
            }
        }
    }

    function toggleTooth(toothId: number) {
        if (!state[toothId]) return;

        if (currentTool === 'borrador') {
            state[toothId].status = 'sano';
            for (const s in state[toothId].surfaces) {
                state[toothId].surfaces[s as SurfaceId] = 'sano';
            }
            return;
        }

        if (['corona', 'implante', 'extraccion', 'endodoncia'].includes(currentTool)) {
            if (state[toothId].status === currentTool) {
                state[toothId].status = 'sano'; // Toggle off
            } else {
                state[toothId].status = currentTool as ToothStatus;
                
                // Si es extracción, limpiar las superficies porque ya no existen
                if (currentTool === 'extraccion') {
                    for (const s in state[toothId].surfaces) {
                        state[toothId].surfaces[s as SurfaceId] = 'sano';
                    }
                }
            }
        }
    }

    function loadSnapshot(snapshot: OdontogramState) {
        state = JSON.parse(JSON.stringify(snapshot));
    }

    function getState() {
        return state; // Por referencia para reactividad
    }

    function exportJSON() {
        // Devuelve una copia limpia (snapshot)
        return JSON.parse(JSON.stringify(state));
    }

    return {
        get state() { return state; },
        get currentTool() { return currentTool; },
        setTool,
        toggleSurface,
        toggleTooth,
        loadSnapshot,
        getState,
        exportJSON
    };
}
