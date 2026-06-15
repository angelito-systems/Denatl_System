<script lang="ts">
    import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { X, ZoomIn, ZoomOut, RotateCw, Download, ChevronLeft, ChevronRight } from 'lucide-svelte';

    let { isOpen = $bindable(false), images = [], currentIndex = $bindable(0) } = $props();

    let scale = $state(1);
    let rotation = $state(0);

    let currentImage = $derived(images[currentIndex] || null);

    function resetView() {
        scale = 1;
        rotation = 0;
    }

    function handleZoomIn() {
        scale = Math.min(scale + 0.25, 3);
    }

    function handleZoomOut() {
        scale = Math.max(scale - 0.25, 0.5);
    }

    function handleRotate() {
        rotation = (rotation + 90) % 360;
    }

    function handleNext() {
        if (currentIndex < images.length - 1) {
            currentIndex++;
            resetView();
        }
    }

    function handlePrev() {
        if (currentIndex > 0) {
            currentIndex--;
            resetView();
        }
    }

    $effect(() => {
        if (!isOpen) {
            resetView();
        }
    });
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="max-w-[95vw] w-full h-[95vh] flex flex-col p-0 gap-0 bg-black/95 border-none text-white overflow-hidden [&>button:last-child]:hidden">
        <DialogTitle class="sr-only">Visor de Imagen</DialogTitle>
        <DialogDescription class="sr-only">Visualizando imagen clínica en pantalla completa</DialogDescription>
        
        <!-- Toolbar -->
        <div class="flex items-center justify-between p-4 bg-black/50 backdrop-blur-sm z-10">
            <div class="flex flex-col">
                <span class="font-medium text-lg">{currentImage?.title || currentImage?.file_name}</span>
                <span class="text-xs text-gray-400">
                    {currentImage?.category?.name || 'Sin categoría'} • {currentImage?.taken_at ? new Date(currentImage.taken_at).toLocaleDateString() : ''}
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20" onclick={handleZoomOut} title="Alejar">
                    <ZoomOut class="w-5 h-5" />
                </Button>
                <span class="text-sm w-12 text-center">{Math.round(scale * 100)}%</span>
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20" onclick={handleZoomIn} title="Acercar">
                    <ZoomIn class="w-5 h-5" />
                </Button>
                <div class="w-px h-6 bg-gray-600 mx-2"></div>
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20" onclick={handleRotate} title="Rotar">
                    <RotateCw class="w-5 h-5" />
                </Button>
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20" asChild title="Descargar">
                    <a href={`/patient-images/${currentImage?.id}/download`} download>
                        <Download class="w-5 h-5" />
                    </a>
                </Button>
                <div class="w-px h-6 bg-gray-600 mx-2"></div>
                <Button variant="ghost" size="icon" class="text-white hover:bg-red-500/50 hover:text-white" onclick={() => isOpen = false}>
                    <X class="w-6 h-6" />
                </Button>
            </div>
        </div>

        <!-- Viewer Area -->
        <div class="flex-1 relative flex items-center justify-center overflow-hidden">
            {#if images.length > 1 && currentIndex > 0}
                <Button variant="ghost" size="icon" class="absolute left-4 z-10 text-white bg-black/50 hover:bg-black/80 rounded-full w-12 h-12" onclick={handlePrev}>
                    <ChevronLeft class="w-8 h-8" />
                </Button>
            {/if}

            {#if currentImage}
                {#if currentImage.mime_type === 'application/pdf'}
                    <iframe 
                        src={currentImage.url} 
                        class="w-full h-full border-none bg-white" 
                        title="PDF Viewer"
                    ></iframe>
                {:else}
                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                    <div class="w-full h-full flex items-center justify-center" style="cursor: {scale > 1 ? 'grab' : 'default'}">
                        <img 
                            src={currentImage.url} 
                            alt={currentImage.title}
                            class="max-w-full max-h-full object-contain transition-transform duration-200 ease-out"
                            style="transform: scale({scale}) rotate({rotation}deg);"
                            draggable="false"
                        />
                    </div>
                {/if}
            {/if}

            {#if images.length > 1 && currentIndex < images.length - 1}
                <Button variant="ghost" size="icon" class="absolute right-4 z-10 text-white bg-black/50 hover:bg-black/80 rounded-full w-12 h-12" onclick={handleNext}>
                    <ChevronRight class="w-8 h-8" />
                </Button>
            {/if}
        </div>
    </DialogContent>
</Dialog>
