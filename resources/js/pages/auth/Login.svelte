<script module lang="ts">
    export const layout = {
        title: '¡Bienvenido de nuevo!',
        description: 'Ingresa tus credenciales para acceder a tu área de trabajo.',
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';

    let {
        status = '',
        canResetPassword,
    }: {
        status?: string;
        canResetPassword: boolean;
    } = $props();
</script>

<AppHead title="Iniciar Sesión" />

{#if status}
    <div class="mb-6 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800">
        {status}
    </div>
{/if}

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="flex flex-col gap-5"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-5">
            <div class="space-y-2">
                <Label for="username" class="text-slate-700 dark:text-slate-300">Usuario</Label>
                <Input
                    id="username"
                    type="text"
                    name="username"
                    required
                    autocomplete="username"
                    placeholder="ej. doctor.perez"
                    class="h-11 bg-slate-50 dark:bg-slate-800 border-slate-200 focus-visible:ring-blue-500"
                />
                <InputError message={errors.username} />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-slate-700 dark:text-slate-300">Contraseña</Label>
                    {#if canResetPassword}
                        <Link
                            href={request()}
                            class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline dark:text-cyan-400"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-11 bg-slate-50 dark:bg-slate-800 border-slate-200 focus-visible:ring-blue-500"
                />
                <InputError message={errors.password} />
            </div>

            <Button
                type="submit"
                class="mt-4 h-11 w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white shadow-md transition-all duration-200 hover:shadow-lg text-base font-medium"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}
                    <Spinner class="mr-2 h-4 w-4" />
                {/if}
                Ingresar al Sistema
            </Button>
        </div>
    {/snippet}
</Form>
