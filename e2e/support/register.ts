import { expect, type Page } from '@playwright/test';
import { linkFromLatestEmail } from './mailpit';
import { uniqueName } from './users';

/** Signs a brand-new user up and leaves them on the household setup step. Returns their email. */
export async function registerNewUser(page: Page, firstName: string, lastName: string): Promise<string> {
    const email = `${uniqueName(firstName).replace(/\s/g, '-').toLowerCase()}@example.test`;

    await page.goto('/register');
    await page.getByLabel('First name').fill(firstName);
    await page.getByLabel('Last name').fill(lastName);
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill('Correct-Horse-42!');
    await page.getByRole('checkbox', { name: /terms of service/i }).click();
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page).toHaveURL(/\/household\/setup$/);

    return email;
}

/** Follows the verification link from the user's inbox. */
export async function verifyEmail(page: Page, email: string): Promise<void> {
    await page.goto(await linkFromLatestEmail(email, '/verify-email/'));
}
