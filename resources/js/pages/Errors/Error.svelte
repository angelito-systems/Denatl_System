<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Link } from '@inertiajs/svelte';
    import { AlertTriangle, Lock, FileQuestion, ServerCrash, Wrench } from 'lucide-svelte';

    let { status } = $props<{ status: number }>();

    const errors: Record<number, { title: string; description: string; icon: any; color: string }> = {
        403: {
            title: 'Acceso Denegado',
            description: 'No tienes los permisos necesarios para acceder a esta página.',
            icon: Lock,
            color: 'text-amber-500'
        },
        404: {
            title: 'Página no encontrada',
            description: 'La página que estás buscando no existe o ha sido movida.',
            icon: FileQuestion,
            color: 'text-indigo-500'
        },
        500: {
            title: 'Error del Servidor',
            description: 'Algo salió mal en nuestros servidores. Por favor, inténtalo más tarde.',
            icon: ServerCrash,
            color: 'text-red-500'
        },
        503: {
            title: 'Servicio no disponible',
            description: 'Estamos realizando tareas de mantenimiento. Volveremos pronto.',
            icon: Wrench,
            color: 'text-blue-500'
        }
    };

    const error = $derived(errors[status] || {
        title: 'Error Inesperado',
        description: 'Ha ocurrido un error desconocido.',
        icon: AlertTriangle,
        color: 'text-slate-500'
    });

    const Icon = $derived(error.icon);
</script>

<AppHead title={error.title} />

<div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 p-4 text-center">
    <div class="max-w-md w-full flex flex-col items-center gap-6">
        <div class="h-24 w-24 rounded-full bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center">
            <Icon class="h-12 w-12 {error.color}" />
        </div>
        
        <div class="space-y-2">
            <h1 class="text-6xl font-bold tracking-tighter text-slate-900 dark:text-white">
                {status}
            </h1>
            <h2 class="text-2xl font-semibold tracking-tight text-slate-700 dark:text-slate-200">
                {error.title}
            </h2>
            <p class="text-muted-foreground">
                {error.description}
            </p>
        </div>

        <div class="pt-6">
            <Button asChild class="bg-indigo-600 hover:bg-indigo-700">
                <Link href="/">
                    Volver al Inicio
                </Link>
            </Button>
        </div>
    </div>
</div>
