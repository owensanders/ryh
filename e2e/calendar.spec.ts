import { expect, test } from '@playwright/test';
import { storageState, uniqueName } from './support/users';

test.use({ storageState: storageState('owner') });

test("an event added for today shows under Today in the dashboard's Coming up card", async ({ page }) => {
    const event = uniqueName('Dentist');

    await page.goto('/calendar');
    await page.getByRole('button', { name: 'New event' }).click();

    // A new event defaults to 09:00 today.
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Title').fill(event);
    await dialog.getByLabel('Location').fill('High Street');
    await dialog.getByRole('button', { name: 'Add event' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByText(event).filter({ visible: true }).first()).toBeVisible();

    await page.goto('/dashboard');
    const comingUp = page.getByRole('region', { name: 'Coming up' });
    await expect(comingUp.getByText('Today', { exact: true })).toBeVisible();
    await expect(comingUp.getByText(event)).toBeVisible();
});

test("a busy day's '+N more' opens the editor, and an event can be renamed and deleted", async ({ page }) => {
    const prefix = uniqueName('Practice');
    const dialog = page.getByRole('dialog');

    await page.goto('/calendar');

    // Four events today is more than a month cell shows.
    for (const n of [1, 2, 3, 4]) {
        await page.getByRole('button', { name: 'New event' }).click();
        await dialog.getByLabel('Title').fill(`${prefix} ${n}`);
        await dialog.getByRole('button', { name: 'Add event' }).click();
        await expect(dialog).toBeHidden();
    }

    // The month grid labels days by date in UTC (the app's timezone).
    const today = page.locator('div.group').filter({ has: page.getByRole('button', { name: `Add an event on ${new Date().getUTCDate()}`, exact: true }) });
    await today.getByRole('button', { name: /^\+\d+ more$/ }).click();
    await dialog.getByRole('button', { name: new RegExp(`${prefix} 4`) }).click();

    await expect(dialog.getByRole('heading', { name: 'Edit event' })).toBeVisible();
    await dialog.getByLabel('Title').fill(`${prefix} renamed`);
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(dialog).toBeHidden();

    await today.getByRole('button', { name: /^\+\d+ more$/ }).click();
    await dialog.getByRole('button', { name: new RegExp(`${prefix} renamed`) }).click();
    await dialog.getByRole('button', { name: 'Delete' }).click();
    await expect(dialog).toBeHidden();

    await today.getByRole('button', { name: /^\+\d+ more$/ }).click();
    await expect(dialog.getByText(`${prefix} 3`)).toBeVisible();
    await expect(dialog.getByText(`${prefix} renamed`)).toHaveCount(0);
});
