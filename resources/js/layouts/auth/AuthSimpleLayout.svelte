<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Activity, ShieldCheck } from 'lucide-svelte'; // Iconos médicos/seguridad
    import type { Snippet } from 'svelte';
    import { home } from '@/routes';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<div class="flex min-h-screen w-full bg-slate-50 dark:bg-slate-950">
    <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:w-1/2 xl:w-[45%] lg:px-20 xl:px-24 bg-white dark:bg-slate-900 shadow-2xl z-10">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-2">
                    <Link href={home()} class="flex items-center gap-3 font-semibold mb-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 shadow-md">
                            <Activity class="h-6 w-6 text-white" />
                        </div>
                        <span class="text-2xl tracking-tight text-slate-900 dark:text-white">Panel<span class="text-blue-600 dark:text-cyan-400">Dental</span></span>
                    </Link>

                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {title}
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {description}
                    </p>
                </div>

                <div class="mt-2">
                    {@render children?.()}
                </div>
            </div>
        </div>
    </div>

    <div class="relative hidden w-0 flex-1 lg:block bg-slate-900">
        <img
            class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
            src="https://images.unsplash.com/photo-1606811841689-23dfddce3e95?q=80&w=2070&auto=format&fit=crop"
            alt="Clínica Dental"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-blue-900/90 via-blue-900/40 to-transparent"></div>

        <div class="absolute bottom-12 left-12 right-12 text-white">
            <div class="flex items-center gap-2 mb-4">
                <ShieldCheck class="h-6 w-6 text-cyan-400" />
                <span class="text-sm font-medium tracking-wider uppercase text-cyan-400">Acceso Seguro</span>
            </div>
            <h2 class="text-3xl font-bold mb-2">Gestión clínica inteligente</h2>
            <p class="text-blue-100 max-w-lg text-lg">
                Administra tus citas, expedientes de pacientes y facturación en un solo lugar de forma rápida y segura.
            </p>
        </div>
    </div>
</div>
