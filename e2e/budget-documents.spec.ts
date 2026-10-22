import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { storageState, uniqueName } from './support/users';

test.use({ storageState: storageState('owner') });

test('the owner adds a budget category', async ({ page }) => {
    const category = uniqueName('Pets');

    await page.goto('/budget');
    await page.getByLabel('New category', { exact: true }).fill(category);
    await page.getByLabel('New category budget').fill('45');
    await page.getByRole('button', { name: 'Add', exact: true }).click();

    await expect(page.getByText(category).first()).toBeVisible();
    await page.reload();
    await expect(page.getByText(category).first()).toBeVisible();
});

test('the owner adds, edits and removes an income source', async ({ page }) => {
    const source = uniqueName('Side job');
    const dialog = page.getByRole('dialog');

    await page.goto('/budget');
    await page.getByRole('button', { name: '+ Add income source' }).click();
    await dialog.getByLabel('Name').fill(source);
    await dialog.getByLabel('Amount').fill('250');
    await dialog.getByRole('button', { name: 'Add income source' }).click();
    await expect(dialog).toBeHidden();

    await page.getByRole('button', { name: new RegExp(source) }).click();
    await expect(dialog.getByRole('heading', { name: 'Edit income source' })).toBeVisible();
    await dialog.getByLabel('Amount').fill('300');
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('button', { name: new RegExp(source) })).toContainText('£300');

    await page.getByRole('button', { name: new RegExp(source) }).click();
    await dialog.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText('Remove this income source?')).toBeVisible();
    await page.getByRole('dialog').filter({ hasText: 'Are you sure?' }).getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByRole('button', { name: new RegExp(source) })).toHaveCount(0);
});

test('the owner renames a budget category in place, then removes it', async ({ page }) => {
    const category = uniqueName('Hobbies');

    await page.goto('/budget');
    await page.getByLabel('New category', { exact: true }).fill(category);
    await page.getByLabel('New category budget').fill('30');
    await page.getByRole('button', { name: 'Add', exact: true }).click();

    // The row: the innermost element holding both the name and its edit button.
    const row = page.locator('div').filter({ has: page.getByText(category, { exact: true }) }).filter({ has: page.getByTitle('Edit category') }).last();
    await row.getByTitle('Edit category').click();

    // In edit mode the name becomes an input, so find the row by its Done button.
    const editing = page.locator('div').filter({ has: page.getByTitle('Done') }).filter({ has: page.getByRole('textbox') }).last();
    await editing.getByRole('textbox').first().fill(`${category} renamed`);
    await editing.getByTitle('Done').click();
    await expect(page.getByText(`${category} renamed`, { exact: true })).toBeVisible();

    const renamed = page.locator('div').filter({ has: page.getByText(`${category} renamed`, { exact: true }) }).filter({ has: page.getByTitle('Delete category') }).last();
    await renamed.getByTitle('Delete category').click();
    await page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(`${category} renamed`, { exact: true })).toHaveCount(0);
});

test('the owner makes a folder, uploads a document into it and downloads it again', async ({ page }) => {
    const folder = uniqueName('Receipts');

    await page.goto('/documents');
    await page.getByLabel('New folder').fill(folder);
    await page.getByRole('button', { name: 'Add folder' }).click();
    await page.getByRole('link', { name: new RegExp(folder) }).click();
    // Wait for the folder to open: uploading straight away aborts that visit
    // (Inertia runs one at a time) and the file lands in the previous folder.
    await expect(page).toHaveURL(/\/documents\/\d+$/);

    const filename = `${uniqueName('receipt').replace(/\s/g, '-')}.pdf`;
    await page.getByLabel('Upload files').setInputFiles({
        name: filename,
        mimeType: 'application/pdf',
        buffer: readFileSync('e2e/fixtures/receipt.pdf'),
    });

    const download = page.getByRole('link', { name: new RegExp(`^Download ${filename.replace('.pdf', '')}`) });
    await expect(download).toBeVisible();

    const [file] = await Promise.all([page.waitForEvent('download'), download.click()]);
    expect(file.suggestedFilename()).toContain(filename.replace('.pdf', ''));
});
