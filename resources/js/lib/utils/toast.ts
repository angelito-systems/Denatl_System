import { toast } from 'svelte-sonner';
import HtmlToast from '@/components/HtmlToast.svelte';

export const Toast = {
    /**
     * Muestra una notificación de éxito
     * @param {string} title - Título del toast
     * @param {string} htmlContent - Contenido en formato HTML
     * @param {object} options - Opciones adicionales de svelte-sonner (duración, etc.)
     */
    success: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'success' },
            ...options,
        });
    },

    /**
     * Muestra una notificación de error (usa el color destructive)
     */
    error: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'error' },
            duration: 5000, // Los errores suelen necesitar más tiempo para leerse
            ...options,
        });
    },

    /**
     * Muestra una notificación informativa
     */
    info: (title, htmlContent, options = {}) => {
        toast.custom(HtmlToast, {
            componentProps: { title, html: htmlContent, type: 'info' },
            ...options,
        });
    },

    /**
     * Permite cerrar todos los toasts o uno específico
     */
    dismiss: (id) => toast.dismiss(id),
};
