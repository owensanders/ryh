import { logIn, logInAndLand } from './support/auth';
import { expect, test } from './support/fixtures';
import { registerNewUser, verifyEmail } from './support/register';
import { uniqueName } from './support/users';

test.use({ role: null });

test('signing in lands on the dashboard and signing out returns to the landing page', async ({ page, household }) => {
    await logInAndLand(page, household.members.owner.email);
    await expect(page.getByText(/Good (morning|afternoon|evening), Sarah/)).toBeVisible();

    await page.getByRole('button', { name: 'Logout' }).click();
    await expect(page).toHaveURL(/\/$/);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
});

test('a wrong password is refused', async ({ page, household }) => {
    await logIn(page, household.members.owner.email, 'not-the-password');

    await expect(page.getByText('These credentials do not match our records.')).toBeVisible();
    await expect(page).toHaveURL(/\/login$/);
});

test('a new user registers, sets up a household, verifies their email and reaches the dashboard', async ({ page }) => {
    const email = await registerNewUser(page, 'Tess', 'Tester');

    await page.getByLabel('Household name').fill(uniqueName('The Testers'));
    await page.getByRole('button', { name: 'Create household' }).click();

    await expect(page.getByRole('heading', { name: 'Your household is live' })).toBeVisible();
    await page.getByRole('link', { name: 'Open my dashboard' }).click();

    // Unverified, so the dashboard holds them at the verification prompt.
    await expect(page).toHaveURL(/\/verify-email$/);

    await verifyEmail(page, email);

    // Back to the dashboard they were sent away from.
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText(/Good (morning|afternoon|evening), Tess/)).toBeVisible();
});
