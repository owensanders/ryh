import { expect, test } from '@playwright/test';
import { storageState } from './support/users';

test.use({ storageState: storageState('owner') });

// RYH is online-only: without a connection, any page shows the service
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

    // The offline page reloads itself once the server answers again (it checks
    // on the `online` event and every 5 seconds).
    await context.setOffline(false);
    await expect(page.getByRole('heading', { name: "You're offline" })).toBeHidden({ timeout: 15_000 });
    await expect(page.getByRole('link', { name: 'Chores', exact: true })).toHaveAttribute('aria-current', 'page');
});

// Moving around inside the app is a background request, not a page load, so
// the offline page never appears; the banner explains why nothing happens.
test('offline, tapping around the app shows the offline banner until the connection returns', async ({ page, context }) => {
    await page.goto('/dashboard');
    const banner = page.getByRole('status').filter({ hasText: "You're offline" });
    await expect(banner).toBeHidden();

    await context.setOffline(true);
    await page.getByRole('link', { name: 'Chores', exact: true }).click();
    await expect(banner).toBeVisible();
    await expect(page).toHaveURL(/\/dashboard$/);

    await context.setOffline(false);
    await expect(banner).toBeHidden();
    await page.getByRole('link', { name: 'Chores', exact: true }).click();
    await expect(page).toHaveURL(/\/chores$/);
});

// Connected to Wi-Fi with no internet: the browser still says it's online, so
// the failed request itself has to raise the banner.
test('a request that fails for lack of connection shows the banner too', async ({ page }) => {
    await page.goto('/dashboard');
    await page.route('**/chores', (route) => route.abort('internetdisconnected'));

    await page.getByRole('link', { name: 'Chores', exact: true }).click();
    const banner = page.getByRole('status').filter({ hasText: "You're offline" });
    await expect(banner).toBeVisible();

    await page.unroute('**/chores');
    await page.getByRole('link', { name: 'Chores', exact: true }).click();
    await expect(page).toHaveURL(/\/chores$/);
    await expect(banner).toBeHidden();
});

// The browser can report it's back online before requests actually get
// through; the offline page keeps checking rather than giving up.
test('the offline page waits until the server really answers before reloading', async ({ page, context }) => {
    await page.goto('/dashboard');
    await page.evaluate(async () => {
        await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
    });

    await context.setOffline(true);
    await page.goto('/chores').catch(() => {});
    const offlinePage = page.getByRole('heading', { name: "You're offline" });
    await expect(offlinePage).toBeVisible();

    let serverReachable = false;
    await context.route('**/up', (route) => (serverReachable ? route.continue() : route.abort('internetdisconnected')));
    await context.setOffline(false);

    await page.waitForTimeout(2_000);
    await expect(offlinePage).toBeVisible();

    serverReachable = true;
    await expect(offlinePage).toBeHidden({ timeout: 15_000 });
    await expect(page.getByRole('link', { name: 'Chores', exact: true })).toHaveAttribute('aria-current', 'page');
});
