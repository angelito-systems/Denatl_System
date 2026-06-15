import { toast } from 'svelte-sonner';
import HtmlToast from '@/components/HtmlToast.svelte';
import ConfirmToast from '@/components/ConfirmToast.svelte';

export const Toast = {
    // ... success, error, info
    success: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'success' },
            ...options,
        });
    },

    error: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'error' },
            duration: 5000,
            ...options,
        });
    },

    info: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'info' },
            ...options,
        });
    },

    confirm: (message, onConfirm, options = {}) => {
        toast.custom(ConfirmToast, {
            componentProps: { 
                title: options.title || 'Confirmar Acción',
                message: message,
                onConfirm: onConfirm,
                onCancel: options.onCancel || (() => {}),
                confirmText: options.confirmText || 'Aceptar',
                cancelText: options.cancelText || 'Cancelar',
                type: options.type || 'default'
            },
            duration: Infinity, // No auto-close para confirmaciones
            ...options,
        });
    },

    dismiss: (id) => toast.dismiss(id),
};
