import { expect, test } from '@playwright/test';

// The landing page's "On your phone" section: what the installed app offers,
// and step-by-step install help for each kind of phone.
test('the landing page explains the phone app and how to install it', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('banner').getByRole('link', { name: 'App', exact: true }).click();
    await expect(page).toHaveURL(/\/#phone$/);

    const section = page.locator('section#phone');
    await expect(section.getByRole('heading', { name: 'RYH lives on your home screen' })).toBeVisible();

    // A desktop visitor starts on Android; iPhone is a tab away.
    await expect(section.getByRole('tab', { name: 'Android' })).toHaveAttribute('aria-selected', 'true');
    await expect(section.getByRole('tabpanel')).toContainText('Install app');

    await section.getByRole('tab', { name: 'iPhone & iPad' }).click();
    await expect(section.getByRole('tabpanel')).toContainText('Add to Home Screen');
    await expect(section.getByRole('tabpanel')).toContainText('Settings → Notifications');
});

test.describe('on an iPhone', () => {
    test.use({
        userAgent:
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    });

    test('the install guide opens on the iPhone steps', async ({ page }) => {
        await page.goto('/#phone');
        await expect(page.locator('section#phone').getByRole('tab', { name: 'iPhone & iPad' })).toHaveAttribute('aria-selected', 'true');
    });
});
