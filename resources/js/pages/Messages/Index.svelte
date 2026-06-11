<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Mensajes CRM', href: '/mensajes' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { useForm } from '@inertiajs/svelte';
    import { Send, Phone } from 'lucide-svelte';

    const form = useForm({
        phone: '',
        message: ''
    });

    function submitForm(e: Event) {
        e.preventDefault();
        form.post('/mensajes/send', {
            onSuccess: () => {
                form.reset('message');
                alert('Mensaje encolado para envío.');
            }
        });
    }

    const quickReplies = [
        "Hola, te escribimos para recordarte tu cita programada para el día de mañana.",
        "Hola, por favor confírmanos tu asistencia respondiendo con un SÍ.",
        "Tu cita ha sido reprogramada exitosamente."
    ];

    function fillQuickReply(reply: string) {
        form.message = reply;
    }
</script>

<AppHead title="Mensajes CRM (WhatsApp)" />

<div class="flex h-[calc(100vh-6rem)] gap-4 p-6">
    <!-- Sidebar CRM -->
    <div class="w-1/3 flex flex-col bg-card rounded-lg border shadow-sm">
        <div class="p-4 border-b">
            <h2 class="text-xl font-bold">Chats Recientes</h2>
            <div class="mt-4 relative">
                <Input type="search" placeholder="Buscar paciente..." />
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-2">
            <div class="text-center text-muted-foreground text-sm mt-10">
                Seleccione un paciente para ver su historial o envíe un mensaje nuevo.
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col bg-card rounded-lg border shadow-sm">
        <div class="p-4 border-b flex items-center justify-between">
            <div class="font-bold text-lg flex items-center gap-2">
                <Phone class="w-5 h-5 text-green-600" />
                Nuevo Mensaje WhatsApp
            </div>
        </div>
        
        <div class="flex-1 p-6 flex flex-col gap-6 overflow-y-auto bg-slate-50">
            <div class="bg-white p-4 rounded-lg shadow-sm border max-w-lg">
                <p class="text-sm text-slate-800 font-medium mb-2">Respuestas Rápidas</p>
                <div class="flex flex-col gap-2">
                    {#each quickReplies as reply}
                        <Button variant="outline" class="justify-start text-left h-auto py-2" onclick={() => fillQuickReply(reply)}>
                            {reply}
                        </Button>
                    {/each}
                </div>
            </div>
        </div>

        <div class="p-4 bg-white border-t">
            <form onsubmit={submitForm} class="flex flex-col gap-4">
                <div>
                    <label for="phone" class="text-sm font-medium">Número de Teléfono</label>
                    <Input id="phone" type="text" placeholder="Ej: +51 987654321" bind:value={form.phone} class="mt-1" required />
                    {#if form.errors.phone}
                        <div class="text-red-500 text-sm mt-1">{form.errors.phone}</div>
                    {/if}
                </div>
                <div class="flex gap-2">
                    <Input 
                        type="text" 
                        placeholder="Escribe un mensaje..." 
                        bind:value={form.message} 
                        class="flex-1"
                        required
                    />
                    <Button type="submit" disabled={form.processing} class="bg-green-600 hover:bg-green-700">
                        <Send class="w-4 h-4 mr-2" />
                        Enviar
                    </Button>
                </div>
            </form>
        </div>
    </div>
</div>
