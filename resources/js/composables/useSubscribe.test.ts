import { router } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';
import { useSubscribe } from './useSubscribe';

vi.mock('@inertiajs/vue3', async (actual) => ({ ...(await actual<typeof import('@inertiajs/vue3')>()), router: { post: vi.fn() } }));

type Options = { onError: () => void };

describe('useSubscribe', () => {
    it('sends one subscribe however often it is clicked, until the visit comes back', () => {
        const { subscribing, subscribe } = useSubscribe();

        subscribe({ interval: 'annual' });
        subscribe({ interval: 'annual' });

        expect(subscribing.value).toBe(true);
        expect(router.post).toHaveBeenCalledTimes(1);
        expect(router.post).toHaveBeenCalledWith('/billing.subscribe', { interval: 'annual' }, expect.anything());

        // Back with an error (no Stripe, say): the button works again.
        (vi.mocked(router.post).mock.calls[0][2] as Options).onError();
        expect(subscribing.value).toBe(false);

        subscribe({ interval: 'monthly', from: 'house' });
        expect(router.post).toHaveBeenCalledTimes(2);
    });
});
