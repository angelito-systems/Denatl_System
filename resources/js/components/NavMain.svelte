<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        SidebarGroup,
        SidebarGroupLabel,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl, cn } from '@/lib/utils';
    import type { NavItem } from '@/types';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';

    let {
        title = 'Platform',
        items = [],
    }: {
        title?: string;
        items: NavItem[];
    } = $props();

    const url = currentUrlState();

    function findActiveGroup(): string {
        for (const item of items) {
            if (item.children?.some(c => url.isCurrentUrl(c.href as string, url.currentUrl))) {
                return item.title;
            }
        }
        return '';
    }

    let openGroup = $state(findActiveGroup());

    function isGroupActive(item: NavItem): boolean {
        return item.children?.some(c => url.isCurrentUrl(c.href as string, url.currentUrl)) ?? false;
    }

    function toggle(key: string) {
        openGroup = openGroup === key ? '' : key;
    }
</script>

<SidebarGroup class="px-2 py-1">
    <SidebarGroupLabel class="text-xs font-bold text-slate-400 mb-2 group-data-[collapsible=icon]:hidden">
        {title}
    </SidebarGroupLabel>

    <SidebarMenu class="gap-1">
        {#each items as item (item.title)}
            {#if item.children && item.children.length > 0}
                {@const isOpen = openGroup === item.title}
                {@const groupActive = isGroupActive(item)}

                <SidebarMenuItem>
                    <SidebarMenuButton asChild tooltip={item.title} isActive={groupActive}>
                        {#snippet children(props)}
                            <button
                                {...props}
                                type="button"
                                onclick={(e) => {
                                    toggle(item.title);
                                    // Mantenemos los eventos nativos de shadcn si existen
                                    if (typeof props.onclick === 'function') props.onclick(e);
                                }}
                                class={cn(
                                    props.class,
                                    "w-full justify-between transition-all duration-200",
                                    groupActive ? "bg-sidebar-accent text-sidebar-accent-foreground font-semibold" : "text-slate-600 dark:text-slate-300"
                                )}
                            >
                                <div class="flex items-center gap-2">
                                    {#if item.icon}
                                        <item.icon class="size-4 shrink-0" />
                                    {/if}
                                    <span class="flex-1 truncate text-left">{item.title}</span>
                                </div>
                                <ChevronRight
                                    class={cn(
                                        'size-4 shrink-0 transition-transform duration-200 opacity-60',
                                        isOpen && 'rotate-90 opacity-100'
                                    )}
                                />
                            </button>
                        {/snippet}
                    </SidebarMenuButton>

                    <div
                        style="display: grid; grid-template-rows: {isOpen ? '1fr' : '0fr'}; transition: grid-template-rows 220ms ease;"
                        class="group-data-[collapsible=icon]:hidden"
                    >
                        <div class="overflow-hidden">
                            <div class="pl-4 pt-1 pb-1">
                                <SidebarMenu class="border-l border-slate-200 dark:border-slate-700 pl-2 gap-1 mt-1">
                                    {#each item.children as child (child.title)}
                                        {@const isChildActive = url.isCurrentUrl(child.href as string, url.currentUrl)}
                                        <SidebarMenuItem>
                                            <SidebarMenuButton asChild isActive={isChildActive} tooltip={child.title}>
                                                {#snippet children(props)}
                                                    <Link
                                                        {...props}
                                                        href={toUrl(child.href as string)}
                                                        class={cn(
                                                            props.class,
                                                            "flex items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors",
                                                            isChildActive
                                                                ? "bg-blue-600 text-white font-medium hover:bg-blue-700 dark:bg-cyan-600 dark:text-white"
                                                                : "text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-cyan-400"
                                                        )}
                                                    >
                                                        {#if child.icon}
                                                            <child.icon class="size-4 shrink-0 opacity-70" />
                                                        {/if}
                                                        <span>{child.title}</span>
                                                    </Link>
                                                {/snippet}
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    {/each}
                                </SidebarMenu>
                            </div>
                        </div>
                    </div>
                </SidebarMenuItem>
            {:else}
                <SidebarMenuItem>
                    {@const isActive = url.isCurrentUrl(item.href as string, url.currentUrl)}
                    <SidebarMenuButton asChild isActive={isActive} tooltip={item.title}>
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={toUrl(item.href as string)}
                                class={cn(
                                    props.class,
                                    "transition-all duration-200 text-slate-600 dark:text-slate-300",
                                    isActive && "font-semibold"
                                )}
                            >
                                {#if item.icon}
                                    <item.icon class="size-4 shrink-0" />
                                {/if}
                                <span>{item.title}</span>
                            </Link>
                        {/snippet}
                    </SidebarMenuButton>
                </SidebarMenuItem>
            {/if}
        {/each}
    </SidebarMenu>
</SidebarGroup>
