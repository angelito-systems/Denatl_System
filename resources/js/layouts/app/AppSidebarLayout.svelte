<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppContent from '@/components/AppContent.svelte';
    import AppShell from '@/components/AppShell.svelte';
    import AppSidebar from '@/components/AppSidebar.svelte';
    import AppSidebarHeader from '@/components/AppSidebarHeader.svelte';
    import { onMount } from 'svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';
    import { evolutionWs } from '@/lib/utils/evolution';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();

    onMount(() => {
        evolutionWs.connect();
    });
</script>

<AppShell variant="sidebar" class="bg-slate-50 dark:bg-slate-950">
    <AppSidebar />
    <AppContent variant="sidebar" class="overflow-x-hidden">
        <AppSidebarHeader {breadcrumbs} />
        <!-- Contenedor principal con espaciado consistente -->
        <main class="flex-1">
            {@render children?.()}
        </main>
    </AppContent>
    <Toaster />
</AppShell>
