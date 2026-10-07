import { isIos, isStandalone } from '@/lib/pwa';

/**
 * - `unsupported`: this browser can't do Web Push at all.
 * - `needs-install`: iOS only delivers pushes to the installed (home screen) app.
 * - `denied`: the person blocked notifications; only browser settings can undo that.
 * - `available`: push can be turned on (or already is).
 */
export type PushSupport = 'unsupported' | 'needs-install' | 'denied' | 'available';

export function pushSupport(): PushSupport {
    if (isIos() && !isStandalone()) {
        return 'needs-install';
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return 'unsupported';
    }

    return Notification.permission === 'denied' ? 'denied' : 'available';
}

/** This browser's subscription, if push is on here. */
export async function currentSubscription(): Promise<PushSubscription | null> {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    const registration = await navigator.serviceWorker.getRegistration();

    return (await registration?.pushManager.getSubscription()) ?? null;
}

/**
 * Asks for permission and subscribes this browser. Call only from a tap: the
 * permission prompt must never appear unprompted. Resolves false if the person
 * says no.
 */
export async function enablePush(vapidPublicKey: string): Promise<boolean> {
    if ((await Notification.requestPermission()) !== 'granted') {
        return false;
    }

    // Production builds register the worker at startup; registering here as
    // well (a no-op if it's already there) lets push work in dev too.
    await navigator.serviceWorker.register('/sw.js');
    const registration = await navigator.serviceWorker.ready;

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const encodings = (PushManager as unknown as { supportedContentEncodings?: string[] }).supportedContentEncodings ?? ['aesgcm'];

    await send('POST', route('notifications.subscribe'), {
        ...subscription.toJSON(),
        contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
    });

    return true;
}

/** Unsubscribes this browser and forgets it server-side. Safe to call when push is off. */
export async function disablePush(): Promise<void> {
    const subscription = await currentSubscription();
    if (!subscription) {
        return;
    }

    const { endpoint } = subscription;
    await subscription.unsubscribe();
    // Even if this request fails, the push service now rejects the endpoint and
    // the server drops it on the next send.
    await send('DELETE', route('notifications.unsubscribe'), { endpoint }).catch(() => undefined);
}

async function send(method: 'POST' | 'DELETE', url: string, body: object): Promise<void> {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`Push subscription request failed (${response.status})`);
    }
}

// Laravel sets this cookie on every response and accepts it back as a header.
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function urlBase64ToUint8Array(base64: string): Uint8Array<ArrayBuffer> {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(padded);

    return Uint8Array.from(raw, (char) => char.charCodeAt(0));
}
