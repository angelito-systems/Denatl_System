<script lang="ts">
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import { Button } from '@/components/ui/button';
    import { Monitor, Bell, LogOut } from 'lucide-svelte';
    import { page } from '@inertiajs/svelte';
    import { router } from '@inertiajs/svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    let currentTime = $state(new Date().toLocaleString('es-PE'));

    $effect(() => {
        const interval = setInterval(() => {
            currentTime = new Date().toLocaleString('es-PE');
        }, 1000);
        return () => clearInterval(interval);
    });

    const user = $derived(page.props.auth.user);

    function handleLogout() {
        router.post('/logout');
    }
</script>

<header
    class="flex h-[60px] shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-[60px] md:px-4"
>
    <div class="flex items-center gap-2">
        <SidebarTrigger class="-ml-1" />
        {#if breadcrumbs && breadcrumbs.length > 0}
            <Breadcrumbs {breadcrumbs} />
        {/if}
    </div>

    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-muted-foreground hidden md:inline-block">
            {currentTime}
        </span>
        
        <div class="flex items-center gap-2 text-muted-foreground">
            <Button variant="ghost" size="icon">
                <Monitor class="size-5" />
            </Button>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="size-5" />
                <span class="absolute top-2 right-2 size-2 rounded-full bg-red-600"></span>
            </Button>
        </div>

        <div class="flex items-center gap-3 border-l pl-4 border-sidebar-border/70">
            <span class="text-sm font-medium">
                {user?.nombres || user?.name}
            </span>
            <Button variant="destructive" size="sm" onclick={handleLogout}>
                <LogOut class="size-4 mr-2" />
                Cerrar Sesión
            </Button>
        </div>
    </div>
</header>
