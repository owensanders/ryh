import { expect, test, type Page } from '@playwright/test';
import { logIn } from './support/auth';
import { ownerOfNewHousehold } from './support/households';
import { emailHtmlUrl, waitForEmail } from './support/mailpit';
import { registerNewUser, verifyEmail } from './support/register';
import { storageState, uniqueName } from './support/users';

/**
 * Opens the email as a mail client would show it, checks it carries the RYH
 * branding (the logo and a coral button), and returns where its button goes —
 * so each journey continues by following the button, the way a person would.
 */
async function openBrandedEmail(page: Page, address: string, subject: RegExp, heading: string, button: string): Promise<string> {
    await page.goto(emailHtmlUrl(await waitForEmail(address, subject)));

    // The light-mode logo; mail apps in dark mode swap in img.logo-dark.
    const logo = page.locator('img.logo-light');
    await expect(logo).toBeVisible();
    await expect(logo).toHaveAttribute('alt', 'Run Your Home');
    expect(await logo.evaluate((img: HTMLImageElement) => img.naturalWidth)).toBeGreaterThan(0);

    await expect(page.getByRole('heading', { name: heading })).toBeVisible();

    const action = page.getByRole('link', { name: button });
    await expect(action).toHaveCSS('background-color', 'rgb(255, 79, 64)');

    return (await action.getAttribute('href')) ?? '';
}

test('a forgotten password is reset from the emailed link', async ({ page, browser }) => {
    const email = await registerNewUser(page, 'Fran', 'Forgetful');

    // Signed out, they ask for a reset link.
    const guest = await browser.newPage();
    await guest.goto('/forgot-password');
    await guest.getByLabel('Email address').fill(email);
    await guest.getByRole('button', { name: 'Email password reset link' }).click();
    await expect(guest.getByText('A reset link will be sent if the account exists.')).toBeVisible();

    const resetUrl = await openBrandedEmail(guest, email, /Reset Password/, 'Hello!', 'Reset Password');

    await guest.goto(resetUrl);
    await guest.getByLabel('Password', { exact: true }).fill('Brand-New-Pass-77!');
    await guest.getByLabel('Confirm password').fill('Brand-New-Pass-77!');
    await guest.getByRole('button', { name: 'Reset password' }).click();
    await expect(guest).toHaveURL(/\/login$/);

    // The new password signs them in (still unverified, so they're held at the prompt).
    await logIn(guest, email, 'Brand-New-Pass-77!');
    await expect(guest).toHaveURL(/\/verify-email$/);
});

test('the owner invites someone new by email, and they sign up from the link into the household', async ({ browser }) => {
    const owner = await browser.newPage({ storageState: storageState('owner') });
    const email = `${uniqueName('aunt').replace(/\s/g, '-')}@example.test`;

    await owner.goto('/house');
    await owner.getByLabel('Email', { exact: true }).fill(email);
    await owner.getByRole('button', { name: 'Send invite' }).click();
    await expect(owner.getByLabel('Email', { exact: true })).toHaveValue('');

    const invitee = await browser.newPage();
    const registerUrl = await openBrandedEmail(invitee, email, /Join The Parkers/, 'Join The Parkers on RYH', 'Create your account');

    await invitee.goto(registerUrl);
    await expect(invitee.getByLabel('Email address')).toHaveValue(email);
    await invitee.getByLabel('First name').fill('Aunt');
    await invitee.getByLabel('Last name').fill('Parker');
    await invitee.getByLabel('Password', { exact: true }).fill('Correct-Horse-42!');
    await invitee.getByRole('checkbox', { name: /terms of service/i }).click();
    await invitee.getByRole('button', { name: 'Continue' }).click();

    await verifyEmail(invitee, email);

    await expect(invitee).toHaveURL(/\/dashboard/);
    await expect(invitee.getByText(/Good (morning|afternoon|evening), Aunt/)).toBeVisible();
});

test('the owner invites someone who already has an account, and they accept from the email', async ({ browser }) => {
    const invitee = await browser.newPage();
    const email = await registerNewUser(invitee, 'Cousin', 'Parker');

    const owner = await browser.newPage({ storageState: storageState('owner') });
    await owner.goto('/house');
    await owner.getByLabel('Email', { exact: true }).fill(email);
    await owner.getByRole('button', { name: 'Send invite' }).click();
    await expect(owner.getByLabel('Email', { exact: true })).toHaveValue('');

    const acceptUrl = await openBrandedEmail(invitee, email, /invited to join The Parkers/, "You're invited to join The Parkers", 'Accept invite');

    await invitee.goto(acceptUrl);
    await expect(invitee).toHaveURL(/\/household\/select$/);
    await expect(invitee.getByRole('status')).toHaveText(/You're in The Parkers/);
    await expect(invitee.getByText('Here is your household')).toBeVisible();
    await expect(invitee.getByRole('heading', { name: "You're all set" })).toBeVisible();
    await expect(invitee.getByRole('button', { name: 'Open The Parkers' })).toBeVisible();
});

// A fresh household, so the new member doesn't crowd the shared seeded Parkers.
test('the owner invites a child by email after confirming they are their parent or guardian', async ({ browser }) => {
    const owner = await browser.newPage();
    const { householdName } = await ownerOfNewHousehold(owner, 'Gwen', 'Guardian');
    const email = `${uniqueName('kid').replace(/\s/g, '-')}@example.test`;

    await owner.getByLabel('Email', { exact: true }).fill(email);
    await owner.getByLabel('Role', { exact: true }).selectOption({ label: 'Child' });

    // A Child can't be invited until the owner vouches for being their parent or guardian.
    const send = owner.getByRole('button', { name: 'Send invite' });
    await expect(send).toBeDisabled();
    await owner.getByRole('checkbox', { name: /parent or guardian/ }).click();
    await send.click();
    await expect(owner.getByLabel('Email', { exact: true })).toHaveValue('');

    const invitee = await browser.newPage();
    const registerUrl = await openBrandedEmail(invitee, email, /Join /, `Join ${householdName} on RYH`, 'Create your account');

    await invitee.goto(registerUrl);
    // Invited members aren't asked to say they're 18 or over.
    await expect(invitee.getByText('the parent or guardian who invited me has agreed to them for me')).toBeVisible();
    await expect(invitee.getByText("I'm 18 or over")).toHaveCount(0);
    await invitee.getByLabel('First name').fill('Kid');
    await invitee.getByLabel('Last name').fill('Parker');
    await invitee.getByLabel('Password', { exact: true }).fill('Correct-Horse-42!');
    await invitee.getByRole('checkbox', { name: /terms of service/i }).click();
    await invitee.getByRole('button', { name: 'Continue' }).click();

    await verifyEmail(invitee, email);

    await expect(invitee).toHaveURL(/\/dashboard/);
    // A Child gets the simplified view.
    await expect(invitee.getByRole('link', { name: 'Budget', exact: true })).toHaveCount(0);
});
