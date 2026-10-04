import { expect, test } from '@playwright/test';
import { storageState, uniqueName } from './support/users';

test.use({ storageState: storageState('owner') });

test('the owner adds an item, ticks it off, then starts a new list', async ({ page }) => {
    const item = uniqueName('Oat milk');
    const list = uniqueName('Corner shop');

    await page.goto('/shopping');
    await page.getByLabel('Add to this list').fill(item);
    await page.getByLabel('Quantity').fill('x2');
    await page.getByRole('button', { name: 'Add', exact: true }).click();

    const row = page.getByRole('checkbox', { name: new RegExp(item) });
    await expect(row).toHaveAttribute('aria-checked', 'false');
    await row.click();
    await expect(row).toHaveAttribute('aria-checked', 'true');

    await page.reload();
    await expect(row).toHaveAttribute('aria-checked', 'true');

    await page.getByRole('button', { name: 'New list' }).click();
    await page.getByRole('dialog').getByLabel('Name').fill(list);
    await page.getByRole('button', { name: 'Add list' }).click();

    await page.getByRole('link', { name: new RegExp(list) }).click();
    await expect(page.getByRole('heading', { name: list })).toBeVisible();
    await expect(page.getByText('This list is empty.')).toBeVisible();
});
