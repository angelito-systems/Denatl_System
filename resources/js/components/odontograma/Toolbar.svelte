<script lang="ts">
    import type { ToolType } from './types';
    import { Button } from '@/components/ui/button';
    import { Printer, MousePointer2, Eraser } from 'lucide-svelte';

    interface Props {
        currentTool: ToolType;
        onSetTool: (tool: ToolType) => void;
        onPrint: () => void;
    }

    let { currentTool, onSetTool, onPrint }: Props = $props();

    const SURFACE_TOOLS = [
        { id: 'caries', label: 'Caries', color: 'bg-red-500' },
        { id: 'restauracion', label: 'Restauración', color: 'bg-blue-500' },
    ] as const;

    const TOOTH_TOOLS = [
        { id: 'corona', label: 'Corona', color: 'bg-yellow-500' },
        { id: 'implante', label: 'Implante', color: 'bg-slate-500' },
        { id: 'endodoncia', label: 'Endodoncia', color: 'bg-purple-500' },
        { id: 'extraccion', label: 'Extracción', color: 'bg-red-600' },
    ] as const;
</script>

<div class="flex flex-col gap-4 bg-white dark:bg-slate-900 border border-slate-200 shadow-sm p-4 rounded-xl print:hidden">
    
    <div class="flex flex-wrap items-center justify-between gap-4">
        
        <!-- Herramientas Generales -->
        <div class="flex items-center gap-2">
            <Button 
                variant={currentTool === 'seleccionar' ? 'default' : 'outline'} 
                size="sm" 
                onclick={() => onSetTool('seleccionar')}
                class="flex items-center gap-2"
            >
                <MousePointer2 class="w-4 h-4" /> Seleccionar
            </Button>
            
            <Button 
                variant={currentTool === 'borrador' ? 'destructive' : 'outline'} 
                size="sm" 
                onclick={() => onSetTool('borrador')}
                class="flex items-center gap-2"
            >
                <Eraser class="w-4 h-4" /> Borrador
            </Button>
        </div>

        <div class="h-8 w-px bg-slate-200 hidden md:block"></div>

        <!-- Herramientas Clínicas -->
        <div class="flex flex-wrap items-center gap-2 flex-1">
            <span class="text-xs font-bold text-slate-400 mr-2 uppercase">Superficies:</span>
            {#each SURFACE_TOOLS as tool}
                <button 
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all
                           {currentTool === tool.id ? 'bg-slate-100 ring-2 ring-slate-400 shadow-sm' : 'hover:bg-slate-50 border border-transparent'}"
                    onclick={() => onSetTool(tool.id as ToolType)}
                >
                    <span class="w-3 h-3 rounded-full {tool.color}"></span>
                    {tool.label}
                </button>
            {/each}

            <span class="text-xs font-bold text-slate-400 mx-2 uppercase">Diente:</span>
            {#each TOOTH_TOOLS as tool}
                <button 
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all
                           {currentTool === tool.id ? 'bg-slate-100 ring-2 ring-slate-400 shadow-sm' : 'hover:bg-slate-50 border border-transparent'}"
                    onclick={() => onSetTool(tool.id as ToolType)}
                >
                    <span class="w-3 h-3 rounded-full {tool.color}"></span>
                    {tool.label}
                </button>
            {/each}
        </div>

        <!-- Acción Global -->
        <Button variant="outline" size="sm" onclick={onPrint} class="flex items-center gap-2 ml-auto">
            <Printer class="w-4 h-4" /> Imprimir Odontograma
        </Button>
    </div>
</div>
