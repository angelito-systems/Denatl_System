<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { PenLine, Eraser, Check, ArrowRight } from 'lucide-svelte';
    import { onMount } from 'svelte';

    let { 
        isOpen = $bindable(false), 
        documentName = 'Documento',
        onSign
    } = $props();

    let canvas: HTMLCanvasElement;
    let ctx: CanvasRenderingContext2D | null;
    let isDrawing = false;
    let hasSignature = $state(false);
    
    let step = $state(1); // 1 = Paciente, 2 = Clínica
    let signatureClient = $state('');
    let signatureAdmin = $state('');

    onMount(() => {
        if (isOpen && canvas) {
            initCanvas();
        }
    });

    $effect(() => {
        if (isOpen && canvas && !ctx) {
            setTimeout(initCanvas, 100);
        }
        if (!isOpen) {
            // Reset when closed
            step = 1;
            signatureClient = '';
            signatureAdmin = '';
            hasSignature = false;
        }
    });

    function initCanvas() {
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        if (!ctx) return;
        
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;

        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a';
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
        e.preventDefault();
        
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

    function nextStep() {
        if (!canvas || !hasSignature) return;
        signatureClient = canvas.toDataURL('image/png');
        step = 2;
        clear();
    }

    function confirm() {
        if (!canvas || !hasSignature) return;
        signatureAdmin = canvas.toDataURL('image/png');
        if (onSign) {
            onSign(signatureClient, signatureAdmin);
        }
    }
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <PenLine class="w-5 h-5" />
                {step === 1 ? 'Firma del Paciente' : 'Firma de la Clínica (Admin)'}
            </DialogTitle>
            <DialogDescription>
                {step === 1 
                    ? `Por favor, pida al paciente que firme para el documento: ${documentName}.` 
                    : `Por favor, firme como representante de la clínica para el documento: ${documentName}.`}
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
            
            {#if step === 1}
                <Button onclick={nextStep} disabled={!hasSignature} type="button" class="bg-blue-600 hover:bg-blue-700">
                    Siguiente <ArrowRight class="w-4 h-4 ml-2" />
                </Button>
            {:else}
                <Button onclick={confirm} disabled={!hasSignature} type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                    <Check class="w-4 h-4 mr-2" />
                    Finalizar Firmas
                </Button>
            {/if}
        </div>
    </DialogContent>
</Dialog>
