import type { Page } from '@playwright/test';
import { addChoreAsOwner } from './support/chores';
import { expect, test } from './support/fixtures';
import { uniqueName } from './support/users';

const bell = (page: Page) => page.getByRole('button', { name: /^Notifications/ });

async function unreadCount(page: Page): Promise<number> {
    const label = (await bell(page).getAttribute('aria-label')) ?? '';

    return Number(label.match(/(\d+) unread/)?.[1] ?? 0);
}

test('assigning a chore notifies the assignee, and opening it marks it read', async ({ pageAs, household }) => {
    const chore = uniqueName('Take the bins out');

    await addChoreAsOwner(pageAs, chore, household.members.adult.name);

    const james = await pageAs('adult');
    // Simulate a slow network on the mark-read call. Locally the server is fast
    // enough to win the race against a navigation that aborts the request, which
    // hid the original bug; over a real connection it didn't.
    await james.route('**/notifications/*/read', async (route) => {
        await new Promise((resolve) => setTimeout(resolve, 500));
        await route.continue().catch(() => {});
    });
    await james.goto('/dashboard');

    const notification = james.getByRole('button', { name: `${household.members.owner.name} assigned you "${chore}"` });
    await bell(james).click();
    await expect(notification).toBeVisible();
    const unreadBefore = await unreadCount(james);
    expect(unreadBefore).toBeGreaterThan(0);

    await notification.click();
    await expect(james).toHaveURL(/\/chores$/);

    // The read has to survive a reload, not just clear the dot locally (PR #9).
    await james.reload();
    await expect.poll(() => unreadCount(james)).toBe(unreadBefore - 1);
});

test('Settings → Notifications remembers which categories to push', async ({ pageAs }) => {
    const mia = await pageAs('teen');
    await mia.getByRole('link', { name: 'Settings' }).first().click();
    await mia.getByRole('link', { name: 'Notifications' }).click();
    await expect(mia).toHaveURL(/\/settings\/notifications$/);

    const meals = mia.getByRole('switch', { name: 'Meals' });
    await expect(meals).toHaveAttribute('aria-checked', 'true');

    await meals.click();
    await expect(mia.getByText('Notification settings saved')).toBeVisible();
    await mia.reload();
    await expect(meals).toHaveAttribute('aria-checked', 'false');
    await expect(mia.getByRole('switch', { name: 'Chores' })).toHaveAttribute('aria-checked', 'true');
});
