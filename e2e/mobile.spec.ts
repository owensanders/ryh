import { expect, test } from '@playwright/test';
import { storageState } from './support/users';

test.use({ storageState: storageState('owner') });

// Phones get the bottom tab bar and a "More" sheet instead of the sidebar.
test('on a phone, the tab bar and More sheet reach every screen', async ({ page }) => {
    await page.goto('/dashboard');
    const tabs = page.getByRole('navigation', { name: 'Main' });

    await tabs.getByRole('link', { name: 'Chores' }).click();
    await expect(page).toHaveURL(/\/chores$/);
    await expect(tabs.getByRole('link', { name: 'Chores' })).toHaveAttribute('aria-current', 'page');

    await tabs.getByRole('button', { name: 'More' }).click();
    await page.locator('#more-sheet').getByRole('link', { name: 'Budget' }).click();
    await expect(page).toHaveURL(/\/budget$/);
    await expect(page.locator('#more-sheet')).toBeHidden();
});

// Chrome on Android offers an install dialog via `beforeinstallprompt`, which
// headless Chromium never fires, so the test fires a stand-in.
test('on Android, the install card opens the browser install dialog', async ({ page }) => {
    await page.goto('/dashboard');
    await page.evaluate(() => {
        const event = Object.assign(new Event('beforeinstallprompt'), {
            prompt: async () => void ((window as Window & { prompted?: boolean }).prompted = true),
            userChoice: Promise.resolve({ outcome: 'accepted' }),
        });
        window.dispatchEvent(event);
    });

    const card = page.getByRole('region', { name: 'Install HouseHub' });
    await card.getByRole('button', { name: 'Install', exact: true }).click();

    expect(await page.evaluate(() => (window as Window & { prompted?: boolean }).prompted)).toBe(true);
    await expect(card).toBeHidden();
});

test.describe('on an iPhone', () => {
    test.use({
        userAgent:
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    });

    test('the install card explains Add to Home Screen, and "Not now" sticks', async ({ page }) => {
        await page.goto('/dashboard');
        const card = page.getByRole('region', { name: 'Install HouseHub' });
        await expect(card).toContainText('Add to Home Screen');

        await card.getByRole('button', { name: 'Not now' }).click();
        await page.reload();
        await expect(page.getByRole('navigation', { name: 'Main' })).toBeVisible();
        await expect(card).toBeHidden();
    });
});
