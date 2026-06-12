<script lang="ts">
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import OdontogramaPro from '@/components/odontograma/OdontogramaPro.svelte';

    let { 
        isOpen = $bindable(false),
        patientId,
        odontogramData = null,
    } = $props();
</script>

<Dialog bind:open={isOpen}>
    <DialogContent class="max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <DialogHeader>
            <DialogTitle>Registro Histórico del Odontograma</DialogTitle>
            <DialogDescription>Visualizando el estado exacto de la dentadura en esta fecha. Edición deshabilitada.</DialogDescription>
        </DialogHeader>
        
        {#if odontogramData}
            <div class="mt-4 pointer-events-none opacity-90">
                <OdontogramaPro 
                    patientId={patientId} 
                    initialData={odontogramData}
                    readonly={true} 
                />
            </div>
        {/if}
    </DialogContent>
</Dialog>
