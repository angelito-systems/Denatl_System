<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import {
        DollarSign,
        LayoutGrid,
        Settings,
        MessageSquare,
        BarChart3,
        UserCog,
        Activity,
        CreditCard,
    } from 'lucide-svelte';
    import Calendar from 'lucide-svelte/icons/calendar';
    import Users from 'lucide-svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
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
    import { index as patientsIndex } from '@/routes/patients';
    import { index as appointmentsIndex } from '@/routes/appointments';
    import { index as treatmentsIndex } from '@/routes/treatments';
    import { index as paymentsIndex } from '@/routes/payments';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const menuItems: NavItem[] = [
        { title: 'Panel Principal', href: dashboard(), icon: LayoutGrid },
        { title: 'Pacientes', href: patientsIndex(), icon: Users },
        { title: 'Citas', href: appointmentsIndex(), icon: Calendar },
        { title: 'Tratamientos', href: treatmentsIndex(), icon: Activity },
        { title: 'Pagos', href: paymentsIndex(), icon: CreditCard },
        { title: 'Reportes', href: '/reportes', icon: BarChart3 },
        { title: 'Gestión de Staff', href: '/staff', icon: UserCog },
        { title: 'Mensajes (CRM)', href: '/mensajes', icon: MessageSquare },
        { title: 'Configuración', href: '/configuracion', icon: Settings },
    ];

    const analisisItems: NavItem[] = [
        { title: 'Facturación', href: '/facturacion', icon: DollarSign },
        { title: 'Reportes', href: '/reportes', icon: BarChart3 },
    ];

    const sistemaItems: NavItem[] = [
        { title: 'Staff', href: '/staff', icon: UserCog },
        { title: 'Configuración', href: '/configuracion', icon: Settings },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain title="MENÚ" items={menuItems} />
        <NavMain title="ANÁLISIS" items={analisisItems} />
        <NavMain title="SISTEMA" items={sistemaItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
