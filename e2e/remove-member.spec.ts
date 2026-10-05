import { expect, test, type Page } from '@playwright/test';
import { logInAndLand } from './support/auth';
import { registerNewUser, verifyEmail } from './support/register';
import { uniqueName, users } from './support/users';

/** Signs a brand-new, verified user up as owner of their own household, leaving them on the House page. */
async function ownerOfNewHousehold(page: Page, firstName: string, lastName: string): Promise<void> {
    const email = await registerNewUser(page, firstName, lastName);

    await page.getByLabel('Household name').fill(uniqueName(`The ${lastName}s`));
    await page.getByRole('button', { name: 'Create household' }).click();
    await expect(page.getByRole('heading', { name: 'Your household is live' })).toBeVisible();

    await verifyEmail(page, email);
    await page.goto('/house');
}

test("cancelling an invite to someone else's account leaves their account and household alone", async ({ page, browser }) => {
    // A stranger invites James, who already belongs to the Parkers, then cancels.
    await ownerOfNewHousehold(page, 'Stan', 'Stranger');
    await page.getByLabel('Email', { exact: true }).fill(users.adult.email);
    await page.getByRole('button', { name: 'Send invite' }).click();

    await page.getByRole('button', { name: `Remove ${users.adult.name} from household` }).click();
    await expect(page.getByRole('status')).toHaveText(new RegExp(`${users.adult.name} — invite cancelled`));
    await expect(page.getByText(users.adult.email)).toHaveCount(0);

    // James can still sign in, straight into the Parkers.
    const james = await browser.newPage();
    await logInAndLand(james, users.adult.email);
    await expect(james.getByText(/Good (morning|afternoon|evening), James/)).toBeVisible();

    await james.close();
});

test('a removed member keeps their account and is sent to set up a household of their own', async ({ page, browser }) => {
    await ownerOfNewHousehold(page, 'Rhea', 'Remover');

    const inviteCode = page.getByRole('switch', { name: 'Invite code' });
    if ((await inviteCode.getAttribute('aria-checked')) !== 'true') {
        await inviteCode.click();
    }
    await expect(inviteCode).toHaveAttribute('aria-checked', 'true');
    const code = (await page.getByTestId('join-code').innerText()).trim();

    // Someone joins with the code and is approved.
    const member = await browser.newPage();
    const surname = uniqueName('Lodger').replace(/\s/g, '-');
    const email = await registerNewUser(member, 'Lou', surname);
    await member.getByRole('button', { name: /Join with a code/ }).click();
    await member.getByLabel('Invite code').fill(code);
    await member.getByRole('button', { name: 'Request to join' }).click();
    await expect(member.getByRole('heading', { name: 'Request sent' })).toBeVisible();
    await verifyEmail(member, email);

    await page.reload();
    await page.getByRole('button', { name: `Approve Lou ${surname}` }).click();
    await expect(page.getByRole('button', { name: `Approve Lou ${surname}` })).toHaveCount(0);

    await member.goto('/dashboard');
    await expect(member.getByText(/Good (morning|afternoon|evening), Lou/)).toBeVisible();

    // The owner removes them.
    await page.getByRole('button', { name: `Remove Lou ${surname} from household` }).click();
    await expect(page.getByRole('status')).toHaveText(new RegExp(`Lou ${surname} removed from the household`));

    // Their session still works, but the household is gone from it.
    await member.goto('/dashboard');
    await expect(member).toHaveURL(/\/household\/setup$/);

    await member.close();
});
