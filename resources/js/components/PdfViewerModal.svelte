<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Button } from '@/components/ui/button';
    import { Download, Printer, Loader2 } from 'lucide-svelte';

    // Props del componente
    let { 
        isOpen = $bindable(false),
        url = '',
        title = 'Visor de Documento',
        onPrint = undefined
    } = $props();

    function descargar() {
        if (url) {
            window.open(url, '_blank');
        }
    }

    function imprimir() {
        if (url) {
            const iframe = document.getElementById('pdf-iframe') as HTMLIFrameElement;
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            } else {
                // Si no se puede acceder al iframe, abrir en nueva pestaña (donde el usuario imprimirá manualmente)
                window.open(url, '_blank');
            }
            if (onPrint) onPrint();
        }
    }
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="max-w-5xl w-full h-[85vh] p-0 overflow-hidden flex flex-col">
        <DialogHeader class="px-6 py-4 border-b shrink-0 flex flex-row items-center justify-between">
            <div>
                <DialogTitle class="text-lg">Visor de Documento</DialogTitle>
                <DialogDescription>{title}</DialogDescription>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" onclick={imprimir}>
                    <Printer class="w-4 h-4 mr-2" />
                    Imprimir
                </Button>
                <Button variant="outline" size="sm" onclick={descargar}>
                    <Download class="w-4 h-4 mr-2" />
                    Descargar Original
                </Button>
            </div>
        </DialogHeader>
        <div class="flex-1 w-full bg-slate-100 overflow-hidden relative">
            {#if isOpen && url}
                <!-- Usamos el visor nativo del navegador que es la librería más robusta y completa para PDFs -->
                <iframe 
                    id="pdf-iframe"
                    src={url} 
                    class="w-full h-full border-0 absolute inset-0"
                    title={title}
                ></iframe>
            {:else}
                <div class="flex items-center justify-center h-full">
                    <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
            {/if}
        </div>
    </DialogContent>
</Dialog>
