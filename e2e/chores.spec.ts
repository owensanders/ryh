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

    // The tick shows before the server answers, so wait for it to save before leaving.
    const saved = page.waitForResponse((response) => response.url().includes('/toggle') && response.request().method() === 'PATCH');
    await page.getByRole('button', { name: `Mark ${chore} as done` }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as not done` })).toBeVisible();
    await saved;

    await page.goto('/dashboard');
    await expect(progress).toContainText(`${doneBefore + 1} of ${totalBefore + 1} done today`);
});

test('a tick shows before the server answers, and the board reloads only what changed', async ({ page }) => {
    const chore = uniqueName('Water the plants');

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(chore);
    await page.getByRole('button', { name: 'Add chore' }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as done` })).toBeVisible();

    // Hold the request until the tick has been checked.
    let release!: () => void;
    const held = new Promise<void>((resolve) => (release = resolve));
    let partialData: string | undefined;
    await page.route('**/chores/*/toggle', async (route) => {
        partialData = route.request().headers()['x-inertia-partial-data'];
        await held;
        await route.continue();
    });

    await page.getByRole('button', { name: `Mark ${chore} as done` }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as not done` })).toBeVisible();
    expect(partialData).toBe('columns,scores');

    release();
    await page.waitForResponse((response) => response.url().endsWith('/chores') && response.request().method() === 'GET');
    await expect(page.getByRole('button', { name: `Mark ${chore} as not done` })).toBeVisible();
});

test("a tick that doesn't save is undone, with a message", async ({ page }) => {
    const chore = uniqueName('Empty the bins');

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(chore);
    await page.getByRole('button', { name: 'Add chore' }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as done` })).toBeVisible();

    await page.route('**/chores/*/toggle', (route) => route.abort('connectionfailed'));

    await page.getByRole('button', { name: `Mark ${chore} as done` }).click();

    await expect(page.getByRole('alert')).toContainText("That didn't save");
    await expect(page.getByRole('button', { name: `Mark ${chore} as done` })).toBeVisible();
});
