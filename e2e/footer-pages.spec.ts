import { expect, test } from '@playwright/test';

// The landing footer's Privacy, Terms, Support and Status pages, as a visitor meets them.
test('the landing footer reaches every public page', async ({ page }) => {
    const pages = [
        { link: 'Privacy', url: /\/privacy$/, heading: 'Privacy Policy' },
        { link: 'Terms', url: /\/terms$/, heading: 'Terms of Service' },
        { link: 'Cookies', url: /\/cookies$/, heading: 'Cookie Policy' },
        { link: 'Support', url: /\/support$/, heading: 'How can we help?' },
        { link: 'Status', url: /\/status$/, heading: 'Platform status' },
    ];

    for (const { link, url, heading } of pages) {
        await page.goto('/');
        await page.locator('footer').getByRole('link', { name: link }).click();
        await expect(page).toHaveURL(url);
        await expect(page.getByRole('heading', { level: 1, name: heading })).toBeVisible();
    }
});

test('the status page lists each part of the platform', async ({ page }) => {
    await page.goto('/status');

    await expect(page.getByRole('status')).toBeVisible();
    for (const service of ['Website & app', 'Household data', 'Emails & background jobs', 'Live notifications', 'AI meal plans']) {
        await expect(page.getByText(service, { exact: true })).toBeVisible();
    }
});

test('the legal contents jump to their section', async ({ page }) => {
    await page.goto('/privacy');

    await page.getByRole('navigation', { name: 'Contents' }).getByRole('link', { name: 'Your rights' }).click();
    await expect(page).toHaveURL(/#your-rights$/);
    await expect(page.locator('#your-rights')).toBeInViewport();
});

test('registration links to the terms and privacy policy without ticking the box', async ({ page, context }) => {
    await page.goto('/register');
    const terms = page.getByRole('checkbox');

    const [termsTab] = await Promise.all([context.waitForEvent('page'), page.getByRole('link', { name: 'Terms of Service' }).click()]);
    await expect(termsTab.getByRole('heading', { level: 1, name: 'Terms of Service' })).toBeVisible();
    await termsTab.close();

    await expect(terms).toHaveAttribute('aria-checked', 'false');
    // The start of the label, clear of the links inside it.
    await page.getByText("I'm 18 or over").click({ position: { x: 4, y: 8 } });
    await expect(terms).toHaveAttribute('aria-checked', 'true');
});
