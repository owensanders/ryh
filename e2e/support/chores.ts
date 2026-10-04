import { expect, type Browser } from '@playwright/test';
import { storageState } from './users';

/** Adds a Today chore as the owner, assigned to the member named `assignee`. */
export async function addChoreAsOwner(browser: Browser, name: string, assignee: string): Promise<void> {
    const page = await browser.newPage({ storageState: storageState('owner') });

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(name);
    await page.getByLabel('Assign new chore to').selectOption({ label: assignee });
    await page.getByRole('button', { name: 'Add chore' }).click();
    await expect(page.getByRole('button', { name: `Mark ${name} as done` })).toBeVisible();

    await page.close();
}
