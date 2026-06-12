<script lang="ts">
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import { Button } from '@/components/ui/button';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Bell, LogOut, Search, MonitorPlay } from 'lucide-svelte';
    import { page } from '@inertiajs/svelte';
    import { router } from '@inertiajs/svelte';
    import type { BreadcrumbItem } from '@/types';

    let { breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItem[] } = $props();

    let currentTime = $state(new Date().toLocaleString('es-PE', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    }));

    $effect(() => {
        const interval = setInterval(() => {
            currentTime = new Date().toLocaleString('es-PE', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }, 1000);
        return () => clearInterval(interval);
    });

    const user = $derived(page.props.auth.user);
    const userName = $derived(user?.nombres || user?.name || 'Admin');
    const userInitials = $derived(userName.substring(0, 2).toUpperCase());

    function handleLogout() {
        router.post('/logout');
    }
</script>

<header class="sticky top-0 z-10 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/80 md:px-6 transition-all">

    <!-- Lado Izquierdo: Trigger y Breadcrumbs -->
    <div class="flex items-center gap-3">
        <SidebarTrigger class="-ml-2 text-slate-500 hover:text-blue-600 transition-colors" />
        <div class="h-4 w-px bg-slate-300 dark:bg-slate-700 hidden sm:block"></div>
        {#if breadcrumbs && breadcrumbs.length > 0}
            <div class="hidden sm:block">
                <Breadcrumbs {breadcrumbs} />
            </div>
        {/if}
    </div>

    <!-- Lado Derecho: Utilidades y Usuario -->
    <div class="flex items-center gap-4">
        <span class="text-xs font-medium text-slate-500 capitalize hidden lg:inline-block bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-full">
            {currentTime}
        </span>

        <div class="flex items-center gap-1 text-slate-500">
            <a href="/projector" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center whitespace-nowrap rounded-full transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-slate-950 disabled:pointer-events-none disabled:opacity-50 dark:hover:bg-slate-800 dark:hover:text-slate-50 dark:focus-visible:ring-slate-300" title="Abrir Pantalla de Sala de Espera">
                <MonitorPlay class="size-[1.15rem] text-blue-600" />
            </a>
            <Button variant="ghost" size="icon" class="rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                <Search class="size-[1.15rem]" />
            </Button>
            <Button variant="ghost" size="icon" class="relative rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                <Bell class="size-[1.15rem]" />
                <span class="absolute top-2 right-2.5 size-2 rounded-full bg-rose-500 border-2 border-white dark:border-slate-950"></span>
            </Button>
        </div>

        <div class="flex items-center gap-3 border-l border-slate-200 dark:border-slate-800 pl-4">
            <div class="flex items-center gap-2 hidden md:flex">
                <Avatar class="h-8 w-8 shadow-sm">
                    <AvatarFallback class="bg-blue-100 text-blue-700 text-xs font-bold dark:bg-blue-900 dark:text-blue-200">
                        {userInitials}
                    </AvatarFallback>
                </Avatar>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {userName}
                </span>
            </div>
            <Button variant="ghost" size="icon" onclick={handleLogout} class="text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-full transition-colors" title="Cerrar Sesión">
                <LogOut class="size-[1.15rem]" />
            </Button>
        </div>
    </div>
</header>
