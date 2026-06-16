<script lang="ts">
    import { Link } from '@inertiajs/svelte';

    import {
        LayoutGrid,
        Settings,
        MessageSquare,
        BarChart3,
        CreditCard,
        Wallet,
        Calendar,
        Users,
        ClipboardList,
        Receipt,
        Megaphone,
        Gift,
        Star,
        Building2,
        UserRoundCog,
        Stethoscope,
        Shield,
    } from 'lucide-svelte';

    import type { Snippet } from 'svelte';

    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';

    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';

    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';

    let { children }: { children?: Snippet } = $props();

    const mainItems: NavItem[] = [
        {
            title: 'Panel Principal',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Recepción',
            icon: Users,
            children: [
                {
                    title: 'Pacientes',
                    href: '/patients',
                    icon: Users,
                    permissions: ['ver_pacientes'],
                },
                {
                    title: 'Citas',
                    href: '/appointments',
                    icon: Calendar,
                    permissions: ['ver_citas'],
                },
            ],
        },
        {
            title: 'Clínica',
            icon: Stethoscope,
            children: [
                {
                    title: 'Tratamientos',
                    href: '/treatments',
                    icon: ClipboardList,
                    permissions: ['ver_contratos'],
                },
                {
                    title: 'Categorías',
                    href: '/treatment-categories',
                    icon: ClipboardList,
                    permissions: ['ver_contratos'],
                },
            ],
        },
        {
            title: 'Facturación',
            icon: Receipt,
            children: [
                {
                    title: 'Caja',
                    href: '/cashbox',
                    icon: Wallet,
                    permissions: ['ver_facturacion'],
                },
                {
                    title: 'Pagos',
                    href: '/payments',
                    icon: CreditCard,
                    permissions: ['ver_facturacion'],
                },
                {
                    title: 'Reportes',
                    href: '/reportes',
                    icon: BarChart3,
                    permissions: ['ver_reportes'],
                },
            ],
        },
    ];

    const adminItems: NavItem[] = [
        {
            title: 'Marketing y Bot',
            icon: Megaphone,
            children: [
                {
                    title: 'Mensajes (CRM)',
                    href: '/mensajes',
                    icon: MessageSquare,
                    permissions: ['ver_mensajes_crm'],
                },
                {
                    title: 'Promociones',
                    href: '/promotions',
                    icon: Megaphone,
                    roles: ['Administrador'],
                },
                {
                    title: 'Sorteos',
                    href: '/raffles',
                    icon: Gift,
                    roles: ['Administrador'],
                },
                {
                    title: 'Valoraciones',
                    href: '/ratings',
                    icon: Star,
                    roles: ['Administrador'],
                },
            ],
        },
        {
            title: 'Administración',
            icon: Building2,
            children: [
                {
                    title: 'Staff',
                    href: '/staff',
                    icon: UserRoundCog,
                    permissions: ['ver_staff'],
                },
                {
                    title: 'Roles y Permisos',
                    href: '/roles',
                    icon: Shield,
                    roles: ['Administrador'],
                },
                {
                    title: 'Configuración',
                    href: '/configuracion',
                    icon: Settings,
                    permissions: ['ver_configuracion'],
                },
            ],
        },
    ];
    import { page } from '@inertiajs/svelte';

    const userRoles = $derived(page.props.auth?.user?.roles || []);
    const userPermissions = $derived(page.props.auth?.user?.permissions || []);

    function hasAccess(item: NavItem): boolean {
        // Si el usuario es Administrador, tiene acceso a todo
        if (userRoles.includes('Administrador')) return true;

        let access = true;
        
        if (item.roles && item.roles.length > 0) {
            access = item.roles.some(role => userRoles.includes(role));
        }
        
        if (access && item.permissions && item.permissions.length > 0) {
            access = item.permissions.some(permission => userPermissions.includes(permission));
        }

        return access;
    }

    function filterItems(items: NavItem[]): NavItem[] {
        return items
            .filter(hasAccess)
            .map(item => {
                if (item.children) {
                    return { ...item, children: filterItems(item.children) };
                }
                return item;
            })
            .filter(item => !item.children || item.children.length > 0);
    }

    const filteredMainItems = $derived(filterItems(mainItems));
    const filteredAdminItems = $derived(filterItems(adminItems));
</script>

<Sidebar
    collapsible="icon"
    variant="inset"
    class="border-r border-slate-200 dark:border-slate-800"
>
    <SidebarHeader class="py-4">
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton
                    size="lg"
                    asChild
                    class="hover:bg-transparent hover:opacity-80 transition-opacity"
                >
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class="{props.class} flex items-center gap-3"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 via-cyan-500 to-teal-500 shadow-lg"
                            >
                                <Stethoscope class="h-5 w-5 text-white" />
                            </div>

                            <div
                                class="flex flex-col truncate group-data-[collapsible=icon]:hidden"
                            >
                                <span
                                    class="text-base font-bold tracking-tight text-slate-900 dark:text-white"
                                >
                                    Panel
                                    <span
                                        class="text-blue-600 dark:text-cyan-400"
                                    >
                                        Dental
                                    </span>
                                </span>

                                <span
                                    class="text-[10px] uppercase tracking-wider text-slate-500"
                                >
                                    Gestión Clínica
                                </span>
                            </div>
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent class="px-2">
        <NavMain
            title="MENÚ PRINCIPAL"
            items={filteredMainItems}
        />

        {#if filteredAdminItems.length > 0}
            <div class="mx-4 my-2 h-px bg-slate-200 dark:bg-slate-800"></div>

            <NavMain
                title="SISTEMA"
                items={filteredAdminItems}
            />
        {/if}
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>

{@render children?.()}
