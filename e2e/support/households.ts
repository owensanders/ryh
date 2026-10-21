import { expect, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { registerNewUser, verifyEmail } from './register';
import { uniqueName } from './users';

/**
 * Signs a brand-new, verified user up as the owner of their own fresh household,
 * leaving them on the House page. Journeys that fill a household up use this so
 * they never crowd the shared seeded Parkers.
 */
export async function ownerOfNewHousehold(page: Page, firstName: string, lastName: string): Promise<{ email: string; householdName: string }> {
    const email = await registerNewUser(page, firstName, lastName);
    const householdName = uniqueName(`The ${lastName}s`);

    await page.getByLabel('Household name').fill(householdName);
    await page.getByRole('button', { name: 'Create household' }).click();
    await expect(page.getByRole('heading', { name: 'Your household is live' })).toBeVisible();

    await verifyEmail(page, email);
    await page.goto('/house');

    return { email, householdName };
}

/**
 * Adds `count` active members straight into the owner's current household — setup
 * for the cap journeys, which are about what happens once it's full, not about
 * signing eight people up through the UI.
 */
export function addMembers(ownerEmail: string, count: number): void {
    const code = `
        $household = App\\Models\\Household::findOrFail(App\\Models\\User::where('email', '${ownerEmail}')->value('last_household_id'));
        foreach (range(1, ${count}) as $i) {
            $user = App\\Models\\User::forceCreate([
                'name' => "Filler {$i}",
                'email' => uniqid('filler-', true).'@example.test',
                'password' => bcrypt('Correct-Horse-42!'),
                'email_verified_at' => now(),
            ]);
            $user->households()->attach($household->id, ['role' => 'adult', 'pending' => false]);
        }
    `;

    execFileSync('./e2e/scripts/artisan.sh', ['tinker', '--execute', code], { stdio: 'pipe' });
}

/**
 * A verified account in no household, for journeys that invite an existing user.
 * Use this rather than a seeded user, so the invites a journey sends (and often
 * leaves pending) never tie the shared Parkers' members to other households.
 */
export function createAccount(name: string): string {
    const email = `${uniqueName(name).replace(/\s/g, '-').toLowerCase()}@example.test`;
    const code = `
        App\\Models\\User::forceCreate([
            'name' => '${name}',
            'email' => '${email}',
            'password' => bcrypt('Correct-Horse-42!'),
            'email_verified_at' => now(),
        ]);
    `;

    execFileSync('./e2e/scripts/artisan.sh', ['tinker', '--execute', code], { stdio: 'pipe' });

    return email;
}
