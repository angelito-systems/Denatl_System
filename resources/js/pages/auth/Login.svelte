<script module lang="ts">
    export const layout = {
        title: 'Iniciar Sesión',
        description: 'Ingresa tu usuario y contraseña para acceder al sistema',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { register } from '@/routes';
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
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        {status}
    </div>
{/if}

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="username">Usuario</Label>
                <Input
                    id="username"
                    type="text"
                    name="username"
                    required
                    autocomplete="username"
                    placeholder="Tu usuario"
                />
                <InputError message={errors.username} />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Contraseña</Label>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Contraseña"
                />
                <InputError message={errors.password} />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                Ingresar
            </Button>
        </div>
    {/snippet}
</Form>
