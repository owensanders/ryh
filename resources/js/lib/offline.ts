import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * HouseHub is online-only. In-app navigation and saving are background
 * requests, so when the connection drops nothing visibly happens — this flag
 * drives the banner that says why (components/OfflineBanner.vue).
 */
export const offline = ref(false);

export function initOfflineDetection(): void {
    offline.value = !navigator.onLine;

    window.addEventListener('offline', () => (offline.value = true));
    window.addEventListener('online', () => {
        if (offline.value) {
            offline.value = false;
            // Pick up anything that changed while we couldn't hear about it.
            router.reload();
        }
    });

    // `navigator.onLine` misses "connected to Wi-Fi but no internet", so a
    // request that fails without any response counts as offline too.
    router.on('exception', (event) => {
        if (!navigator.onLine || isNetworkError(event.detail.exception)) {
            offline.value = true;
            event.preventDefault();
        }
    });

    router.on('success', () => (offline.value = false));
}

function isNetworkError(error: unknown): boolean {
    return typeof error === 'object' && error !== null && 'code' in error && (error as { code?: string }).code === 'ERR_NETWORK';
}
