import { addChoreAsOwner } from './support/chores';
import { expect, test } from './support/fixtures';
import { uniqueName } from './support/users';

const sidebarLink = (name: string) => ({ name, exact: true });

test.describe('a child', () => {
    test.use({ role: 'child' });

    test("can't reach money, documents or house management", async ({ page }) => {
        await page.goto('/dashboard');

        for (const link of ['Budget', 'Documents', 'House']) {
            await expect(page.getByRole('link', sidebarLink(link))).toHaveCount(0);
        }

        for (const path of ['/budget', '/documents', '/house']) {
            expect((await page.goto(path))?.status(), path).toBe(403);
        }
    });

    test('ticks off their own chore but not anyone else’s', async ({ page, pageAs, household }) => {
        const mine = uniqueName('Feed the cat');
        const theirs = uniqueName('Mow the lawn');
        await addChoreAsOwner(pageAs, mine, household.members.child.name);
        await addChoreAsOwner(pageAs, theirs, household.members.adult.name);

        await page.goto('/chores');
        await expect(page.getByLabel('New chore', { exact: true })).toHaveCount(0);
        await expect(page.getByRole('button', { name: `Mark ${theirs} as done` })).toBeDisabled();

        await page.getByRole('button', { name: `Mark ${mine} as done` }).click();
        await expect(page.getByRole('button', { name: `Mark ${mine} as not done` })).toBeVisible();
    });
});

test.describe('a teen', () => {
    test.use({ role: 'teen' });

    test('can add calendar events but not chores', async ({ page }) => {
        const event = uniqueName('Swim gala');

        await page.goto('/calendar');
        await page.getByRole('button', { name: 'New event' }).click();
        await page.getByRole('dialog').getByLabel('Title').fill(event);
        await page.getByRole('dialog').getByRole('button', { name: 'Add event' }).click();
        await expect(page.getByText(event).filter({ visible: true }).first()).toBeVisible();

        await page.goto('/chores');
        await expect(page.getByRole('button', { name: 'Add chore' })).toHaveCount(0);
    });
});

test.describe('an adult', () => {
    test.use({ role: 'adult' });

    test('manages the budget but not the house', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page.getByRole('link', sidebarLink('Budget'))).toBeVisible();
        await expect(page.getByRole('link', sidebarLink('House'))).toHaveCount(0);

        expect((await page.goto('/house'))?.status()).toBe(403);
    });
});
