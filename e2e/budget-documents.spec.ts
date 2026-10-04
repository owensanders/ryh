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

test('the owner makes a folder, uploads a document into it and downloads it again', async ({ page }) => {
    const folder = uniqueName('Receipts');

    await page.goto('/documents');
    await page.getByLabel('New folder').fill(folder);
    await page.getByRole('button', { name: 'Add folder' }).click();
    await page.getByRole('link', { name: new RegExp(folder) }).click();

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
