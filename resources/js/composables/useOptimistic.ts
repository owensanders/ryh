import { showToast } from '@/composables/useToast';
import { ref, watch, type Ref } from 'vue';

export interface OptimisticCallbacks {
    onSuccess: () => void;
    onError: (errors: Record<string, string>) => void;
    onCancel: () => void;
    onFinish: () => void;
}

const FAILED = "That didn't save. Try again.";

/**
 * A local copy of a prop that changes the moment someone acts, then follows
 * the server once the request lands. If it fails, the copy goes back to the
 * server's version and a toast says so. Render from `state`, not the prop.
 */
export function useOptimistic<T>(source: () => T): {
    state: Ref<T>;
    apply: (change: (draft: T) => void, send: (callbacks: OptimisticCallbacks) => void) => void;
} {
    const copy = (value: T): T => JSON.parse(JSON.stringify(value));
    const state = ref(copy(source())) as Ref<T>;

    // Whatever the server sends next is the truth, including after a redirect back with an error.
    watch(source, (value) => (state.value = copy(value)));

    function apply(change: (draft: T) => void, send: (callbacks: OptimisticCallbacks) => void): void {
        change(state.value);

        let outcome: 'pending' | 'saved' | 'rejected' | 'cancelled' = 'pending';

        send({
            onSuccess: () => (outcome = 'saved'),
            onError: (errors) => {
                outcome = 'rejected';
                showToast(Object.values(errors)[0] ?? FAILED, true);
            },
            onCancel: () => (outcome = 'cancelled'),
            onFinish: () => {
                if (outcome === 'saved') {
                    return;
                }

                // A 403, a 500 or a lost connection never sends new props, so undo it here.
                state.value = copy(source());

                if (outcome === 'pending') {
                    showToast(FAILED, true);
                }
            },
        });
    }

    return { state, apply };
}
