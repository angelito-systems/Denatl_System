<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Send, Loader2 } from 'lucide-svelte';
    import { Toast } from '@/lib/utils/toast';

    let { 
        phone, 
        payment_id = null, 
        media_id = null, 
        patient_id = null, 
        type = null, 
        plantilla = null,
        caption = 'Aquí tienes tu documento.', 
        buttonText = 'WhatsApp',
        variant = 'outline',
        class: className = ''
    } = $props();

    const form = useForm({
        phone,
        payment_id,
        media_id,
        patient_id,
        type,
        plantilla,
        caption
    });

    function sendDocument() {
        if (!phone) {
            Toast.error('Error', 'El paciente no tiene un número de teléfono registrado.');
            return;
        }

        form.post('/whatsapp/send-document', {
            preserveScroll: true,
            onSuccess: () => {
                Toast.success('Éxito', 'Documento enviado exitosamente por WhatsApp.');
            },
            onError: () => {
                Toast.error('Error', 'Ocurrió un error al enviar el documento.');
            }
        });
    }
</script>

<Button 
    variant={variant} 
    onclick={sendDocument} 
    disabled={form.processing || !phone}
    class={className}
>
    {#if form.processing}
        <Loader2 class="mr-2 h-4 w-4 animate-spin" />
        Enviando...
    {:else}
        <Send class="mr-2 h-4 w-4" />
        {buttonText}
    {/if}
</Button>
