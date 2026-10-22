import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Starting a subscription: the button stays disabled from the first click
 * until the visit comes back to this page (an error, or already subscribed).
 * On success the browser leaves for Stripe Checkout. The server also takes
 * one at a time per household, for two tabs or two owners.
 */
export function useSubscribe() {
    const subscribing = ref(false);

    function subscribe(data: { interval: 'monthly' | 'annual'; from?: 'house' }): void {
        if (subscribing.value) {
            return;
        }

        subscribing.value = true;
        const done = (): void => {
            subscribing.value = false;
        };

        router.post(route('billing.subscribe'), data, {
            preserveScroll: true,
            onSuccess: done,
            onError: done,
            onCancel: done,
            onHttpException: done,
            onNetworkError: done,
        });
    }

    return { subscribing, subscribe };
}
