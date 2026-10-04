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
