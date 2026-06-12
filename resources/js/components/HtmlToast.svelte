<script lang="ts">
    import { toast } from 'svelte-sonner';

    // 1. SOLUCIÓN TYPESCRIPT: Hacemos que 'id' sea opcional para que TS no pida
    // incluirlo obligatoriamente en los componentProps.
    export let id: string | number | undefined = undefined;

    export let title = '';
    export let html = '';
    export let type: 'success' | 'error' | 'info' | 'warning' | 'default' = 'default';

    const styles = {
        success: 'border-l-4 border-l-primary bg-card text-card-foreground',
        error: 'border-l-4 border-l-destructive bg-destructive text-destructive-foreground',
        info: 'border-l-4 border-l-secondary bg-card text-card-foreground',
        warning: 'border-l-4 border-l-chart-5 bg-card text-card-foreground',
        default: 'border bg-card text-card-foreground'
    };

    const closeBtnStyles = {
        error: 'text-destructive-foreground hover:bg-black/10',
        default: 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
    };
</script>

<!-- 2. SOLUCIÓN TAILWIND: Cambiamos w-[356px] por w-89 -->
<div
    class="w-89 flex items-start gap-3 p-4 rounded-xl shadow-lg transition-all {styles[type]} dental-card-hover"
>
    <div class="flex-1 min-w-0">
        {#if title}
            <h4 class="font-semibold text-sm mb-1">{title}</h4>
        {/if}

        <div class="text-sm opacity-90 prose prose-sm max-w-none">
            <!-- 3. SOLUCIÓN ESLINT: Le decimos al linter que confíe en esta línea -->
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html html}
        </div>
    </div>

    <button
        on:click={() => toast.dismiss(id)}
        class="shrink-0 p-1 rounded-md transition-colors {type === 'error' ? closeBtnStyles.error : closeBtnStyles.default}"
        aria-label="Cerrar notificación"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
        </svg>
    </button>
</div>

<style>
    .prose :global(b), .prose :global(strong) {
        font-weight: 600;
        color: inherit;
    }
    .prose :global(a) {
        text-decoration: underline;
        color: inherit;
        opacity: 0.9;
    }
    .prose :global(a:hover) {
        opacity: 1;
    }
</style>
