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
