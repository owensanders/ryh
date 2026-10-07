/*
 * HouseHub service worker, registered by resources/js/lib/pwa.ts in
 * production builds only.
 *
 * - Page loads go to the network and fall back to /offline.html when there
 *   isn't one.
 * - Vite's hashed build assets never change, so they're served cache-first.
 * - Everything else (Inertia visits, API calls, uploads) passes straight
 *   through, uncached.
 * - Push messages (App\Notifications\PushNotification) become system
 *   notifications; tapping one opens the page it's about.
 *
 * Bump VERSION when this file or the precached files change; the old cache is
 * dropped when the new worker activates.
 */
const VERSION = 'v2';
const CACHE = `househub-${VERSION}`;
// Self-contained (inline styles and icon), so it renders with no network at all.
const PRECACHE = ['/offline.html'];
const MAX_ASSETS = 80;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('househub-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (url.pathname.startsWith('/build/assets/')) {
        event.respondWith(cacheFirst(request));
    }
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);
    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    if (response.ok) {
        await cache.put(request, response.clone());
        await trimAssets(cache);
    }

    return response;
}

// Each deploy adds newly hashed assets, so keep only the most recent ones.
async function trimAssets(cache) {
    const assets = (await cache.keys()).filter((request) => new URL(request.url).pathname.startsWith('/build/assets/'));
    await Promise.all(assets.slice(0, Math.max(0, assets.length - MAX_ASSETS)).map((request) => cache.delete(request)));
}

self.addEventListener('push', (event) => {
    const message = event.data?.json() ?? {};
    event.waitUntil(showUnlessFocused(message));
});

// While HouseHub is open and focused, the bell and toast already show it.
// Safari requires every push to show a notification, so it always does there.
async function showUnlessFocused(message) {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const isSafari = /^((?!chrome|android).)*safari/i.test(self.navigator.userAgent);
    if (!isSafari && windows.some((client) => client.focused && client.visibilityState === 'visible')) {
        return;
    }

    // WebPushMessage::toArray() — title, plus body/icon/tag/data as options.
    const { title = 'HouseHub', ...options } = message;
    await self.registration.showNotification(title, options);
}

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url ?? '/dashboard';
    event.waitUntil(openOrFocus(url));
});

async function openOrFocus(url) {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const existing = windows.find((client) => new URL(client.url).origin === self.location.origin);
    if (existing) {
        await existing.focus();
        // navigate() only works on windows this worker controls.
        const navigated = await existing.navigate(url).catch(() => null);
        if (navigated) {
            return navigated;
        }
    }

    return self.clients.openWindow(url);
}
