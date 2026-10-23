import { expect, test } from './support/fixtures';

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

    const card = page.getByRole('region', { name: 'Install RYH' });
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
        const card = page.getByRole('region', { name: 'Install RYH' });
        await expect(card).toContainText('Add to Home Screen');

        await card.getByRole('button', { name: 'Not now' }).click();
        await page.reload();
        await expect(page.getByRole('navigation', { name: 'Main' })).toBeVisible();
        await expect(card).toBeHidden();
    });
});

// The frame is a persistent layout: moving between screens swaps only the
// content, so the tab bar and header aren't rebuilt (which felt like a reload).
test('moving between screens keeps the tab bar and header, and starts each screen at the top', async ({ page }) => {
    await page.goto('/dashboard');
    const tabs = page.getByRole('navigation', { name: 'Main' });
    await tabs.evaluate((nav) => (nav.dataset.original = 'yes'));
    await page.locator('header').evaluate((header) => (header.dataset.original = 'yes'));
    // Inertia records the scroll position for Back shortly after scrolling stops. Scrolled
    // again each time round, in case the dashboard hadn't finished growing tall enough yet.
    await expect
        .poll(async () => {
            await page.evaluate(() => window.scrollTo(0, 600));

            return page.evaluate(() => history.state?.documentScrollPosition?.top ?? 0);
        })
        .toBeGreaterThan(500);

    await tabs.getByRole('link', { name: 'Chores' }).click();
    await expect(page).toHaveURL(/\/chores$/);
    await expect(page.locator('header')).toContainText('Chores');
    await expect(page).toHaveTitle(/^Chores/);

    await expect(tabs).toHaveAttribute('data-original', 'yes');
    await expect(page.locator('header')).toHaveAttribute('data-original', 'yes');
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);

    // Back returns to where you were.
    await page.goBack();
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(500);
});
