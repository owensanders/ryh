import { expect, test } from '@playwright/test';

// The landing page's "On your phone" section: what the installed app offers,
// and step-by-step install help for each kind of phone.
test('the landing page explains the phone app and how to install it', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('banner').getByRole('link', { name: 'App', exact: true }).click();
    await expect(page).toHaveURL(/\/#phone$/);

    const section = page.locator('section#phone');
    await expect(section.getByRole('heading', { name: 'RYH lives on your home screen' })).toBeVisible();

    // A desktop visitor starts on iPhone & iPad; Android is a tab away.
    await expect(section.getByRole('tab', { name: 'iPhone & iPad' })).toHaveAttribute('aria-selected', 'true');
    await expect(section.getByRole('tabpanel')).toContainText('Add to Home Screen');
    await expect(section.getByRole('tabpanel')).toContainText('Settings → Notifications');

    await section.getByRole('tab', { name: 'Android' }).click();
    await expect(section.getByRole('tabpanel')).toContainText('Install app');
});

test.describe('on an Android phone', () => {
    test.use({
        userAgent:
            'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
    });

    test('the install guide opens on the Android steps', async ({ page }) => {
        await page.goto('/#phone');
        await expect(page.locator('section#phone').getByRole('tab', { name: 'Android' })).toHaveAttribute('aria-selected', 'true');
    });
});

// The header's section links land each section just below the sticky header,
// whether clicked on the landing page or from another page (a fresh load,
// where Inertia restores the scroll position after the page renders).
test('the header links bring each section into view just below the header, from any page', async ({ page }) => {
    const headingGap = (name: string) =>
        page.evaluate((name) => {
            const heading = [...document.querySelectorAll('h2')].find((h) => h.textContent?.trim() === name);
            const header = document.querySelector('header');
            return heading && header ? heading.getBoundingClientRect().top - header.getBoundingClientRect().bottom : null;
        }, name);

    for (const start of ['/', '/terms']) {
        for (const [link, heading] of [
            ['Daily rhythm', 'One glance, three answers'],
            ['Pricing', 'One price, everything included'],
        ]) {
            await page.goto(start);
            await page.getByRole('banner').getByRole('link', { name: link, exact: true }).click();
            await expect.poll(() => headingGap(heading), { message: `${link} from ${start}` }).toBeGreaterThanOrEqual(0);
            expect(await headingGap(heading), `${link} from ${start}`).toBeLessThan(60);
        }
    }
});
