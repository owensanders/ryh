import { expect, test } from './support/fixtures';
import { uniqueName } from './support/users';

test('the owner adds a chore for James, ticks it off, and the dashboard counts it', async ({ page, household }) => {
    const chore = uniqueName('Hoover the stairs');

    await page.goto('/dashboard');
    const progress = page.getByRole('region', { name: "Today's chores" }).getByText(/\d+ of \d+ done today/);
    const [doneBefore, totalBefore] = (await progress.innerText()).match(/\d+/g)!.map(Number);

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(chore);
    await page.getByLabel('Assign new chore to').selectOption({ label: household.members.adult.name });
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

test('the owner renames a chore, drags it to Completed, then deletes it', async ({ page, household }) => {
    const chore = uniqueName('Clean the car');
    const dialog = page.getByRole('dialog');

    await page.goto('/chores');
    await page.getByLabel('New chore', { exact: true }).fill(chore);
    await page.getByRole('button', { name: 'Add chore' }).click();
    await expect(page.getByRole('button', { name: `Mark ${chore} as done` })).toBeVisible();

    await page.getByText(chore, { exact: true }).click();
    await expect(dialog.getByRole('heading', { name: 'Edit chore' })).toBeVisible();
    await dialog.getByLabel('Name').fill(`${chore} inside and out`);
    await dialog.getByLabel('Assignee').selectOption({ label: household.members.adult.name });
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(dialog).toBeHidden();

    const renamed = `${chore} inside and out`;
    // Drop low in the Completed column, level with the card: the columns are the same
    // height, and dragging up to its heading would scroll mid-drag, which cancels it.
    const completed = page.getByText('Completed', { exact: true }).locator('xpath=../..');
    const box = (await completed.boundingBox())!;
    await page.getByText(renamed, { exact: true }).dragTo(completed, { targetPosition: { x: box.width / 2, y: box.height - 40 } });
    await expect(page.getByRole('button', { name: `Mark ${renamed} as not done` })).toBeVisible();

    await page.getByRole('button', { name: `Mark ${renamed} as not done` }).locator('..').getByTitle('Delete chore').click();
    await page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(renamed, { exact: true })).toHaveCount(0);
});
