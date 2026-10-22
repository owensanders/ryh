import { PLANNED_DAY_PROPS, PLANNED_MEAL_PROPS } from '@/lib/ryh';
import type { PlannedMealData, RecipeData } from '@/types/generated';
import type { TagOption } from '@/types/ryh';
import { router, useForm } from '@inertiajs/vue3';
import { computed, inject, provide, ref, type InjectionKey } from 'vue';

// Errors the dialog shows beside their own field; any other gets a general message.
const HANDLED_ERROR_KEYS = ['recipe_id', 'new_recipe_name', 'new_recipe_description', 'planned_on'];

interface MealEditorSettings {
    recipes: RecipeData[];
    tagOptions: TagOption[];
}

/**
 * The Meal Planner's add/edit dialog: pick a saved recipe or name a new one,
 * with its details filled in from the library, then save or remove the meal.
 */
function createMealEditor(settings: MealEditorSettings) {
    const open = ref(false);
    const editing = ref<PlannedMealData | null>(null);
    const confirmOpen = ref(false);

    const form = useForm({
        recipe_id: null as number | null,
        new_recipe_name: '',
        new_recipe_description: '',
        new_recipe_duration_label: '',
        new_recipe_difficulty: '',
        new_recipe_tags: [] as string[],
        new_recipe_ingredients: [] as { name: string; quantity: string }[],
        new_recipe_tint: 0,
        new_recipe_is_favourite: false as boolean,
        new_recipe_shopping_list_id: null as number | null,
        planned_on: '',
        slot: 'dinner',
        cook_id: null as number | null,
    });

    const showRecipeDetails = computed(() => form.new_recipe_name.trim() !== '' || form.recipe_id !== null);
    const hasUnhandledErrors = computed(() => Object.keys(form.errors).some((key) => !HANDLED_ERROR_KEYS.includes(key)));

    // Drops any tag not in tagOptions — e.g. legacy/seeded values that predate
    // the fixed tag list — since there's no checkbox to deselect them and
    // re-submitting them as-is would fail validation with no visible error.
    function validTags(tags: string[]): string[] {
        return tags.filter((tag) => settings.tagOptions.some((option) => option.value === tag));
    }

    function populateRecipeDetailFields(recipe: RecipeData): void {
        form.new_recipe_description = recipe.description ?? '';
        form.new_recipe_duration_label = recipe.durationLabel ?? '';
        form.new_recipe_difficulty = recipe.difficulty ?? '';
        form.new_recipe_tags = validTags(recipe.tags);
        form.new_recipe_ingredients = recipe.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' }));
        form.new_recipe_tint = recipe.tint;
        form.new_recipe_is_favourite = recipe.isFavourite;
    }

    function clearRecipeDetailFields(): void {
        form.new_recipe_description = '';
        form.new_recipe_duration_label = '';
        form.new_recipe_difficulty = '';
        form.new_recipe_tags = [];
        form.new_recipe_ingredients = [];
        form.new_recipe_tint = 0;
        form.new_recipe_is_favourite = false;
    }

    function onRecipeSelected(): void {
        form.clearErrors();
        form.new_recipe_name = '';
        const recipe = settings.recipes.find((r) => r.id === form.recipe_id) ?? null;

        if (recipe) {
            populateRecipeDetailFields(recipe);
        } else {
            clearRecipeDetailFields();
        }
    }

    function onNewRecipeNameInput(): void {
        if (form.recipe_id !== null) {
            form.clearErrors();
            clearRecipeDetailFields();
        }

        form.recipe_id = null;
    }

    function openNew(date: string): void {
        editing.value = null;
        form.clearErrors();
        form.defaults({
            recipe_id: null,
            new_recipe_name: '',
            new_recipe_description: '',
            new_recipe_duration_label: '',
            new_recipe_difficulty: '',
            new_recipe_tags: [],
            new_recipe_ingredients: [],
            new_recipe_tint: 0,
            new_recipe_is_favourite: false,
            new_recipe_shopping_list_id: null,
            planned_on: date,
            slot: 'dinner',
            cook_id: null,
        });
        form.reset();
        open.value = true;
    }

    function openEdit(meal: PlannedMealData, date: string): void {
        editing.value = meal;
        form.clearErrors();

        const recipe = settings.recipes.find((r) => r.id === meal.recipeId) ?? null;

        form.defaults({
            recipe_id: meal.recipeId,
            new_recipe_name: '',
            new_recipe_description: recipe?.description ?? '',
            new_recipe_duration_label: recipe?.durationLabel ?? '',
            new_recipe_difficulty: recipe?.difficulty ?? '',
            new_recipe_tags: recipe ? validTags(recipe.tags) : [],
            new_recipe_ingredients: recipe ? recipe.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' })) : [],
            new_recipe_tint: recipe?.tint ?? 0,
            new_recipe_is_favourite: recipe?.isFavourite ?? false,
            new_recipe_shopping_list_id: null,
            planned_on: date,
            slot: meal.slotKey,
            cook_id: meal.cook?.id ?? null,
        });
        form.reset();
        open.value = true;
    }

    function submit(): void {
        const options = {
            preserveScroll: true,
            only: PLANNED_MEAL_PROPS,
            onSuccess: () => {
                open.value = false;
            },
        };

        form.transform((data) => ({
            ...data,
            new_recipe_ingredients: data.new_recipe_ingredients.filter((ingredient) => ingredient.name.trim() !== ''),
        }));

        if (editing.value) {
            form.patch(route('meals.update', { meal: editing.value.id }), options);
        } else {
            form.post(route('meals.store'), options);
        }
    }

    function destroy(): void {
        if (editing.value === null) {
            return;
        }

        confirmOpen.value = true;
    }

    function confirmDestroy(): void {
        if (editing.value === null) {
            return;
        }

        confirmOpen.value = false;

        router.delete(route('meals.destroy', { meal: editing.value.id }), {
            preserveScroll: true,
            only: PLANNED_DAY_PROPS,
            onSuccess: () => {
                open.value = false;
            },
        });
    }

    return {
        open,
        editing,
        confirmOpen,
        form,
        showRecipeDetails,
        hasUnhandledErrors,
        onRecipeSelected,
        onNewRecipeNameInput,
        openNew,
        openEdit,
        submit,
        destroy,
        confirmDestroy,
    };
}

export type MealEditor = ReturnType<typeof createMealEditor>;

const key: InjectionKey<MealEditor> = Symbol('MealEditor');

/** Called by the Meal Planner page; the week and the dialog reach the same editor with useMealEditor(). */
export function provideMealEditor(settings: MealEditorSettings): MealEditor {
    const editor = createMealEditor(settings);
    provide(key, editor);

    return editor;
}

export function useMealEditor(): MealEditor {
    const editor = inject(key);

    if (editor === undefined) {
        throw new Error('useMealEditor() needs provideMealEditor() in a parent: the Meal Planner page.');
    }

    return editor;
}
