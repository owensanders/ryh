import { ref } from 'vue';

/** Backs a ConfirmDialog: ask with a message, and run the action only if they confirm. */
export function useConfirm() {
    const open = ref(false);
    const message = ref('');
    let pending: (() => void) | null = null;

    function ask(text: string, action: () => void): void {
        message.value = text;
        pending = action;
        open.value = true;
    }

    function confirm(): void {
        open.value = false;
        pending?.();
        pending = null;
    }

    return { open, message, ask, confirm };
}
