import { expect, test } from '@playwright/test';
import { addMembers, createAccount, ownerOfNewHousehold } from './support/households';
import { linkFromLatestEmail } from './support/mailpit';
import { registerNewUser } from './support/register';
import { uniqueName } from './support/users';

/** A household holds up to 8 people — pending invites and join requests included. */

test('a household stops taking invites at 8 people, and cancelling one makes room again', async ({ page }) => {
    const { email } = await ownerOfNewHousehold(page, 'Fay', 'Fuller');

    addMembers(email, 6);
    await page.reload();
    await expect(page.getByTestId('member-count')).toHaveText('7 of 8 members');

    // The eighth place goes to a pending invite.
    await page.getByLabel('Email', { exact: true }).fill(createAccount('Paula Pending'));
    await page.getByRole('button', { name: 'Send invite' }).click();

    await expect(page.getByTestId('member-count')).toHaveText('8 of 8 members');
    await expect(page.getByText('Your household is full')).toBeVisible();
    await expect(page.getByLabel('Email', { exact: true })).toHaveCount(0);

    // Cancelling that invite frees the place.
    await page.getByRole('button', { name: 'Remove Paula Pending from household' }).click();

    await expect(page.getByTestId('member-count')).toHaveText('7 of 8 members');
    await expect(page.getByLabel('Email', { exact: true })).toBeVisible();
});

test('the setup wizard takes up to 7 invites, since the owner is the eighth', async ({ page }) => {
    await registerNewUser(page, 'Wes', 'Wizard');

    const email = page.getByPlaceholder('their@email.co.uk');
    for (let i = 1; i <= 7; i++) {
        await email.fill(`guest-${i}@example.test`);
        await page.getByRole('button', { name: 'Add', exact: true }).click();
    }

    await expect(page.getByText("That's everyone — a household holds 8 people, including you.")).toBeVisible();
    await expect(email).toHaveCount(0);

    await page.getByRole('button', { name: '✕' }).first().click();
    await expect(page.getByPlaceholder('their@email.co.uk')).toBeVisible();
});

test('a full household turns away someone with its join code', async ({ page, browser }) => {
    const { email } = await ownerOfNewHousehold(page, 'Cody', 'Coder');

    const inviteCode = page.getByRole('switch', { name: 'Invite code' });
    // A household's invite code starts switched on.
    await expect(inviteCode).toHaveAttribute('aria-checked', 'true');
    const code = (await page.getByTestId('join-code').innerText()).trim();

    addMembers(email, 7);

    const joiner = await browser.newPage();
    await registerNewUser(joiner, 'Late', 'Comer');
    await joiner.getByRole('button', { name: /Join with a code/ }).click();
    await joiner.getByLabel('Invite code').fill(code);
    await joiner.getByRole('button', { name: 'Request to join' }).click();

    await expect(joiner.getByText("That household is full, so it can't take any more members.")).toBeVisible();
    await expect(joiner).toHaveURL(/\/household\/setup$/);
});

test('an invite link to a household that has since filled up explains why they were not added', async ({ page, browser }) => {
    const { email: ownerEmail, householdName } = await ownerOfNewHousehold(page, 'Ivy', 'Inviter');

    const inviteeEmail = `${uniqueName('invitee').replace(/\s/g, '-')}@example.test`;
    await page.getByLabel('Email', { exact: true }).fill(inviteeEmail);
    await page.getByRole('button', { name: 'Send invite' }).click();
    await expect(page.getByLabel('Email', { exact: true })).toHaveValue('');

    // Invites to people without an account don't hold a place, so it fills up meanwhile.
    addMembers(ownerEmail, 7);

    const invitee = await browser.newPage();
    await invitee.goto(await linkFromLatestEmail(inviteeEmail, '/register'));
    await invitee.getByLabel('First name').fill('Too');
    await invitee.getByLabel('Last name').fill('Late');
    await invitee.getByLabel('Password', { exact: true }).fill('Correct-Horse-42!');
    await invitee.getByRole('checkbox', { name: /terms of service/i }).click();
    await invitee.getByRole('button', { name: 'Continue' }).click();

    await expect(invitee).toHaveURL(/\/household\/setup$/);
    await expect(invitee.getByRole('alert')).toContainText(`${householdName} is full.`);
});
