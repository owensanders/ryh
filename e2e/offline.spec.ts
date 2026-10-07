import { expect, test } from '@playwright/test';
import { storageState } from './support/users';

test.use({ storageState: storageState('owner') });

// HouseHub is online-only: without a connection, any page shows the service
// worker's offline page, which comes back by itself once the connection does.
test('offline, a page load shows the offline page, then recovers when back online', async ({ page, context }) => {
    await page.goto('/dashboard');
    // Production builds register the worker at startup; register it here too so
    // the test also runs against the Vite dev server.
    await page.evaluate(async () => {
        await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
    });

    await context.setOffline(true);
    await page.goto('/chores').catch(() => {});
    await expect(page.getByRole('heading', { name: "You're offline" })).toBeVisible();

    // The offline page reloads itself on the browser's `online` event.
    await context.setOffline(false);
    await expect(page.getByRole('heading', { name: "You're offline" })).toBeHidden();
    await expect(page.getByRole('link', { name: 'Chores', exact: true })).toHaveAttribute('aria-current', 'page');
});
