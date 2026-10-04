import { test as setup } from '@playwright/test';
import { logInAndLand } from './support/auth';
import { storageState, users, type Role } from './support/users';

// Sign each seeded role in once; journeys reuse the saved session.
for (const role of Object.keys(users) as Role[]) {
    setup(`sign in as ${role}`, async ({ page }) => {
        await logInAndLand(page, users[role].email);
        await page.context().storageState({ path: storageState(role) });
    });
}
