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
        Paperclip,
        Smile,
        Trash2,
        Mic,
        Video,
        Zap,
        FileText,
        CalendarDays,
        Receipt,
        Search,
        X,
        Check
    } from 'lucide-svelte';
    import { onMount, untrack } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import * as Dialog from '@/components/ui/dialog';
    import { Toast } from '@/lib/utils/toast';
    import 'emoji-picker-element';

    function clickOutside(node: HTMLElement, callback: () => void) {
        const handleClick = (event: MouseEvent) => {
            if (!node.contains(event.target as Node)) {
                callback();
            }
        };
        document.addEventListener('click', handleClick);
        return {
            destroy() {
                document.removeEventListener('click', handleClick);
            }
        };
    }

    import { evolutionWs } from '@/lib/utils/evolution';

    let props = $derived(page.props as any);
    let initialConversations = $derived(props.conversations || []);
    let initialSelectedId = $derived(props.selectedId || null);

    let conversations = $state<any[]>([]);
    let selectedConversationId = $state<number | null>(null);

    // Lista de mensajes de la conversación actual (fusionada con historial real si tuviéramos API)
    let currentMessages = $state<any[]>([]);

    const form = useForm({
        phone: '',
        message: '',
        attachment: null as File | null,
    });

    let searchQuery = $state('');
    let fileInput: HTMLInputElement;

    // Emojis, Slash Commands y Quick Actions
    let showEmojiPicker = $state(false);
    let showQuickActions = $state(false);
    let showComprobanteOptions = $state(false);
    let showContratosOptions = $state(false);
    let showCitasOptions = $state(false);
    
    let patientPayments = $state<any[]>([]);
    let isFetchingPayments = $state(false);
    
    let patientContracts = $state<any[]>([]);
    let isFetchingContracts = $state(false);
    
    let patientAppointments = $state<any[]>([]);
    let isFetchingAppointments = $state(false);
    let emojiPickerEl = $state<any>(null);

    $effect(() => {
        if (emojiPickerEl) {
            const handleEmojiClick = (e: any) => insertEmoji(e.detail.unicode);
            emojiPickerEl.addEventListener('emoji-click', handleEmojiClick);
            return () => emojiPickerEl.removeEventListener('emoji-click', handleEmojiClick);
        }
    });

    let showSlashCommands = $state(false);
    let slashQuery = $state('');
    let selectedIndex = $state(0);

    const predefinedCommands = [
        { trigger: '/hola', preview: 'Saludo inicial', template: '¡Hola {{nombre}}! ¿Cómo te encuentras hoy?' },
        { trigger: '/precio', preview: 'Costo consulta', template: 'Hola {{nombre}}, el costo de la consulta general es de S/ 50.00. ¿Deseas agendar?' },
        { trigger: '/ubicacion', preview: 'Dirección clínica', template: 'Nos ubicamos en Av. Principal 456, Miraflores. ¡Te esperamos {{nombre}}!' },
        { trigger: '/despedida', preview: 'Cerrar chat', template: '¡Gracias por comunicarte con nosotros {{nombre}}! Que tengas un excelente día.' },
        { trigger: '/confirmar', preview: 'Recordatorio cita', template: '{{nombre}}, te escribimos para recordarte tu cita programada para mañana. ¿Nos confirmas tu asistencia?' },
    ];

    let filteredCommands = $derived(
        predefinedCommands.filter(c => c.trigger.toLowerCase().startsWith('/' + slashQuery))
    );

    $effect(() => {
        if (props.conversations) {
            // Inertia will provide new array reference on reload or post
            conversations = props.conversations;
            
            if (initialSelectedId && !selectedConversationId) {
                const conv = conversations.find((c: any) => c.id === initialSelectedId);
                if (conv) selectConversation(conv, false);
            } else if (selectedConversationId) {
                const updatedConv = conversations.find((c: any) => c.id === selectedConversationId);
                if (updatedConv) {
                    const serverMessages = updatedConv.messages || [];
                    untrack(() => {
                        const tempMessages = currentMessages.filter(m => String(m.message_id).startsWith('temp-'));
                        
                        // Merge missing media_url from temp messages to server messages
                        serverMessages.forEach(s => {
                            if (!s.media_url && s.type !== 'text') {
                                const matchingTemp = tempMessages.find(t => 
                                    t.type === s.type && 
                                    t.content === s.content && 
                                    new Date(s.created_at).getTime() > Date.now() - 30000 // Last 30 seconds
                                );
                                if (matchingTemp && matchingTemp.media_url) {
                                    s.media_url = matchingTemp.media_url;
                                }
                            }
                        });

                        // Keep temp messages that don't have a matching server message yet
                        const filteredTempMessages = tempMessages.filter(t => {
                            return !serverMessages.find(s => 
                                s.type === t.type && 
                                s.content === t.content && 
                                new Date(s.created_at).getTime() > Date.now() - 30000
                            );
                        });

                        currentMessages = [...serverMessages, ...filteredTempMessages];
                    });
                    
                    setTimeout(() => {
                        const chatContainer = document.getElementById('chat-messages-container');
                        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
                    }, 100);
                }
            }
        }
    });

    // Grabación Web
    let isRecordingAudio = $state(false);
    let isRecordingVideo = $state(false);
    let recordingTime = $state(0);
    let recordingInterval: any;
    let mediaRecorder: MediaRecorder | null = null;
    let audioChunks: BlobPart[] = [];
    let videoStream = $state<MediaStream | null>(null);
    let cancelNextRecording = false;

    function setVideoStream(node: HTMLVideoElement, stream: MediaStream | null) {
        if (stream) node.srcObject = stream;
        return {
            update(newStream: MediaStream | null) {
                if (newStream) node.srcObject = newStream;
            }
        };
    }

    async function toggleAudioRecording() {
        if (isRecordingAudio) {
            stopRecording();
        } else {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                startRecording(stream, 'audio');
            } catch (err) {
                Toast.error('Error', 'No se pudo acceder al micrófono');
            }
        }
    }

    async function toggleVideoRecording() {
        if (isRecordingVideo) {
            stopRecording();
        } else {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                videoStream = stream;
                startRecording(stream, 'video');
            } catch (err) {
                Toast.error('Error', 'No se pudo acceder a la cámara');
            }
        }
    }

    function startRecording(stream: MediaStream, type: 'audio' | 'video') {
        if (type === 'audio') isRecordingAudio = true;
        if (type === 'video') isRecordingVideo = true;

        recordingTime = 0;
        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) audioChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
            if (!cancelNextRecording) {
                const blob = new Blob(audioChunks, { type: type === 'audio' ? 'audio/webm' : 'video/webm' });
                const file = new File([blob], type === 'audio' ? 'nota_de_voz.webm' : 'video_mensaje.webm', { type: blob.type });
                form.attachment = file;
                submitMessage();
            }

            stream.getTracks().forEach(track => track.stop());
            if (type === 'video') videoStream = null;
            cancelNextRecording = false;
        };

        mediaRecorder.start();
        recordingInterval = setInterval(() => {
            recordingTime++;
            if (recordingTime >= 120) { // 2 minutos límite
                stopRecording();
            }
        }, 1000);
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        clearInterval(recordingInterval);
        isRecordingAudio = false;
        isRecordingVideo = false;
    }

    function cancelRecording() {
        cancelNextRecording = true;
        stopRecording();
        form.attachment = null;
    }

    let selectedConversation = $derived(
        conversations.find((c) => c.id === selectedConversationId),
    );

    function selectConversation(conv: any, updateUrl = true) {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        selectedConversationId = conv.id;
        form.phone = conv.phone_number;
        currentMessages = conv.messages || [];

        if (updateUrl) {
            window.history.pushState({}, '', `/mensajes/${conv.id}`);
        }

        // Auto-scroll al fondo al cargar
        setTimeout(() => {
            const chatContainer = document.getElementById(
                'chat-messages-container',
            );
            if (chatContainer)
                chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 100);
    }

    function handleInput(e: Event) {
        const val = form.message;
        const lastWord = val.split(' ').pop() || '';

        if (lastWord.startsWith('/')) {
            showSlashCommands = true;
            slashQuery = lastWord.substring(1).toLowerCase();
            selectedIndex = 0;
        } else {
            showSlashCommands = false;
        }
    }

    function handleKeydown(e: KeyboardEvent) {
        if (!showSlashCommands) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % filteredCommands.length;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + filteredCommands.length) % filteredCommands.length;
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (filteredCommands[selectedIndex]) {
                selectCommand(filteredCommands[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            showSlashCommands = false;
        }
    }

    function selectCommand(cmd: any) {
        let patientName = selectedConversation?.patient?.first_name || 'Paciente';
        let text = cmd.template.replace(/{{nombre}}/g, patientName);

        const words = form.message.split(' ');
        words.pop(); // Remove trigger
        form.message = (words.length > 0 ? words.join(' ') + ' ' : '') + text;

        showSlashCommands = false;
        setTimeout(() => document.getElementById('chat-input')?.focus(), 10);
    }

    function insertEmoji(emoji: string) {
        form.message += emoji;
        showEmojiPicker = false;
        setTimeout(() => document.getElementById('chat-input')?.focus(), 10);
    }

    function clearChat() {
        if (!selectedConversation) return;
        Toast.confirm(
            '¿Estás seguro de vaciar permanentemente el chat?',
            () => {
                router.post(`/mensajes/${selectedConversation.id}/clear`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        currentMessages = [];
                        let conv = conversations.find(c => c.id === selectedConversationId);
                        if (conv) conv.messages = [];
                        conversations = [...conversations];
                        Toast.success('Éxito', 'Chat vaciado correctamente');
                    }
                });
            },
            {
                title: 'Vaciar Chat',
                message: 'Esta acción no se puede deshacer.',
                type: 'destructive',
                confirmText: 'Vaciar'
            }
        );
    }

    function sendQuickAction(type: string) {
        if (!selectedConversation || !selectedConversation.patient) {
            Toast.error('Error', 'Este chat no está vinculado a un paciente registrado en el sistema.');
            showQuickActions = false;
            return;
        }

        router.post('/whatsapp/send-document', {
            phone: selectedConversation.phone_number,
            patient_id: selectedConversation.patient.id,
            type: type,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showQuickActions = false;
            }
        });
    }

    function handleComprobanteClick(e: Event) {
        e.stopPropagation();
        showQuickActions = false;
        showComprobanteOptions = true;
        patientPayments = [];
    }

    function handleContratoClick(e: Event) {
        e.stopPropagation();
        showQuickActions = false;
        showContratosOptions = true;
        patientContracts = [];
    }

    function handleCitaClick(e: Event) {
        e.stopPropagation();
        showQuickActions = false;
        showCitasOptions = true;
        patientAppointments = [];
    }

    async function fetchPatientPayments() {
        if (!selectedConversation || !selectedConversation.patient) return;
        
        isFetchingPayments = true;
        try {
            const res = await fetch(`/api/patients/${selectedConversation.patient.id}/payments`);
            patientPayments = await res.json();
            if (patientPayments.length === 0) {
                Toast.error('Error', 'No se encontraron comprobantes para este paciente.');
            }
        } catch (error) {
            Toast.error('Error', 'Error al cargar los comprobantes.');
        } finally {
            isFetchingPayments = false;
        }
    }

    async function fetchPatientContracts() {
        if (!selectedConversation || !selectedConversation.patient) return;
        
        isFetchingContracts = true;
        try {
            const res = await fetch(`/api/patients/${selectedConversation.patient.id}/contracts`);
            patientContracts = await res.json();
            if (patientContracts.length === 0) {
                Toast.error('Error', 'No se encontraron contratos para este paciente.');
            }
        } catch (error) {
            Toast.error('Error', 'Error al cargar los contratos.');
        } finally {
            isFetchingContracts = false;
        }
    }

    async function fetchPatientAppointments() {
        if (!selectedConversation || !selectedConversation.patient) return;
        
        isFetchingAppointments = true;
        try {
            const res = await fetch(`/api/patients/${selectedConversation.patient.id}/appointments`);
            patientAppointments = await res.json();
            if (patientAppointments.length === 0) {
                Toast.error('Error', 'No se encontraron citas para este paciente.');
            }
        } catch (error) {
            Toast.error('Error', 'Error al cargar las citas.');
        } finally {
            isFetchingAppointments = false;
        }
    }

    function sendSpecificComprobante(payment_id: number) {
        if (!selectedConversation || !selectedConversation.patient) return;

        router.post('/whatsapp/send-document', {
            phone: selectedConversation.phone_number,
            payment_id: payment_id,
            type: 'comprobante_especifico',
        }, {
            preserveScroll: true,
            onStart: () => addOptimisticMessage('document', '[Documento] Comprobante de Pago'),
            onSuccess: () => {
                showComprobanteOptions = false;
                Toast.success('Éxito', 'Comprobante enviado exitosamente');
            }
        });
    }

    function sendSpecificContrato(contract_id: number) {
        if (!selectedConversation || !selectedConversation.patient) return;

        router.post('/whatsapp/send-document', {
            phone: selectedConversation.phone_number,
            contract_id: contract_id,
            patient_id: selectedConversation.patient.id,
            type: 'contrato_especifico',
        }, {
            preserveScroll: true,
            onStart: () => addOptimisticMessage('document', '[Documento] Contrato Tratamiento'),
            onSuccess: () => {
                showContratosOptions = false;
                Toast.success('Éxito', 'Contrato enviado exitosamente');
            }
        });
    }

    function sendSpecificCita(appointment_id: number) {
        if (!selectedConversation || !selectedConversation.patient) return;

        router.post('/whatsapp/send-document', {
            phone: selectedConversation.phone_number,
            appointment_id: appointment_id,
            patient_id: selectedConversation.patient.id,
            type: 'cita_especifica',
        }, {
            preserveScroll: true,
            onStart: () => addOptimisticMessage('text', '[Recordatorio de Cita] Enviando...'),
            onSuccess: () => {
                showCitasOptions = false;
                Toast.success('Éxito', 'Recordatorio de cita enviado exitosamente');
            }
        });
    }

    function addOptimisticMessage(type: string, content: string, media_url: string | null = null) {
        if (!selectedConversation) return;

        const optimisticId = 'temp-' + Date.now();
        currentMessages = [
            ...currentMessages,
            {
                message_id: optimisticId,
                sender_type: 'advisor',
                content: content,
                type: type,
                media_url: media_url,
                created_at: new Date().toISOString(),
            },
        ];

        setTimeout(() => {
            const chatContainer = document.getElementById('chat-messages-container');
            if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 100);
    }

    function submitMessage() {
        if (!selectedConversation) return;
        if (!form.message && !form.attachment) return; // Prevent empty submit

        const sentMessageContent = form.message;
        const sentAttachment = form.attachment;

        // Optimistic update UI
        if (sentMessageContent) {
            addOptimisticMessage('text', sentMessageContent);
        }
        if (sentAttachment) {
            const type = sentAttachment.type.startsWith('image/') ? 'image' 
                       : sentAttachment.type.startsWith('video/') ? 'video' 
                       : sentAttachment.type.startsWith('audio/') ? 'audio' 
                       : 'document';
            const text = `[${type === 'image' ? 'Imagen' : type === 'video' ? 'Video' : type === 'audio' ? 'Audio' : 'Documento'}] ${sentAttachment.name || ''}`;
            const previewUrl = URL.createObjectURL(sentAttachment);
            addOptimisticMessage(type, text, previewUrl);
        }

        form.post('/mensajes/send', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('message', 'attachment');
                if (fileInput) fileInput.value = '';
            },
        });
    }

    function submitForm(e?: Event) {
        if (e) e.preventDefault();
        submitMessage();
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
                // Limpiar el mensaje temporal (optimistic) si coincide con este real
                currentMessages = currentMessages.filter(m => !(String(m.message_id).startsWith('temp-') && m.content === text));

                // Evitar duplicados por message_id (muy seguro) o si es idéntico recientemente (fallback)
                if (
                    !currentMessages.find(
                        (m) =>
                            (m.message_id && m.message_id === message?.key?.id) ||
                            (m.content === text && new Date(m.created_at).getTime() > Date.now() - 5000 && !String(m.message_id).startsWith('temp-'))
                    )
                ) {
                    currentMessages = [
                        ...currentMessages,
                        {
                            message_id: message?.key?.id,
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
                    message_id: message?.key?.id,
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
                            message_id: message?.key?.id,
                            content: text,
                            type,
                            media_url,
                            created_at: new Date().toISOString(),
                        },
                    ],
                };
                conversations = [newConv, ...conversations];
            }

            // Si el mensaje es del usuario y el bot está activo, recargar en 2.5s para atrapar la respuesta asíncrona del bot
            if (!message?.key?.fromMe && (conv?.bot_status === 'active' || !conv)) {
                setTimeout(() => {
                    router.reload({ only: ['conversations'], preserveScroll: true, preserveState: true });
                }, 2500);
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
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-2 bg-white text-red-600 border-red-200 hover:bg-red-50"
                        onclick={clearChat}
                    >
                        <Trash2 class="w-4 h-4" /> Vaciar
                    </Button>
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
            <div class="bg-[#f0f2f5] p-3 z-10 relative">

                {#if showEmojiPicker}
                    <div class="absolute bottom-full left-0 mb-2 ml-3 shadow-2xl border rounded-xl z-50 bg-white" use:clickOutside={() => showEmojiPicker = false}>
                        <emoji-picker bind:this={emojiPickerEl} class="light"></emoji-picker>
                    </div>
                {/if}

                {#if isRecordingVideo && videoStream}
                    <div class="absolute bottom-full right-4 mb-4 w-48 h-64 bg-black rounded-xl overflow-hidden shadow-2xl border-2 border-red-500 z-50">
                        <!-- svelte-ignore a11y_media_has_caption -->
                        <video
                            autoplay
                            muted
                            class="w-full h-full object-cover"
                            use:setVideoStream={videoStream}
                        ></video>
                        <div class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse flex items-center gap-1">
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            REC
                        </div>
                        <div class="absolute bottom-2 left-0 w-full text-center text-white text-xs font-mono drop-shadow-md">
                            {Math.floor(recordingTime / 60).toString().padStart(2, '0')}:{(recordingTime % 60).toString().padStart(2, '0')} / 02:00
                        </div>
                    </div>
                {/if}

                <form
                    onsubmit={submitForm}
                    class="flex flex-col gap-2 max-w-5xl mx-auto"
                >
                    <div class="flex items-end gap-2 relative">
                        <div class="flex items-center gap-1 mb-1">
                            {#if !isRecordingAudio && !isRecordingVideo}
                                <button type="button" class="p-2 text-gray-500 hover:text-gray-700 transition rounded-full hover:bg-gray-200" onclick={(e) => { e.stopPropagation(); showEmojiPicker = !showEmojiPicker; }} title="Emojis">
                                    <Smile class="w-6 h-6" />
                                </button>
                                <button type="button" class="p-2 text-gray-500 hover:text-gray-700 transition rounded-full hover:bg-gray-200" onclick={() => fileInput.click()} title="Adjuntar archivo">
                                    <Paperclip class="w-6 h-6" />
                                </button>
                                <input type="file" bind:this={fileInput} class="hidden" onchange={(e) => {
                                    const file = (e.target as HTMLInputElement).files?.[0];
                                    if (file) {
                                        if (file.size > 5 * 1024 * 1024) {
                                            Toast.error('Error', 'El archivo excede los 5MB permitidos');
                                            return;
                                        }
                                        form.attachment = file;
                                    }
                                }} />
                                <button type="button" class="p-2 text-gray-500 hover:text-yellow-500 transition rounded-full hover:bg-yellow-50" onclick={(e) => { e.stopPropagation(); showQuickActions = !showQuickActions; }} title="Acciones Rápidas (Documentos)">
                                    <Zap class="w-6 h-6" />
                                </button>
                                <button type="button" class="p-2 text-gray-500 hover:text-red-500 transition rounded-full hover:bg-red-50" onclick={toggleAudioRecording} title="Grabar nota de voz">
                                    <Mic class="w-6 h-6" />
                                </button>
                                <button type="button" class="p-2 text-gray-500 hover:text-blue-500 transition rounded-full hover:bg-blue-50" onclick={toggleVideoRecording} title="Grabar mensaje de video">
                                    <Video class="w-6 h-6" />
                                </button>
                            {:else}
                                <button type="button" class="p-2 text-red-500 hover:text-red-700 transition rounded-full hover:bg-red-50 flex items-center gap-1 px-3" onclick={cancelRecording} title="Cancelar Grabación">
                                    <Trash2 class="w-5 h-5" />
                                    <span class="text-sm font-medium">Cancelar</span>
                                </button>
                            {/if}
                        </div>

                        <div class="flex-1 bg-white rounded-xl flex flex-col shadow-sm border focus-within:ring-1 focus-within:ring-[#00a884] transition-all relative">
                            {#if isRecordingAudio || isRecordingVideo}
                                <div class="flex items-center justify-between px-4 min-h-[44px] bg-red-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                        <span class="text-red-600 font-medium text-sm">Grabando {isRecordingAudio ? 'Audio' : 'Video'}...</span>
                                    </div>
                                    <span class="text-red-600 font-mono font-medium">
                                        {Math.floor(recordingTime / 60).toString().padStart(2, '0')}:{(recordingTime % 60).toString().padStart(2, '0')}
                                    </span>
                                </div>
                            {:else}
                                {#if form.attachment}
                                    <div class="flex items-center justify-between bg-blue-50/50 p-2 border-b text-sm text-blue-800">
                                        <div class="flex items-center gap-2">
                                            <Paperclip class="w-4 h-4" />
                                            <span class="truncate max-w-[200px] font-medium">{form.attachment.name}</span>
                                        </div>
                                        <button type="button" class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 rounded" onclick={() => form.attachment = null}>✕</button>
                                    </div>
                                {/if}

                                <!-- Quick Actions Menu -->
                                {#if showQuickActions}
                                    <div class="absolute bottom-full left-0 mb-2 w-full max-w-sm bg-white shadow-xl border rounded-xl overflow-hidden z-50" use:clickOutside={() => showQuickActions = false}>
                                        <div class="text-xs font-semibold text-gray-500 bg-yellow-50 px-3 py-2 border-b flex items-center gap-1">
                                            <Zap class="w-3 h-3 text-yellow-500" /> Acciones Rápidas
                                        </div>
                                        <div class="py-1 flex flex-col">
                                            <button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center gap-3" onclick={() => sendQuickAction('historia')}>
                                                <FileText class="w-4 h-4 text-blue-500" />
                                                <div>
                                                    <div class="font-medium text-gray-800">Enviar Historia Clínica</div>
                                                    <div class="text-xs text-gray-500">PDF con el historial del paciente</div>
                                                </div>
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center gap-3 border-t border-gray-50" onclick={handleContratoClick}>
                                                <FileText class="w-4 h-4 text-purple-500" />
                                                <div>
                                                    <div class="font-medium text-gray-800">Enviar Contrato General</div>
                                                    <div class="text-xs text-gray-500">Nuevo o seleccionar historial</div>
                                                </div>
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center gap-3 border-t border-gray-50" onclick={handleCitaClick}>
                                                <CalendarDays class="w-4 h-4 text-green-500" />
                                                <div>
                                                    <div class="font-medium text-gray-800">Recordar Cita</div>
                                                    <div class="text-xs text-gray-500">Próxima o seleccionar cita</div>
                                                </div>
                                            </button>
                                            <button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors flex items-center gap-3 border-t border-gray-50" onclick={handleComprobanteClick}>
                                                <Receipt class="w-4 h-4 text-orange-500" />
                                                <div>
                                                    <div class="font-medium text-gray-800">Enviar Comprobante</div>
                                                    <div class="text-xs text-gray-500">Último o seleccionar de historial</div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                {/if}

                                <!-- Slash commands popover inside the input container so it overlays properly -->
                                {#if showSlashCommands && filteredCommands.length > 0}
                                    <div class="absolute bottom-full left-0 mb-2 w-full max-w-sm bg-white shadow-xl border rounded-xl overflow-hidden z-50" use:clickOutside={() => showSlashCommands = false}>
                                        <div class="text-xs font-semibold text-gray-500 bg-gray-50 px-3 py-2 border-b">Plantillas de respuesta</div>
                                        <div class="max-h-48 overflow-y-auto py-1">
                                            {#each filteredCommands as cmd, index}
                                                <button type="button" class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 transition-colors {selectedIndex === index ? 'bg-blue-50' : ''}" onclick={() => selectCommand(cmd)}>
                                                    <div class="font-bold text-blue-700 mb-0.5">{cmd.trigger} <span class="text-gray-400 font-normal ml-1">({cmd.preview})</span></div>
                                                    <div class="text-gray-600 truncate opacity-80 text-xs">{cmd.template}</div>
                                                </button>
                                            {/each}
                                        </div>
                                    </div>
                                {/if}

                                <Input
                                    id="chat-input"
                                    bind:value={form.message}
                                    oninput={handleInput}
                                    onkeydown={handleKeydown}
                                    class="border-none shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 bg-transparent flex-1 py-3 px-4 min-h-[44px]"
                                    placeholder="Escribe un mensaje o '/' para plantillas..."
                                    disabled={selectedConversation.bot_status === 'active'}
                                    autocomplete="off"
                                />
                            {/if}
                        </div>

                        {#if isRecordingAudio || isRecordingVideo}
                            <Button
                                type="button"
                                onclick={stopRecording}
                                class="bg-red-500 hover:bg-red-600 rounded-full w-12 h-12 flex items-center justify-center shadow-sm text-white shrink-0 transition-all active:scale-95 mb-1 animate-pulse"
                                title="Detener y Enviar"
                            >
                                <i class="fas fa-paper-plane text-lg ml-1"></i>
                            </Button>
                        {:else}
                            <Button
                                type="submit"
                                disabled={form.processing || (!form.message && !form.attachment) || selectedConversation.bot_status === 'active'}
                                class="bg-[#00a884] hover:bg-[#008f6f] rounded-full w-12 h-12 flex items-center justify-center shadow-sm text-white shrink-0 transition-all active:scale-95 mb-1"
                            >
                                <i class="fas fa-paper-plane text-lg ml-1"></i>
                            </Button>
                        {/if}
                    </div>
                </form>
                {#if selectedConversation.bot_status === 'active'}
                    <div class="text-center text-xs text-gray-500 mt-3 font-medium bg-white/50 py-1.5 rounded-lg max-w-sm mx-auto">
                        Pausa el bot en la barra superior para poder escribir.
                    </div>
                {/if}
            </div>
        {/if}
    </div>
</div>

<Dialog.Dialog bind:open={showComprobanteOptions}>
    <Dialog.Content class="sm:max-w-md p-0 overflow-hidden">
        <Dialog.Header class="p-4 border-b bg-gray-50">
            <Dialog.Title class="flex items-center gap-2">
                <Receipt class="w-5 h-5 text-orange-500" />
                Enviar Comprobante
            </Dialog.Title>
        </Dialog.Header>
        <div class="p-4 flex flex-col gap-3">
            <button class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={() => { showComprobanteOptions = false; sendQuickAction('ultimo_comprobante'); }}>
                Enviar Último Comprobante
            </button>
            
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">o buscar en el historial</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>
            
            {#if isFetchingPayments}
                <div class="text-center py-6 text-gray-500 text-sm flex flex-col items-center gap-2">
                    <div class="w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                    Cargando historial de pagos...
                </div>
            {:else if patientPayments.length === 0}
                <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={(e) => { e.stopPropagation(); fetchPatientPayments(); }}>
                    <Search class="w-4 h-4" /> Buscar comprobantes anteriores
                </button>
            {:else}
                <div class="max-h-60 overflow-y-auto border rounded-lg divide-y bg-gray-50 shadow-inner">
                    {#each patientPayments as payment}
                        <button class="w-full text-left p-3 bg-white hover:bg-orange-50 transition flex justify-between items-center group" onclick={(e) => { e.stopPropagation(); sendSpecificComprobante(payment.id); }}>
                            <div>
                                <div class="font-medium text-gray-800">S/ {parseFloat(payment.amount).toFixed(2)} - {payment.receipt_type}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{new Date(payment.created_at).toLocaleDateString()} • {payment.treatment_contract?.treatment_name || 'General'}</div>
                            </div>
                            <div class="text-orange-500 opacity-0 group-hover:opacity-100 transition bg-orange-100 p-2 rounded-full">
                                <Send class="w-4 h-4 -ml-0.5" />
                            </div>
                        </button>
                    {/each}
                </div>
            {/if}
        </div>
    </Dialog.Content>
</Dialog.Dialog>

<Dialog.Dialog bind:open={showContratosOptions}>
    <Dialog.Content class="sm:max-w-md p-0 overflow-hidden">
        <Dialog.Header class="p-4 border-b bg-gray-50">
            <Dialog.Title class="flex items-center gap-2">
                <FileText class="w-5 h-5 text-purple-500" />
                Enviar Contrato
            </Dialog.Title>
        </Dialog.Header>
        <div class="p-4 flex flex-col gap-3">
            <button class="w-full bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={() => { showContratosOptions = false; sendQuickAction('contrato'); }}>
                Generar Nuevo Contrato
            </button>
            
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">o buscar en el historial</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>
            
            {#if isFetchingContracts}
                <div class="text-center py-6 text-gray-500 text-sm flex flex-col items-center gap-2">
                    <div class="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                    Cargando contratos...
                </div>
            {:else if patientContracts.length === 0}
                <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={(e) => { e.stopPropagation(); fetchPatientContracts(); }}>
                    <Search class="w-4 h-4" /> Buscar contratos anteriores
                </button>
            {:else}
                <div class="max-h-60 overflow-y-auto border rounded-lg divide-y bg-gray-50 shadow-inner">
                    {#each patientContracts as contract}
                        <button class="w-full text-left p-3 bg-white hover:bg-purple-50 transition flex justify-between items-center group" onclick={(e) => { e.stopPropagation(); sendSpecificContrato(contract.id); }}>
                            <div>
                                <div class="font-medium text-gray-800">{contract.treatment_name}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{new Date(contract.created_at).toLocaleDateString()} • {contract.status}</div>
                            </div>
                            <div class="text-purple-500 opacity-0 group-hover:opacity-100 transition bg-purple-100 p-2 rounded-full">
                                <Send class="w-4 h-4 -ml-0.5" />
                            </div>
                        </button>
                    {/each}
                </div>
            {/if}
        </div>
    </Dialog.Content>
</Dialog.Dialog>

<Dialog.Dialog bind:open={showCitasOptions}>
    <Dialog.Content class="sm:max-w-md p-0 overflow-hidden">
        <Dialog.Header class="p-4 border-b bg-gray-50">
            <Dialog.Title class="flex items-center gap-2">
                <CalendarDays class="w-5 h-5 text-green-500" />
                Recordar Cita
            </Dialog.Title>
        </Dialog.Header>
        <div class="p-4 flex flex-col gap-3">
            <button class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={() => { showCitasOptions = false; sendQuickAction('proxima_cita'); }}>
                Recordar Próxima Cita
            </button>
            
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">o buscar otra cita</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>
            
            {#if isFetchingAppointments}
                <div class="text-center py-6 text-gray-500 text-sm flex flex-col items-center gap-2">
                    <div class="w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full animate-spin"></div>
                    Cargando citas...
                </div>
            {:else if patientAppointments.length === 0}
                <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition" onclick={(e) => { e.stopPropagation(); fetchPatientAppointments(); }}>
                    <Search class="w-4 h-4" /> Buscar citas programadas
                </button>
            {:else}
                <div class="max-h-60 overflow-y-auto border rounded-lg divide-y bg-gray-50 shadow-inner">
                    {#each patientAppointments as appt}
                        <button class="w-full text-left p-3 bg-white hover:bg-green-50 transition flex justify-between items-center group" onclick={(e) => { e.stopPropagation(); sendSpecificCita(appt.id); }}>
                            <div>
                                <div class="font-medium text-gray-800">{new Date(appt.date).toLocaleDateString()} a las {appt.start_time.substring(0,5)}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{appt.treatment} • {appt.status}</div>
                            </div>
                            <div class="text-green-500 opacity-0 group-hover:opacity-100 transition bg-green-100 p-2 rounded-full">
                                <Send class="w-4 h-4 -ml-0.5" />
                            </div>
                        </button>
                    {/each}
                </div>
            {/if}
        </div>
    </Dialog.Content>
</Dialog.Dialog>
