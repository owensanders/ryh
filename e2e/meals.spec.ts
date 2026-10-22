import { expect, test } from '@playwright/test';
import { ownerOfNewHousehold } from './support/households';
import { storageState, uniqueName } from './support/users';

test.use({ storageState: storageState('owner') });

test('the owner saves a recipe, sends its ingredients to a list, and plans it for tonight', async ({ page }) => {
    const recipe = uniqueName('Lemon risotto');
    const ingredient = uniqueName('Arborio rice');

    await page.goto('/recipes');
    await page.getByRole('button', { name: 'New recipe' }).click();

    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Name').fill(recipe);
    await dialog.getByRole('button', { name: '+ Add ingredient' }).click();
    await dialog.getByLabel('Ingredient 1', { exact: true }).fill(ingredient);
    await dialog.getByLabel('Ingredient 1 quantity').fill('300g');

    const listSelect = dialog.getByLabel('Also add ingredients to a shopping list');
    const listName = (await listSelect.locator('option').nth(1).innerText()).trim();
    await listSelect.selectOption({ label: listName });
    await dialog.getByRole('button', { name: 'Add recipe' }).click();

    await expect(dialog).toBeHidden();
    await expect(page.getByRole('button', { name: new RegExp(recipe) })).toBeVisible();

    await page.goto('/shopping');
    await page.getByRole('link', { name: new RegExp(listName) }).click();
    await expect(page.getByRole('checkbox', { name: new RegExp(ingredient) })).toBeVisible();

    // The planner labels days d/m in UTC (the app's timezone).
    const today = new Date();
    const dayLabel = `${String(today.getUTCDate()).padStart(2, '0')}/${String(today.getUTCMonth() + 1).padStart(2, '0')}`;

    await page.goto('/meals');
    await page.getByRole('button', { name: `Add a meal on ${dayLabel}` }).click();
    await dialog.getByLabel('Recipe', { exact: true }).selectOption({ label: recipe });
    await dialog.getByLabel('Slot').selectOption('dinner');
    await dialog.getByRole('button', { name: 'Add meal' }).click();

    await expect(dialog).toBeHidden();
    await expect(page.getByTitle(recipe)).toBeVisible();
});

test('the owner edits a planned meal, drags it to another day, then removes it', async ({ page }) => {
    const meal = uniqueName('Fish pie');
    const dialog = page.getByRole('dialog');

    // The planner labels days d/m in UTC (the app's timezone).
    const today = new Date();
    const todayLabel = `${String(today.getUTCDate()).padStart(2, '0')}/${String(today.getUTCMonth() + 1).padStart(2, '0')}`;
    const dayColumn = (label: string) => page.locator('div.group').filter({ has: page.getByRole('button', { name: `Add a meal on ${label}` }) });

    await page.goto('/meals');
    await page.getByRole('button', { name: `Add a meal on ${todayLabel}` }).click();
    await dialog.getByLabel('…or add a new recipe').fill(meal);
    await dialog.getByRole('button', { name: 'Add meal' }).click();
    await expect(dialog).toBeHidden();

    await page.getByTitle(meal).click();
    await expect(dialog.getByRole('heading', { name: 'Edit meal' })).toBeVisible();
    await dialog.getByLabel('Slot').selectOption('lunch');
    await dialog.getByRole('button', { name: 'Save changes' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('button', { name: new RegExp(`lunch\\s+${meal}`, 'i') })).toBeVisible();

    // Any other day in the week shown.
    const labels = await page.getByRole('button', { name: /^Add a meal on / }).evaluateAll((buttons) => buttons.map((b) => b.getAttribute('aria-label')!.replace('Add a meal on ', '')));
    const otherDay = labels.find((label) => label !== todayLabel)!;
    await page.getByTitle(meal).dragTo(dayColumn(otherDay));
    await expect(dayColumn(otherDay).getByTitle(meal)).toBeVisible();

    await dayColumn(otherDay).getByTitle(meal).click();
    await dialog.getByRole('button', { name: 'Remove' }).click();
    await page.getByRole('dialog').filter({ hasText: 'Remove this meal from the plan?' }).getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByTitle(meal)).toHaveCount(0);
});

test.describe('a fresh household', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    // In e2e the AI is a fixed fake (App\Ai\Fakes\E2eMealPlanFake) that answers after ~7s.
    test('plans the week with the AI step by step, then from its recipes once the weekly plan is used', async ({ page }) => {
        test.setTimeout(60_000);

        // A household of its own, so its weekly AI plan and accepted dinners don't touch the shared Parkers.
        await ownerOfNewHousehold(page, 'Wren', 'Wizard');

        await page.goto('/meals');
        await page.getByRole('button', { name: 'Plan my week' }).click();
        const dialog = page.getByRole('dialog');
        const next = dialog.getByRole('button', { name: 'Next' });

        await expect(dialog.getByRole('button', { name: 'Days' })).toHaveAttribute('aria-current', 'step');
        await expect(dialog.getByRole('button', { name: 'Finishing touches' })).toBeVisible();
        await next.click();

        // Too long to pass validation, to check the dialog brings us back here.
        await dialog.getByLabel('Avoid').fill('x'.repeat(256));
        await dialog.getByLabel('Vegetarian').check();
        await next.click();

        await expect(dialog.getByRole('button', { name: 'Kitchen' })).toHaveAttribute('aria-current', 'step');
        await expect(dialog.getByRole('button', { name: 'Oven' })).toHaveAttribute('aria-pressed', 'true');
        await dialog.getByRole('button', { name: 'Air fryer' }).click();
        await expect(dialog.getByRole('button', { name: 'Air fryer' })).toHaveAttribute('aria-pressed', 'true');
        await next.click();

        await expect(dialog.getByText(/4 people · Vegetarian · Oven, Hob, Air fryer/)).toBeVisible();
        await dialog.getByRole('button', { name: 'Generate my week' }).click();

        await expect(dialog.getByRole('button', { name: 'Food' })).toHaveAttribute('aria-current', 'step');
        await expect(dialog.getByText(/avoid field must not be greater than 255 characters/)).toBeVisible();
        await dialog.getByLabel('Avoid').fill('mushrooms');
        await next.click();
        await next.click();
        await dialog.getByRole('button', { name: 'Generate my week' }).click();

        // The waiting screen works through what the AI was asked, against the clock.
        await expect(dialog.getByText('Planning your week')).toBeVisible();
        await expect(dialog.getByText('Steering clear of mushrooms')).toBeVisible();
        await expect(dialog.getByRole('status')).toHaveText('Reading your answers');
        await expect(dialog.getByRole('status')).toHaveText('Making every dinner vegetarian', { timeout: 10_000 });

        await expect(dialog.getByRole('heading', { name: 'Your week, ready to go' })).toBeVisible({ timeout: 15_000 });
        await expect(dialog.getByText('Lemon chicken traybake')).toBeVisible();

        await dialog.getByRole('button', { name: 'Accept this week' }).click();
        await expect(dialog).toBeHidden();
        await expect(page.getByTitle('Lemon chicken traybake').first()).toBeVisible();

        // The weekly AI plan is used, so the wizard drops its AI-only steps and plans from the saved recipes.
        await page.getByRole('button', { name: 'Plan my week' }).click();
        await expect(dialog.getByText(/You've used this week's AI plan/)).toBeVisible();
        await expect(dialog.getByRole('button', { name: 'Kitchen' })).toHaveCount(0);
        await next.click();

        await expect(dialog.getByRole('button', { name: 'Food' })).toHaveAttribute('aria-current', 'step');
        await expect(dialog.getByLabel('Anything to use up?')).toHaveCount(0);

        // Back keeps answers; a done step in the indicator jumps back too.
        await dialog.getByLabel('Avoid').fill('mushrooms');
        await dialog.getByRole('button', { name: 'Back', exact: true }).click();
        await next.click();
        await expect(dialog.getByLabel('Avoid')).toHaveValue('mushrooms');
        await dialog.getByRole('button', { name: 'Back to Days' }).click();
        await expect(dialog.getByText('Which days?')).toBeVisible();
        await next.click();

        // Answers carry over, and the fake's recipes aren't tagged Vegetarian, so nothing matches until it's unticked.
        await expect(dialog.getByLabel('Vegetarian')).toBeChecked();
        await dialog.getByRole('button', { name: 'Use my recipes' }).click();
        await expect(dialog.getByText(/None of your saved recipes match/)).toBeVisible();
        await dialog.getByLabel('Vegetarian').uncheck();
        await dialog.getByRole('button', { name: 'Use my recipes' }).click();
        await expect(dialog.getByRole('heading', { name: 'Your week, ready to go' })).toBeVisible();
        await expect(dialog.getByText(/From your saved recipes/)).toBeVisible();

        // The AI plan is kept: a browser that hasn't shown it yet (here, this one with its memory cleared) opens on it.
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
        await page.evaluate(() => localStorage.removeItem('ryh:ai-plans-seen'));
        await page.reload();
        await page.getByRole('button', { name: 'Plan my week' }).click();
        await expect(dialog.getByRole('heading', { name: 'Your week, ready to go' })).toBeVisible();
        await expect(dialog.getByText('Lemon chicken traybake')).toBeVisible();
    });
});
