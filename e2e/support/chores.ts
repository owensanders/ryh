import { expect, type Page } from '@playwright/test';
import type { Role } from './fixtures';

/** Adds a Today chore as the owner, assigned to the member named `assignee`. */
export async function addChoreAsOwner(pageAs: (role: Role) => Promise<Page>, name: string, assignee: string): Promise<void> {
    const page = await pageAs('owner');

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(name);
    await page.getByLabel('Assign new chore to').selectOption({ label: assignee });
    await page.getByRole('button', { name: 'Add chore' }).click();
    await expect(page.getByRole('button', { name: `Mark ${name} as done` })).toBeVisible();
}
