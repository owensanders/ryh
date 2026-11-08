import AxeBuilder from '@axe-core/playwright';
import type { Page } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { expect, test } from './support/fixtures';

/**
 * axe (Deque's accessibility engine) checks each main page against WCAG 2.1
 * A and AA: names on controls, labels on fields, ARIA used correctly, and so
 * on. A serious or critical problem fails the run; minor and moderate ones
 * are left to judgement. Colour contrast isn't checked: the design's greys
 * and coral are below AA for small text, and that's a choice (23 Oct 2026).
 */
const FAILS_ON = ['serious', 'critical'];

async function seriousProblems(page: Page): Promise<string[]> {
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).disableRules(['color-contrast']).analyze();

    return results.violations
        .filter((violation) => FAILS_ON.includes(violation.impact ?? ''))
        .map(
            (violation) =>
                `${violation.id} (${violation.impact}): ${violation.help}\n    ${violation.nodes.map((node) => node.target.join(' ')).join('\n    ')}`,
        );
}

const appPages = [
    '/dashboard',
    '/calendar',
    '/chores',
    '/meals',
    '/recipes',
    '/shopping',
    '/budget',
    '/documents',
    '/house',
    '/settings/profile',
    '/settings/password',
    '/settings/households',
    '/settings/appearance',
    '/settings/notifications',
    '/household/setup',
    '/household/select',
];

for (const path of appPages) {
    test(`${path} has no serious accessibility problems`, async ({ page }) => {
        await page.goto(path);
        await page.waitForLoadState('networkidle');

        expect(await seriousProblems(page)).toEqual([]);
    });
}

// Nothing is seeded into a folder, so upload one to have a document card checked too.
test('/documents with a document in the folder has no serious accessibility problems', async ({ page }) => {
    await page.goto('/documents');
    await page
        .getByLabel('Upload files')
        .setInputFiles({ name: 'receipt.pdf', mimeType: 'application/pdf', buffer: readFileSync('e2e/fixtures/receipt.pdf') });
    await expect(page.getByLabel('Move receipt.pdf to another folder')).toBeVisible();

    expect(await seriousProblems(page)).toEqual([]);
});

test.describe('signed out', () => {
    test.use({ role: null });

    for (const path of ['/', '/login', '/register', '/forgot-password']) {
        test(`${path} has no serious accessibility problems`, async ({ page }) => {
            await page.goto(path);
            await page.waitForLoadState('networkidle');

            expect(await seriousProblems(page)).toEqual([]);
        });
    }
});
