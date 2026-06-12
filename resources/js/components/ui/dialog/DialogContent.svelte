<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { fade, scale } from 'svelte/transition';
    import { X } from 'lucide-svelte';
    import { cn } from '@/lib/utils';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    let { class: className = '', children }: { class?: string; children?: Snippet } = $props();

    const { open, setOpen } = getContext<DialogContext>(DIALOG_CONTEXT);

    const close = () => setOpen(false);
</script>

{#if open()}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div
            class="fixed inset-0 bg-black/50 backdrop-blur-sm"
            transition:fade={{ duration: 200 }}
        ></div>
        <div
            class={cn(
                'relative z-10 w-full max-w-lg rounded-xl border bg-background p-6 shadow-2xl',
                className,
            )}
            transition:scale={{ duration: 250, start: 0.95 }}
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 bg-transparent border-0"
                onclick={close}
            >
                <X class="h-4 w-4" />
                <span class="sr-only">Cerrar</span>
            </button>
            {@render children?.()}
        </div>
    </div>
{/if}
