import { expect, test } from './support/fixtures';
import { uniqueName } from './support/users';

test('the owner adds an item, ticks it off, then starts a new list', async ({ page }) => {
    const item = uniqueName('Oat milk');
    const list = uniqueName('Corner shop');

    await page.goto('/shopping');
    await page.getByLabel('Add to this list').fill(item);
    await page.getByLabel('Quantity').fill('x2');
    await page.getByRole('button', { name: 'Add', exact: true }).click();

    const row = page.getByRole('checkbox', { name: new RegExp(item) });
    await expect(row).toHaveAttribute('aria-checked', 'false');
    // The tick shows before the server answers, so wait for it to save before reloading.
    const saved = page.waitForResponse((response) => response.url().includes('/toggle') && response.request().method() === 'PATCH');
    await row.click();
    await expect(row).toHaveAttribute('aria-checked', 'true');
    await saved;

    await page.reload();
    await expect(row).toHaveAttribute('aria-checked', 'true');

    await page.getByRole('button', { name: 'New list' }).click();
    await page.getByRole('dialog').getByLabel('Name').fill(list);
    await page.getByRole('button', { name: 'Add list' }).click();

    await page.getByRole('link', { name: new RegExp(list) }).click();
    await expect(page.getByRole('heading', { name: list })).toBeVisible();
    await expect(page.getByText('This list is empty.')).toBeVisible();
});

test('the owner renames a list, edits and deletes an item, then deletes the list', async ({ page }) => {
    const list = uniqueName('Hardware');
    const item = uniqueName('Screws');
    const dialog = page.getByRole('dialog');

    await page.goto('/shopping');
    await page.getByRole('button', { name: 'New list' }).click();
    await dialog.getByLabel('Name').fill(list);
    await dialog.getByRole('button', { name: 'Add list' }).click();
    await expect(dialog).toBeHidden();

    // Let the list's own visit land before opening a dialog on it, or the page swaps under the dialog.
    const opened = page.waitForResponse((response) => response.url().includes('/shopping/') && response.request().method() === 'GET');
    await page.getByRole('link', { name: new RegExp(list) }).click();
    await opened;
    await expect(page.getByRole('heading', { name: list })).toBeVisible();

    await page.getByRole('button', { name: 'Rename list' }).click();
    await dialog.getByLabel('Name').fill(`${list} renamed`);
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByRole('heading', { name: `${list} renamed` })).toBeVisible();

    await page.getByLabel('Add to this list').fill(item);
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    const row = page.getByRole('checkbox', { name: new RegExp(item) });
    await expect(row).toBeVisible();

    await page.getByRole('button', { name: 'Edit item' }).click();
    await dialog.getByLabel('Quantity').fill('x50');
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(dialog).toBeHidden();
    await expect(row).toContainText('x50');

    await page.getByRole('button', { name: 'Edit item' }).click();
    await dialog.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText('Delete this item?')).toBeVisible();
    await page.getByRole('dialog').filter({ hasText: 'Are you sure?' }).getByRole('button', { name: 'Delete' }).click();
    await expect(row).toHaveCount(0);

    await page.getByRole('button', { name: 'Delete list' }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByRole('link', { name: new RegExp(`${list} renamed`) })).toHaveCount(0);
});

test('an item ticked off on the dashboard is ticked off on its list', async ({ page }) => {
    await page.goto('/dashboard');
    const shopping = page.getByRole('region', { name: 'Shopping' });
    const listName = (await shopping.getByRole('heading').innerText()).replace(/ list$/, '');
    const first = shopping.getByRole('button').first();
    const item = (await first.innerText()).replace('✓', '').trim().split('\n')[0].trim();
    const wasDone = (await first.innerText()).includes('✓');

    const saved = page.waitForResponse((response) => response.url().includes('/toggle') && response.request().method() === 'PATCH');
    await first.click();
    await saved;

    await page.goto('/shopping');
    await page.getByRole('link', { name: new RegExp(`^${listName}`) }).click();
    await expect(page.getByRole('heading', { name: listName })).toBeVisible();
    const row = page.getByRole('checkbox', { name: new RegExp(item) }).first();
    await expect(row).toHaveAttribute('aria-checked', wasDone ? 'false' : 'true');
});
