<script lang="ts">
    import { Dialog, DialogContent, DialogTitle, DialogDescription, DialogHeader } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { ZoomIn, ZoomOut, RotateCw } from 'lucide-svelte';

    let { isOpen = $bindable(false), images = [], selectedBefore = null, selectedAfter = null } = $props();

    let imageBefore = $state(selectedBefore);
    let imageAfter = $state(selectedAfter);

    let scaleBefore = $state(1);
    let rotationBefore = $state(0);
    
    let scaleAfter = $state(1);
    let rotationAfter = $state(0);

    $effect(() => {
        if (isOpen) {
            if (!imageBefore && images.length > 0) imageBefore = images[0];
            if (!imageAfter && images.length > 1) imageAfter = images[1];
            scaleBefore = 1;
            rotationBefore = 0;
            scaleAfter = 1;
            rotationAfter = 0;
        }
    });

</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="max-w-6xl w-full h-[90vh] flex flex-col p-6 overflow-hidden">
        <DialogHeader>
            <DialogTitle>Comparación Clínica Lado a Lado</DialogTitle>
            <DialogDescription>Selecciona dos imágenes para comparar la evolución clínica del paciente.</DialogDescription>
        </DialogHeader>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="flex flex-col gap-2">
                <Label>Imagen Anterior (Antes)</Label>
                <select bind:value={imageBefore} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value={null}>Seleccionar...</option>
                    {#each images as img}
                        <option value={img}>{img.title || img.file_name} ({img.category?.name})</option>
                    {/each}
                </select>
            </div>
            <div class="flex flex-col gap-2">
                <Label>Imagen Posterior (Después)</Label>
                <select bind:value={imageAfter} class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value={null}>Seleccionar...</option>
                    {#each images as img}
                        <option value={img}>{img.title || img.file_name} ({img.category?.name})</option>
                    {/each}
                </select>
            </div>
        </div>

        <div class="flex-1 grid grid-cols-2 gap-6 overflow-hidden bg-slate-50 p-4 rounded-xl border">
            <!-- Antes -->
            <div class="flex flex-col gap-2 items-center justify-center h-full border-r border-dashed pr-6 overflow-hidden">
                <div class="flex items-center justify-between w-full mb-1">
                    <div class="bg-blue-100 text-blue-700 px-4 py-1 rounded-full font-semibold text-sm shadow-sm">ANTES</div>
                    {#if imageBefore}
                    <div class="flex items-center gap-1 bg-white border rounded-lg p-1 shadow-sm">
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => scaleBefore = Math.max(scaleBefore - 0.25, 0.5)} title="Alejar"><ZoomOut class="w-4 h-4" /></Button>
                        <span class="text-[10px] w-8 text-center">{Math.round(scaleBefore * 100)}%</span>
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => scaleBefore = Math.min(scaleBefore + 0.25, 4)} title="Acercar"><ZoomIn class="w-4 h-4" /></Button>
                        <div class="w-px h-4 bg-gray-200 mx-1"></div>
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => rotationBefore = (rotationBefore + 90) % 360} title="Rotar"><RotateCw class="w-4 h-4" /></Button>
                    </div>
                    {/if}
                </div>
                {#if imageBefore}
                    <div class="relative w-full h-full flex items-center justify-center rounded-lg overflow-hidden bg-black/5 shadow-inner" style="cursor: {scaleBefore > 1 ? 'grab' : 'default'}">
                        <img 
                            src={imageBefore.url} 
                            alt="Antes" 
                            class="max-w-full max-h-full object-contain transition-transform duration-200 ease-out"
                            style="transform: scale({scaleBefore}) rotate({rotationBefore}deg);"
                            draggable="false"
                        />
                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white p-2 text-xs text-center z-10 pointer-events-none">
                            {new Date(imageBefore.taken_at || imageBefore.created_at).toLocaleDateString()}
                        </div>
                    </div>
                {:else}
                    <div class="text-muted-foreground text-sm flex items-center justify-center h-full">Seleccione una imagen</div>
                {/if}
            </div>

            <!-- Despues -->
            <div class="flex flex-col gap-2 items-center justify-center h-full pl-2 overflow-hidden">
                <div class="flex items-center justify-between w-full mb-1">
                    <div class="bg-green-100 text-green-700 px-4 py-1 rounded-full font-semibold text-sm shadow-sm">DESPUÉS</div>
                    {#if imageAfter}
                    <div class="flex items-center gap-1 bg-white border rounded-lg p-1 shadow-sm">
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => scaleAfter = Math.max(scaleAfter - 0.25, 0.5)} title="Alejar"><ZoomOut class="w-4 h-4" /></Button>
                        <span class="text-[10px] w-8 text-center">{Math.round(scaleAfter * 100)}%</span>
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => scaleAfter = Math.min(scaleAfter + 0.25, 4)} title="Acercar"><ZoomIn class="w-4 h-4" /></Button>
                        <div class="w-px h-4 bg-gray-200 mx-1"></div>
                        <Button variant="ghost" size="icon" class="h-7 w-7" onclick={() => rotationAfter = (rotationAfter + 90) % 360} title="Rotar"><RotateCw class="w-4 h-4" /></Button>
                    </div>
                    {/if}
                </div>
                {#if imageAfter}
                    <div class="relative w-full h-full flex items-center justify-center rounded-lg overflow-hidden bg-black/5 shadow-inner" style="cursor: {scaleAfter > 1 ? 'grab' : 'default'}">
                        <img 
                            src={imageAfter.url} 
                            alt="Después" 
                            class="max-w-full max-h-full object-contain transition-transform duration-200 ease-out"
                            style="transform: scale({scaleAfter}) rotate({rotationAfter}deg);"
                            draggable="false"
                        />
                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white p-2 text-xs text-center z-10 pointer-events-none">
                            {new Date(imageAfter.taken_at || imageAfter.created_at).toLocaleDateString()}
                        </div>
                    </div>
                {:else}
                    <div class="text-muted-foreground text-sm flex items-center justify-center h-full">Seleccione una imagen</div>
                {/if}
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <Button variant="outline" onclick={() => isOpen = false}>Cerrar Comparación</Button>
        </div>
    </DialogContent>
</Dialog>
