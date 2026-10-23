import { test as base, expect, type Page } from '@playwright/test';

export type Role = 'owner' | 'adult' | 'teen' | 'child';

export interface Member {
    id: number;
    email: string;
    name: string;
}

/** A Parker household made for one test (routes/e2e.php): the seeded demo data, its own members. */
export interface Household {
    slug: string;
    name: string;
    members: Record<Role, Member>;
}

export const PASSWORD = 'password';

/** Signs `page` in as `member` without the login form, landing on `to`. */
export async function signIn(page: Page, member: Member, to = '/app'): Promise<void> {
    await page.goto(`/_e2e/login/${member.id}?to=${encodeURIComponent(to)}`);
}

interface Fixtures {
    /** Who `page` is signed in as; null leaves it signed out. */
    role: Role | null;
    household: Household;
    /** Opens another browser, signed in as one of the household's members; closed after the test. */
    pageAs: (role: Role) => Promise<Page>;
}

/**
 * Every test gets its own household, so tests share nothing and run in
 * parallel. `page` is signed in as its `role` (the owner unless a test says
 * `test.use({ role: 'child' })`, signed out for `role: null`) and starts on
 * the dashboard.
 */
export const test = base.extend<Fixtures>({
    role: ['owner' as Role | null, { option: true }],

    household: async ({ request }, use) => {
        const response = await request.post('/_e2e/households');
        expect(response.ok(), await response.text()).toBe(true);
        await use((await response.json()) as Household);
    },

    page: async ({ page, household, role }, use) => {
        if (role !== null) {
            await signIn(page, household.members[role]);
            await expect(page).toHaveURL(/\/dashboard$/);
        }

        await use(page);
    },

    pageAs: async ({ browser, household }, use) => {
        const opened: Page[] = [];

        await use(async (role) => {
            // The project's options (baseURL, device) apply to a page opened here too.
            const page = await browser.newPage();
            opened.push(page);
            await signIn(page, household.members[role]);
            await expect(page).toHaveURL(/\/dashboard$/);

            return page;
        });

        await Promise.all(opened.map((page) => page.context().close()));
    },
});

export { expect };
