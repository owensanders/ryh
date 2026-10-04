import { expect, test } from '@playwright/test';
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
