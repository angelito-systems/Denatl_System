<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Search } from 'lucide-svelte';

    let { 
        dni = $bindable(), 
        firstName = $bindable(), 
        lastName = $bindable(),
        disabled = false
    } = $props<{
        dni: string;
        firstName: string;
        lastName: string;
        disabled?: boolean;
    }>();

    let searching = $state(false);
    let errorMsg = $state('');

    async function searchDNI() {
        if (!dni || dni.length !== 8) {
            errorMsg = 'El DNI debe tener 8 dígitos';
            return;
        }
        
        searching = true;
        errorMsg = '';
        
        try {
            const response = await fetch(`/api/reniec/${dni}`);
            const data = await response.json();
            
            if (data.success) {
                // Formatear Nombres y Apellidos en Capitalize Case si se desea, o dejar tal cual
                firstName = data.data.nombres;
                lastName = `${data.data.apellido_paterno} ${data.data.apellido_materno}`.trim();
            } else {
                errorMsg = data.error || 'DNI no encontrado';
            }
        } catch (e) {
            errorMsg = 'Error de conexión';
        } finally {
            searching = false;
        }
    }
</script>

<div class="flex flex-col gap-1 w-full">
    <div class="flex gap-2 w-full">
        <Input
            type="text"
            bind:value={dni}
            maxlength={8}
            placeholder="Ingrese DNI"
            {disabled}
            class="flex-1"
        />
        <Button type="button" variant="secondary" disabled={searching || disabled} onclick={searchDNI}>
            <Search class="h-4 w-4 mr-2" />
            {searching ? 'Buscando...' : 'RENIEC'}
        </Button>
    </div>
    {#if errorMsg}
        <span class="text-sm text-red-500">{errorMsg}</span>
    {/if}
</div>
