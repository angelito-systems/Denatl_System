<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant =
        | 'default'
        | 'secondary'
        | 'ghost'
        | 'destructive'
        | 'outline'
        | 'link'
        | 'dental-primary'
        | 'dental-success'
        | 'dental-warning'
        | 'dental-outline'; // Nueva variante añadida

    type Size = 'default' | 'sm' | 'lg' | 'icon';
    type AsChildProps = {
        class?: string;
        onClick?: (event: MouseEvent) => void;
        [key: string]: any;
    };

    const base = 'inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 shadow-sm';

    const variants: Record<Variant, string> = {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90 hover:shadow-md',
        secondary: 'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 hover:shadow-md',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        destructive: 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 hover:shadow-md',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground hover:border-primary/50',
        link: 'text-primary underline-offset-4 hover:underline',
        // Estilos dentales personalizados
        'dental-primary': 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-md hover:shadow-lg hover:from-blue-700 hover:to-cyan-700',
        'dental-success': 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-md hover:shadow-lg hover:from-green-700 hover:to-emerald-700',
        'dental-warning': 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md hover:shadow-lg hover:from-orange-600 hover:to-amber-600',
        // Variante idéntica en comportamiento y sombra al éxito, pero gris/outline clínico
        'dental-outline': 'border-2 border-slate-200 bg-white text-slate-700 shadow-md hover:shadow-lg hover:border-blue-500/50 hover:bg-slate-50 transition-all duration-300'
    };

    const sizes: Record<Size, string> = {
        default: 'h-10 px-5 py-2',
        sm: 'h-8 rounded-md px-3 text-xs',
        lg: 'h-11 rounded-lg px-8 text-base',
        icon: 'h-10 w-10',
    };

    let {
        children,
        asChild = false,
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        asChild?: boolean;
        variant?: Variant;
        size?: Size;
        class?: string;
        type?: 'button' | 'submit' | 'reset';
        [key: string]: unknown;
    } = $props();

    const classes = () => cn(base, variants[variant], sizes[size], className);
</script>

{#if asChild}
    <!-- Pasamos la combinación limpia de clases hacia el snippet del hijo -->
    {@render children?.({
        class: classes(),
        ...rest
    })}
{:else}
    <button class={classes()} type={type} {...rest}>
        {@render children?.({})}
    </button>
{/if}
