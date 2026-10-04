import { expect, test } from '@playwright/test';
import { storageState, uniqueName, users } from './support/users';

test.use({ storageState: storageState('owner') });

test('the owner adds a chore for James, ticks it off, and the dashboard counts it', async ({ page }) => {
    const chore = uniqueName('Hoover the stairs');

    await page.goto('/dashboard');
    const progress = page.getByRole('region', { name: "Today's chores" }).getByText(/\d+ of \d+ done today/);
    const [doneBefore, totalBefore] = (await progress.innerText()).match(/\d+/g)!.map(Number);

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(chore);
    await page.getByLabel('Assign new chore to').selectOption({ label: users.adult.name });
    await page.getByRole('button', { name: 'Add chore' }).click();

    await page.getByRole('button', { name: `Mark ${chore} as done` }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as not done` })).toBeVisible();

    await page.goto('/dashboard');
    await expect(progress).toContainText(`${doneBefore + 1} of ${totalBefore + 1} done today`);
});
