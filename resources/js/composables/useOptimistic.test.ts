import { withSetup } from '@/test/withSetup';
import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';
import { useOptimistic, type OptimisticCallbacks } from './useOptimistic';
import { clientToast } from './useToast';

describe('useOptimistic', () => {
    const items = ref([{ id: 1, done: false }]);
    let callbacks!: OptimisticCallbacks;
    const send = (given: OptimisticCallbacks) => (callbacks = given);
    const tick = (draft: { id: number; done: boolean }[]) => (draft[0].done = true);

    beforeEach(() => {
        items.value = [{ id: 1, done: false }];
        clientToast.value = null;
    });

    it('changes the copy at once, without touching the prop', () => {
        const { state, apply } = withSetup(() => useOptimistic(() => items.value));

        apply(tick, send);

        expect(state.value[0].done).toBe(true);
        expect(items.value[0].done).toBe(false);
    });

    it('keeps the change when the request saves', () => {
        const { state, apply } = withSetup(() => useOptimistic(() => items.value));

        apply(tick, send);
        callbacks.onSuccess();
        callbacks.onFinish();

        expect(state.value[0].done).toBe(true);
        expect(clientToast.value).toBeNull();
    });

    it('puts it back and says why when the server refuses', () => {
        const { state, apply } = withSetup(() => useOptimistic(() => items.value));

        apply(tick, send);
        callbacks.onError({ status: 'That chore is someone else’s.' });
        callbacks.onFinish();

        expect(state.value[0].done).toBe(false);
        expect(clientToast.value).toMatchObject({ message: 'That chore is someone else’s.', error: true });
    });

    it('puts it back with a general message when the request never answers', () => {
        const { state, apply } = withSetup(() => useOptimistic(() => items.value));

        apply(tick, send);
        callbacks.onFinish();

        expect(state.value[0].done).toBe(false);
        expect(clientToast.value).toMatchObject({ message: "That didn't save. Try again.", error: true });
    });

    it('puts it back quietly when the visit is cancelled', () => {
        const { state, apply } = withSetup(() => useOptimistic(() => items.value));

        apply(tick, send);
        callbacks.onCancel();
        callbacks.onFinish();

        expect(state.value[0].done).toBe(false);
        expect(clientToast.value).toBeNull();
    });

    it("follows the server's next props", async () => {
        const { state } = withSetup(() => useOptimistic(() => items.value));

        items.value = [
            { id: 1, done: true },
            { id: 2, done: false },
        ];
        await nextTick();

        expect(state.value).toEqual([
            { id: 1, done: true },
            { id: 2, done: false },
        ]);
    });
});
