import { expect, type Page } from '@playwright/test';
import { PASSWORD } from './users';

export async function logIn(page: Page, email: string, password = PASSWORD): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();
}

export async function logInAndLand(page: Page, email: string): Promise<void> {
    await logIn(page, email);
    await expect(page).toHaveURL(/\/dashboard$/);
}
