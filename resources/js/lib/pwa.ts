import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Chrome's install event; not in the DOM typings yet. */
interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

/** True when running as the installed app rather than in a browser tab. */
export function isStandalone(): boolean {
    return window.matchMedia('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;
}

/** iPhone/iPad, where installing is a manual "Add to Home Screen" from the Share sheet. */
export function isIos(): boolean {
    // iPadOS reports itself as a Mac, so tell them apart by touch support.
    return /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.userAgent.includes('Macintosh') && navigator.maxTouchPoints > 1);
}

/**
 * The deferred install prompt (Android/desktop Chrome). It can fire before any
 * component mounts, so it's captured at startup by `initPwa()` and kept here.
 */
export const installPrompt = ref<BeforeInstallPromptEvent | null>(null);

/** Shows the browser's install dialog; resolves true if the user installed. */
export async function promptInstall(): Promise<boolean> {
    const event = installPrompt.value;
    if (!event) {
        return false;
    }

    installPrompt.value = null;
    await event.prompt();

    return (await event.userChoice).outcome === 'accepted';
}

// How long the app must be in the background before resuming refreshes it.
const RESUME_REFRESH_AFTER_MS = 30_000;

export function initPwa(): void {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt.value = event as BeforeInstallPromptEvent;
    });
    window.addEventListener('appinstalled', () => (installPrompt.value = null));

    if (import.meta.env.PROD && 'serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // Without a worker the app still works, just not offline.
            });
        });
    }

    // An installed app is resumed rather than reloaded, so pull fresh data
    // when it comes back after a while in the background.
    let hiddenAt: number | null = null;
    document.addEventListener('visibilitychange', () => {
        if (!isStandalone()) {
            return;
        }

        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
        } else if (hiddenAt !== null && Date.now() - hiddenAt >= RESUME_REFRESH_AFTER_MS) {
            hiddenAt = null;
            router.reload();
        }
    });
}
