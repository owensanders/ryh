import { ref } from 'vue';

export interface ClientToast {
    message: string;
    error: boolean;
    /** So the same message twice still shows twice. */
    id: number;
}

/** Toasts raised in the browser, beside the ones the server flashes. Shown by Toast.vue. */
export const clientToast = ref<ClientToast | null>(null);

let lastId = 0;

export function showToast(message: string, error = false): void {
    clientToast.value = { message, error, id: ++lastId };
}
