<script lang="ts">
    import { toast } from 'svelte-sonner';
    import { Button } from '@/components/ui/button';

    export let id: string | number | undefined = undefined;
    export let title = '¿Estás seguro?';
    export let message = '';
    export let onConfirm: () => void = () => {};
    export let onCancel: () => void = () => {};
    export let confirmText = 'Confirmar';
    export let cancelText = 'Cancelar';
    export let type: 'default' | 'destructive' = 'default';

    function handleConfirm() {
        toast.dismiss(id);
        onConfirm();
    }

    function handleCancel() {
        toast.dismiss(id);
        onCancel();
    }
</script>

<div class="w-[356px] flex flex-col gap-3 p-4 rounded-xl shadow-lg transition-all border bg-card text-card-foreground">
    <div>
        <h4 class="font-semibold text-sm mb-1">{title}</h4>
        {#if message}
            <p class="text-sm opacity-90 text-muted-foreground">{message}</p>
        {/if}
    </div>
    
    <div class="flex justify-end gap-2 mt-2">
        <Button variant="outline" size="sm" onclick={handleCancel}>{cancelText}</Button>
        <Button variant={type === 'destructive' ? 'destructive' : 'default'} size="sm" onclick={handleConfirm}>{confirmText}</Button>
    </div>
</div>
