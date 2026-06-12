import { page } from '@inertiajs/svelte';
import { io } from 'socket.io-client';
import type { Socket } from 'socket.io-client';
import { writable } from 'svelte/store';
import { Toast } from './toast';

interface EvolutionState {
    isConnected: boolean;
    lastEvent: string | null;
    messages: unknown[];
}

function createEvolutionSocket() {
    const { subscribe, update } = writable<EvolutionState>({
        isConnected: false,
        lastEvent: null,
        messages: [],
    });

    let socket: Socket | null = null;

    const messageListeners: Array<(payload: any) => void> = [];
    const eventListeners = new Map<string, Array<(payload: any) => void>>();

    const connect = () => {
        if (socket?.connected) {
            return;
        }

        const props = (page as any).props || {};

        const config = props.evolutionConfig as
            | {
                  url?: string;
                  apiKey?: string;
                  instance?: string;
              }
            | undefined;

        if (!config?.url) {
            console.error('❌ Evolution URL no configurada');
            return;
        }

        if (!config?.instance) {
            console.error('❌ Evolution Instance no configurada');
            return;
        }

        if (!config?.apiKey) {
            console.error('❌ Evolution API Key no configurada');
            return;
        }

        const baseUrl = config.url.replace(/\/$/, '');
        const namespaceUrl = `${baseUrl}/${config.instance}`;

        console.log('🔌 Conectando a Evolution...');
        console.log('🌐 Namespace:', namespaceUrl);

        socket = io(namespaceUrl, {
            transports: ['websocket'],

            query: {
                apikey: config.apiKey,
            },

            reconnection: true,
            reconnectionAttempts: Infinity,
            reconnectionDelay: 2000,
            reconnectionDelayMax: 10000,
            timeout: 15000,
        });

        socket.on('connect', () => {
            console.log('✅ Conectado');
            console.log('🆔 Socket:', socket?.id);
            console.log('📡 Namespace:', socket?.nsp);

            update((state) => ({
                ...state,
                isConnected: true,
            }));
        });

        socket.on('disconnect', (reason) => {
            console.warn('❌ Desconectado:', reason);

            update((state) => ({
                ...state,
                isConnected: false,
            }));
        });

        socket.on('connect_error', (error) => {
            console.error('❌ Error conexión:', error);

            Toast.error(
                'Error de conexión',
                error.message ?? 'No se pudo conectar con Evolution API',
            );
        });

        socket.onAny((eventName, payload) => {
            try {
                console.log('📨 EVENTO:', eventName);
                console.log(payload);

                update((state) => ({
                    ...state,
                    lastEvent: eventName,
                }));

                const listeners = eventListeners.get(eventName);

                if (listeners) {
                    listeners.forEach((callback) => callback(payload));
                }

                const normalizedEvent = eventName.toUpperCase();

                if (
                    normalizedEvent === 'MESSAGES_UPSERT' ||
                    eventName === 'messages.upsert'
                ) {
                    const message = payload?.data ?? payload;

                    messageListeners.forEach((listener) => {
                        listener(message);
                    });

                    update((state) => ({
                        ...state,
                        messages: [...state.messages, message],
                    }));

                    if (!message?.key?.fromMe) {
                        const senderName =
                            message?.pushName ?? 'Desconocido';

                        const senderPhone =
                            message?.key?.remoteJid
                                ?.split('@')[0] ?? '';

                        const text =
                            message?.message?.conversation ??
                            message?.message?.extendedTextMessage?.text ??
                            '📎 Multimedia';

                        Toast.info(
                            'Nuevo mensaje',
                            `
                            <b>${senderName}</b>
                            <span class="text-xs ml-1">
                                (+${senderPhone})
                            </span>

                            <div class="mt-2 p-2 rounded text-sm italic">
                                "${text}"
                            </div>
                            `,
                        );
                    }
                }

                if (
                    normalizedEvent === 'CONNECTION_UPDATE' ||
                    eventName === 'connection.update'
                ) {
                    const status =
                        payload?.data?.state ??
                        payload?.state;

                    if (status === 'open') {
                        Toast.success(
                            'WhatsApp conectado',
                            'La instancia está operativa',
                        );
                    }

                    if (status === 'close') {
                        Toast.error(
                            'WhatsApp desconectado',
                            'La instancia perdió conexión',
                        );
                    }
                }
            } catch (error) {
                console.error('❌ Error procesando evento:', error);
            }
        });
    };

    const onMessageUpsert = (callback: (payload: any) => void) => {
        messageListeners.push(callback);

        return () => {
            const index = messageListeners.indexOf(callback);

            if (index > -1) {
                messageListeners.splice(index, 1);
            }
        };
    };

    const on = (
        event: string,
        callback: (payload: any) => void,
    ) => {
        const listeners = eventListeners.get(event) ?? [];

        listeners.push(callback);

        eventListeners.set(event, listeners);

        return () => {
            const current = eventListeners.get(event) ?? [];

            eventListeners.set(
                event,
                current.filter((cb) => cb !== callback),
            );
        };
    };

    const emit = (event: string, data?: any) => {
        socket?.emit(event, data);
    };

    const disconnect = () => {
        socket?.disconnect();
        socket = null;

        update((state) => ({
            ...state,
            isConnected: false,
        }));
    };

    const clearMessages = () => {
        update((state) => ({
            ...state,
            messages: [],
        }));
    };

    return {
        subscribe,
        connect,
        disconnect,
        clearMessages,
        onMessageUpsert,
        on,
        emit,

        getSocket: () => socket,
    };
}

export const evolutionWs = createEvolutionSocket();
