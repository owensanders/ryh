import { logInAndLand } from './support/auth';
import { expect, test } from './support/fixtures';
import { ownerOfNewHousehold } from './support/households';
import { registerNewUser, verifyEmail } from './support/register';
import { uniqueName } from './support/users';

// Each starts signed out, as the owner of a household made through sign-up.
test.use({ role: null });

test("cancelling an invite to someone else's account leaves their account and household alone", async ({ page, browser, household }) => {
    const james = household.members.adult;

    // A stranger invites James, who already belongs to the Parkers, then cancels.
    await ownerOfNewHousehold(page, 'Stan', 'Stranger');
    await page.getByLabel('Email', { exact: true }).fill(james.email);
    await page.getByRole('button', { name: 'Send invite' }).click();

    await page.getByRole('button', { name: `Remove ${james.name} from household` }).click();
    await expect(page.getByRole('status')).toHaveText(new RegExp(`${james.name} — invite cancelled`));
    await expect(page.getByText(james.email)).toHaveCount(0);

    // James can still sign in, straight into the Parkers.
    const jamesPage = await browser.newPage();
    await logInAndLand(jamesPage, james.email);
    await expect(jamesPage.getByText(/Good (morning|afternoon|evening), James/)).toBeVisible();

    await jamesPage.close();
});

test('a removed member keeps their account and is sent to set up a household of their own', async ({ page, browser }) => {
    await ownerOfNewHousehold(page, 'Rhea', 'Remover');

    const inviteCode = page.getByRole('switch', { name: 'Invite code' });
    // A household's invite code starts switched on.
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
