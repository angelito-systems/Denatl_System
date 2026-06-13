<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Mensajes CRM', href: '/mensajes' }],
    };
</script>

<script lang="ts">
    import { page, useForm, router } from '@inertiajs/svelte';
    import {
        Phone,
        Send,
        User,
        Bot,
        StopCircle,
        PlayCircle,
        MoreVertical,
    } from 'lucide-svelte';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Toaster } from '@/components/ui/sonner';
    import { toast } from 'svelte-sonner';

    import { evolutionWs } from '@/lib/utils/evolution';

    let props = $derived(page.props as any);
    let initialConversations = $derived(props.conversations || []);

    let conversations = $state<any[]>([]);
    let selectedConversationId = $state<number | null>(null);

    // Lista de mensajes de la conversación actual (fusionada con historial real si tuviéramos API)
    // Por ahora, para la demo, acumularemos los mensajes de WebSocket.
    let currentMessages = $state<any[]>([]);

    const form = useForm({
        phone: '',
        message: '',
    });

    let searchQuery = $state('');

    $effect(() => {
        if (initialConversations.length > 0 && conversations.length === 0) {
            conversations = initialConversations;
        }
    });

    let selectedConversation = $derived(
        conversations.find((c) => c.id === selectedConversationId),
    );

    function selectConversation(conv: any) {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        selectedConversationId = conv.id;
        form.phone = conv.phone_number;
        currentMessages = conv.messages || [];

        // El mensaje automático se envía exclusivamente en toggleBot() cuando cambia a human_assigned

        // Auto-scroll al fondo al cargar
        setTimeout(() => {
            const chatContainer = document.getElementById(
                'chat-messages-container',
            );
            if (chatContainer)
                chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 100);
    }

    function submitForm(e: Event) {
        e.preventDefault();
        if (!selectedConversation) return;

        const sentMessageContent = form.message;

        form.post('/mensajes/send', {
            preserveScroll: true,
            onSuccess: () => {
                // Actualización optimista: inyectamos el mensaje en la UI sin esperar
                const newMessage = {
                    sender_type: 'advisor',
                    content: sentMessageContent,
                    type: 'text',
                    media_url: null,
                    created_at: new Date().toISOString(),
                };

                currentMessages = [...currentMessages, newMessage];

                let conv = conversations.find((c) => c.id === selectedConversationId);
                if (conv) {
                    conv.last_message_at = new Date().toISOString();
                    if (!conv.messages) conv.messages = [];
                    conv.messages.push(newMessage);
                    conversations = [...conversations].sort(
                        (a, b) => new Date(b.last_message_at).getTime() - new Date(a.last_message_at).getTime()
                    );
                }

                form.reset('message');

                // Auto-scroll al fondo
                setTimeout(() => {
                    const chatContainer = document.getElementById('chat-messages-container');
                    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
                }, 100);
            },
        });
    }

    function toggleBot() {
        if (selectedConversation) {
            const newStatus =
                selectedConversation.bot_status === 'active'
                    ? 'human_assigned'
                    : 'active';
            
            const currentPhone = selectedConversation.phone_number;

            router.post(
                `/mensajes/${selectedConversation.id}/toggle-bot`,
                { status: newStatus },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        selectedConversation.bot_status = newStatus;
                        conversations = [...conversations]; // trigger reactivity

                        const userName = page.props.auth?.user?.name || 'especializado';
                        const autoMessage = newStatus === 'human_assigned' 
                            ? `Te hemos asignado al asesor ${userName}. Actualmente se encuentra conectado y atenderá tu consulta en breve. Por favor espera unos momentos mientras revisa tu caso.` 
                            : 'Hola nuevamente. Estoy de vuelta para ayudarte con cualquier consulta o gestión que necesites. 😊';

                        router.post('/mensajes/send', {
                            phone: currentPhone,
                            message: autoMessage
                        }, { preserveScroll: true });
                    },
                },
            );
        }
    }

    onMount(() => {
        evolutionWs.connect();

        const unsubscribe = evolutionWs.onMessageUpsert((message) => {
            console.log('📨 Nuevo mensaje', message);

            const phone = message?.key?.remoteJid?.split('@')[0];

            let type = 'text';
            let media_url = null;
            let text =
                message?.message?.conversation ||
                message?.message?.extendedTextMessage?.text ||
                '';

            if (message?.message?.imageMessage) {
                type = 'image';
                text =
                    '[Imagen] ' + (message.message.imageMessage.caption || '');
                if (message.base64)
                    media_url = `data:${message.message.imageMessage.mimetype || 'image/jpeg'};base64,${message.base64}`;
            } else if (message?.message?.videoMessage) {
                type = 'video';
                text =
                    '[Video] ' + (message.message.videoMessage.caption || '');
                if (message.base64)
                    media_url = `data:${message.message.videoMessage.mimetype || 'video/mp4'};base64,${message.base64}`;
            } else if (message?.message?.audioMessage) {
                type = 'audio';
                text = '[Audio]';
                if (message.base64)
                    media_url = `data:${message.message.audioMessage.mimetype || 'audio/mp3'};base64,${message.base64}`;
            } else if (message?.message?.documentMessage) {
                type = 'document';
                text =
                    '[Documento] ' +
                    (message.message.documentMessage.title ||
                        message.message.documentMessage.caption ||
                        '');
                if (message.base64)
                    media_url = `data:${message.message.documentMessage.mimetype || 'application/pdf'};base64,${message.base64}`;
            } else if (message?.message?.stickerMessage) {
                type = 'sticker';
                text = '[Sticker]';
            }

            if (!text && type === 'text') text = '[Multimedia]';

            // Actualizar la conversación seleccionada si coincide
            if (
                selectedConversation &&
                selectedConversation.phone_number === phone
            ) {
                // Evitar duplicados por messageId
                if (
                    !currentMessages.find(
                        (m) =>
                            m.content === text &&
                            new Date(m.created_at).getTime() >
                                Date.now() - 5000,
                    )
                ) {
                    currentMessages = [
                        ...currentMessages,
                        {
                            sender_type: message?.key?.fromMe
                                ? selectedConversation.bot_status === 'active'
                                    ? 'bot'
                                    : 'advisor'
                                : 'user',
                            content: text,
                            type: type,
                            media_url: media_url,
                            created_at: new Date().toISOString(),
                        },
                    ];

                    // Auto-scroll al fondo
                    setTimeout(() => {
                        const chatContainer = document.getElementById(
                            'chat-messages-container',
                        );
                        if (chatContainer)
                            chatContainer.scrollTop =
                                chatContainer.scrollHeight;
                    }, 100);
                }
            }

            // Actualizar lista izquierda
            let conv = conversations.find((c) => c.phone_number === phone);
            if (conv) {
                conv.last_message_at = new Date().toISOString();
                if (!conv.messages) conv.messages = [];
                conv.messages.push({
                    content: text,
                    type,
                    media_url,
                    created_at: new Date().toISOString(),
                    sender_type: message?.key?.fromMe ? 'advisor' : 'user',
                });
                conversations = [...conversations].sort(
                    (a, b) =>
                        new Date(b.last_message_at).getTime() -
                        new Date(a.last_message_at).getTime(),
                );
            } else {
                // Es un chat nuevo, agregarlo a la lista
                const newConv = {
                    id: Date.now(), // ID temporal hasta recargar
                    phone_number: phone,
                    bot_status: 'active',
                    patient: { first_name: message?.pushName || '' },
                    last_message_at: new Date().toISOString(),
                    messages: [
                        {
                            content: text,
                            type,
                            media_url,
                            created_at: new Date().toISOString(),
                        },
                    ],
                };
                conversations = [newConv, ...conversations];
            }

            // Las notificaciones globales ahora se manejan en evolution.ts
        });

        return () => {
            unsubscribe();
        };
    });
</script>

<AppHead title="WhatsApp CRM" />

<div
    class="flex h-[calc(100vh-5rem)] bg-[#efeae2] rounded-xl overflow-hidden shadow-sm border mt-4 mx-4"
>
    <!-- Sidebar Lista de Chats -->
    <div class="w-[350px] lg:w-[400px] flex flex-col bg-white border-r">
        <!-- Header Sidebar -->
        <div
            class="h-16 bg-[#f0f2f5] flex items-center justify-between px-4 border-b"
        >
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden"
                >
                    <img
                        src="https://ui-avatars.com/api/?name=CRM&background=0D8ABC&color=fff"
                        alt="CRM"
                    />
                </div>
                <span class="font-semibold text-gray-800">WhatsApp CRM</span>
            </div>
            <div class="flex gap-4 text-gray-600">
                <MoreVertical class="w-5 h-5 cursor-pointer" />
            </div>
        </div>

        <!-- Search -->
        <div class="p-2 bg-white border-b">
            <div class="bg-[#f0f2f5] flex items-center rounded-lg px-3 py-1.5">
                <Input
                    bind:value={searchQuery}
                    class="border-none shadow-none bg-transparent h-8 focus-visible:ring-0 focus-visible:ring-offset-0 px-0"
                    placeholder="Buscar chat o contacto..."
                />
            </div>
        </div>

        <!-- Chats List -->
        <div class="flex-1 overflow-y-auto bg-white">
            {#if conversations.length === 0}
                <div
                    class="flex flex-col items-center justify-center h-full text-gray-400 space-y-3"
                >
                    <Phone class="w-8 h-8 opacity-50" />
                    <p class="text-sm">Sin conversaciones</p>
                </div>
            {:else}
                {#each conversations.filter((c) => c.phone_number.includes(searchQuery) || (c.patient?.first_name || '')
                            .toLowerCase()
                            .includes(searchQuery.toLowerCase())) as conv}
                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                    <div
                        class="flex items-center gap-3 px-3 py-3 cursor-pointer hover:bg-[#f5f6f6] transition border-b border-gray-100 {selectedConversationId ===
                        conv.id
                            ? 'bg-[#f0f2f5]'
                            : ''}"
                        onclick={() => selectConversation(conv)}
                    >
                        <div
                            class="w-12 h-12 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center overflow-hidden"
                        >
                            <img
                                src="https://ui-avatars.com/api/?name={conv
                                    .patient?.first_name ||
                                    conv.phone_number}&background=random"
                                alt="Avatar"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div
                                class="flex justify-between items-baseline mb-1"
                            >
                                <span
                                    class="font-medium text-gray-900 truncate"
                                >
                                    {conv.patient
                                        ? `${conv.patient.first_name} ${conv.patient.last_name}`
                                        : `+${conv.phone_number}`}
                                </span>
                                <span
                                    class="text-xs text-gray-500 flex-shrink-0"
                                >
                                    {new Date(
                                        conv.last_message_at,
                                    ).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-gray-500 truncate">
                                    {conv.messages && conv.messages.length > 0
                                        ? conv.messages[
                                              conv.messages.length - 1
                                          ].content
                                        : 'Sin mensajes recientes'}
                                </p>
                                {#if conv.bot_status === 'human_assigned'}
                                    <div
                                        class="w-2 h-2 bg-orange-500 rounded-full"
                                    ></div>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/each}
            {/if}
        </div>
    </div>

    <!-- Chat Area -->
    <div
        class="flex-1 flex flex-col relative bg-[#efeae2]"
        style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.95;"
    >
        {#if !selectedConversation}
            <div
                class="flex-1 flex flex-col items-center justify-center text-center bg-[#f0f2f5] z-10"
            >
                <div class="max-w-md">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                        alt="WA"
                        class="w-24 h-24 mx-auto mb-6 opacity-30 grayscale"
                    />
                    <h2 class="text-2xl text-gray-600 font-light mb-4">
                        WhatsApp Web CRM
                    </h2>
                    <p class="text-gray-500 text-sm">
                        Selecciona una conversación a la izquierda para empezar
                        a chatear o revisar el historial generado por el bot.
                    </p>
                </div>
            </div>
        {:else}
            <!-- Chat Header -->
            <div
                class="h-16 bg-[#f0f2f5] flex items-center justify-between px-4 border-b z-10 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            src="https://ui-avatars.com/api/?name={selectedConversation
                                .patient?.first_name ||
                                selectedConversation.phone_number}&background=random"
                            alt="Avatar"
                        />
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">
                            {selectedConversation.patient
                                ? `${selectedConversation.patient.first_name} ${selectedConversation.patient.last_name}`
                                : `+${selectedConversation.phone_number}`}
                        </div>
                        <div class="text-xs text-gray-500">
                            {selectedConversation.bot_status === 'active'
                                ? '🤖 Bot respondiendo'
                                : '👩‍💼 Asesor Humano'}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {#if selectedConversation.bot_status === 'active'}
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 gap-2 bg-white text-orange-600 border-orange-200 hover:bg-orange-50"
                            onclick={toggleBot}
                        >
                            <StopCircle class="w-4 h-4" /> Pausar Bot
                        </Button>
                    {:else}
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 gap-2 bg-white text-green-600 border-green-200 hover:bg-green-50"
                            onclick={toggleBot}
                        >
                            <PlayCircle class="w-4 h-4" /> Reanudar Bot
                        </Button>
                    {/if}
                </div>
            </div>

            <!-- Messages Stream -->
            <div
                id="chat-messages-container"
                class="flex-1 overflow-y-auto p-4 flex flex-col gap-3 z-10"
            >
                {#if currentMessages.length === 0}
                    <div
                        class="bg-[#d9fdd3] text-gray-800 self-center px-4 py-2 rounded-lg text-sm shadow-sm mt-4"
                    >
                        Los mensajes nuevos aparecerán aquí.
                    </div>
                {/if}

                {#each currentMessages as msg}
                    {@const isMe =
                        msg.sender_type === 'advisor' ||
                        msg.sender_type === 'bot'}
                    <div
                        class="flex {isMe
                            ? 'justify-end'
                            : 'justify-start'} w-full"
                    >
                        <div
                            class="max-w-[70%] rounded-lg px-3 py-2 shadow-sm relative text-sm
                            {isMe
                                ? 'bg-[#d9fdd3] rounded-tr-none'
                                : 'bg-white rounded-tl-none'}
                        "
                        >
                            {#if msg.sender_type === 'bot'}
                                <div
                                    class="text-[10px] text-gray-400 mb-1 flex items-center gap-1"
                                >
                                    <Bot class="w-3 h-3" /> Bot Automático
                                </div>
                            {/if}

                            {#if msg.type === 'image' && msg.media_url}
                                <div class="mb-2 mt-1">
                                    <img
                                        src={msg.media_url}
                                        alt="Imagen"
                                        class="rounded-lg max-h-[300px] object-contain border border-black/10"
                                    />
                                </div>
                            {:else if msg.type === 'video' && msg.media_url}
                                <div class="mb-2 mt-1">
                                    <video
                                        controls
                                        src={msg.media_url}
                                        class="rounded-lg max-w-[250px] max-h-[300px] border border-black/10"
                                    ></video>
                                </div>
                            {:else if msg.type === 'audio' && msg.media_url}
                                <div class="mb-2 mt-1">
                                    <audio
                                        controls
                                        src={msg.media_url}
                                        class="max-w-[250px]"
                                    ></audio>
                                </div>
                            {:else if msg.type === 'document' && msg.media_url}
                                <div
                                    class="mb-2 mt-1 bg-black/5 p-3 rounded-lg flex items-center gap-3 w-fit border border-black/10"
                                >
                                    <div class="text-red-500">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-file-text"
                                            ><path
                                                d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"
                                            /><path
                                                d="M14 2v4a2 2 0 0 0 2 2h4"
                                            /><path d="M10 9H8" /><path
                                                d="M16 13H8"
                                            /><path d="M16 17H8" /></svg
                                        >
                                    </div>
                                    <a
                                        href={msg.media_url}
                                        target="_blank"
                                        class="text-blue-600 hover:underline text-sm font-medium flex-1 truncate"
                                        title="Descargar documento"
                                        >Ver documento</a
                                    >
                                </div>
                            {/if}

                            {#if msg.content}
                                <div
                                    class="text-gray-800 break-words whitespace-pre-wrap"
                                >
                                    {msg.content}
                                </div>
                            {/if}

                            <div
                                class="text-[10px] text-gray-400 text-right mt-1 min-w-[40px]"
                            >
                                {new Date(msg.created_at).toLocaleTimeString(
                                    [],
                                    { hour: '2-digit', minute: '2-digit' },
                                )}
                            </div>
                        </div>
                    </div>
                {/each}
            </div>

            <!-- Chat Input form -->
            <div class="bg-[#f0f2f5] p-3 z-10">
                <form
                    onsubmit={submitForm}
                    class="flex items-center gap-2 max-w-5xl mx-auto"
                >
                    <div
                        class="flex-1 bg-white rounded-lg overflow-hidden flex items-center px-2 py-1 shadow-sm"
                    >
                        <Input
                            bind:value={form.message}
                            class="border-none shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 bg-transparent flex-1"
                            placeholder="Escribe un mensaje..."
                            disabled={selectedConversation.bot_status ===
                                'active'}
                        />
                    </div>

                    <Button
                        type="submit"
                        disabled={form.processing ||
                            !form.message ||
                            selectedConversation.bot_status === 'active'}
                        class="bg-[#00a884] hover:bg-[#008f6f] rounded-full w-12 h-12 flex items-center justify-center shadow-sm text-white shrink-0 transition-colors"
                    >
                        <i class="fa-regular fa-paper-plane"></i>
                    </Button>
                </form>
                {#if selectedConversation.bot_status === 'active'}
                    <div class="text-center text-xs text-gray-500 mt-2">
                        Pausa el bot en la barra superior para poder escribir
                        manualmente.
                    </div>
                {/if}
            </div>
        {/if}
    </div>
</div>

<Toaster position="top-right" />
