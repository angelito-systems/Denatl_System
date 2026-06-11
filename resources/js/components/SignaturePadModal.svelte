<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { PenLine, Eraser, Check } from 'lucide-svelte';
    import { onMount } from 'svelte';

    let { 
        isOpen = $bindable(false), 
        title = 'Firma Digital',
        onSign
    } = $props();

    let canvas: HTMLCanvasElement;
    let ctx: CanvasRenderingContext2D | null;
    let isDrawing = false;
    let hasSignature = $state(false);

    onMount(() => {
        if (isOpen && canvas) {
            initCanvas();
        }
    });

    $effect(() => {
        if (isOpen && canvas && !ctx) {
            setTimeout(initCanvas, 100);
        }
    });

    function initCanvas() {
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        if (!ctx) return;
        
        // Ajustar tamaño real vs renderizado para evitar borrosidad
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;

        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a'; // slate-900
    }

    function startDrawing(e: MouseEvent | TouchEvent) {
        if (!ctx) return;
        isDrawing = true;
        hasSignature = true;
        
        const { x, y } = getCoordinates(e);
        ctx.beginPath();
        ctx.moveTo(x, y);
    }

    function draw(e: MouseEvent | TouchEvent) {
        if (!isDrawing || !ctx) return;
        e.preventDefault(); // Prevenir scroll en móviles
        
        const { x, y } = getCoordinates(e);
        ctx.lineTo(x, y);
        ctx.stroke();
    }

    function stopDrawing() {
        if (!isDrawing || !ctx) return;
        isDrawing = false;
        ctx.closePath();
    }

    function getCoordinates(e: MouseEvent | TouchEvent) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;

        if (e instanceof MouseEvent) {
            clientX = e.clientX;
            clientY = e.clientY;
        } else {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function clear() {
        if (!ctx || !canvas) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasSignature = false;
    }

    function confirm() {
        if (!canvas || !hasSignature) return;
        // Obtener la firma en base64
        const dataUrl = canvas.toDataURL('image/png');
        if (onSign) {
            onSign(dataUrl);
        }
    }
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <PenLine class="w-5 h-5" />
                {title}
            </DialogTitle>
            <DialogDescription>
                Por favor, dibuje su firma en el recuadro inferior.
            </DialogDescription>
        </DialogHeader>

        <div class="mt-4 border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 relative overflow-hidden">
            <canvas 
                bind:this={canvas}
                class="w-full h-64 cursor-crosshair touch-none"
                onmousedown={startDrawing}
                onmousemove={draw}
                onmouseup={stopDrawing}
                onmouseleave={stopDrawing}
                ontouchstart={startDrawing}
                ontouchmove={draw}
                ontouchend={stopDrawing}
            ></canvas>
            
            {#if !hasSignature}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40">
                    <span class="text-xl font-medium text-slate-400 select-none">Firme aquí</span>
                </div>
            {/if}
        </div>

        <div class="flex items-center justify-between mt-6">
            <Button variant="outline" onclick={clear} type="button">
                <Eraser class="w-4 h-4 mr-2" />
                Limpiar
            </Button>
            
            <Button onclick={confirm} disabled={!hasSignature} type="button">
                <Check class="w-4 h-4 mr-2" />
                Confirmar Firma
            </Button>
        </div>
    </DialogContent>
</Dialog>
