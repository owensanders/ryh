import { expect, test } from './support/fixtures';
import { registerNewUser, verifyEmail } from './support/register';
import { uniqueName } from './support/users';

test('someone joins with the invite code, the owner approves them and gives them a role', async ({ page: owner, browser }) => {
    await owner.goto('/house');

    const inviteCode = owner.getByRole('switch', { name: 'Invite code' });
    // A household's invite code starts switched on.
    await expect(inviteCode).toHaveAttribute('aria-checked', 'true');
    const code = (await owner.getByTestId('join-code').innerText()).trim();

    // A new person asks to join.
    const joiner = await browser.newPage();
    const surname = uniqueName('Parker').replace(/\s/g, '-');
    const email = await registerNewUser(joiner, 'Gran', surname);
    await joiner.getByRole('button', { name: /Join with a code/ }).click();
    await joiner.getByLabel('Invite code').fill(code);
    await joiner.getByRole('button', { name: 'Request to join' }).click();

    await expect(joiner.getByRole('heading', { name: 'Request sent' })).toBeVisible();
    await expect(joiner.getByRole('button', { name: 'Open my dashboard' })).toBeDisabled();

    // The owner sees the request and approves it.
    await owner.reload();
    await expect(owner.getByText(`Gran ${surname}`)).toBeVisible();
    await owner.getByRole('button', { name: `Approve Gran ${surname}` }).click();
    await expect(owner.getByRole('button', { name: `Approve Gran ${surname}` })).toHaveCount(0);

    // ...and makes them a Teen, which first asks the owner to confirm they're a parent or guardian.
    const role = owner.getByRole('combobox', { name: `Role for Gran ${surname}` });
    const dialog = owner.getByRole('dialog');

    // Cancelling leaves the role as it was.
    await role.selectOption({ label: 'Teen' });
    await dialog.getByRole('button', { name: 'Cancel' }).click();
    await expect(dialog).toBeHidden();
    await expect(role).toHaveValue('adult');

    await role.selectOption({ label: 'Teen' });
    await expect(dialog.getByRole('button', { name: 'Change role' })).toBeDisabled();
    await dialog.getByRole('checkbox', { name: /parent or guardian/ }).click();
    const saved = owner.waitForResponse((response) => response.request().method() === 'PATCH' && response.url().endsWith('/role'));
    await dialog.getByRole('button', { name: 'Change role' }).click();
    await saved;
    await owner.reload();
    await expect(role).toHaveValue('teen');

    // Once they verify their email, they're in.
    await verifyEmail(joiner, email);
    await joiner.goto('/dashboard');
    await expect(joiner.getByText(/Good (morning|afternoon|evening), Gran/)).toBeVisible();
    await expect(joiner.getByRole('link', { name: 'Budget', exact: true })).toHaveCount(0);

    await joiner.close();
});

test("the owner changes the household's time zone", async ({ page }) => {
    await page.goto('/house');
    const timezone = page.getByLabel('Time zone');
    await expect(timezone).toHaveValue('Europe/London');

    await timezone.selectOption('Asia/Tokyo');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Time zone updated' })).toBeVisible();
    await page.reload();
    await expect(timezone).toHaveValue('Asia/Tokyo');
});
