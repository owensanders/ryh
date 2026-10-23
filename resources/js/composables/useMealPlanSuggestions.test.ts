import { withSetup } from '@/test/withSetup';
import type { MealSuggestionData, PlannerDayData, ShoppingListData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import { provideMealPlanSuggestions } from './useMealPlanSuggestions';

vi.mock('@inertiajs/vue3', async (actual) => ({ ...(await actual<typeof import('@inertiajs/vue3')>()), router: { post: vi.fn() } }));

const day = (n: number): PlannerDayData => ({ date: `2026-10-${19 + n}`, dayLabel: `Day ${n}`, dateLabel: '', isToday: false, meals: [] });

const meal = (name: string, extra: Partial<MealSuggestionData> = {}): MealSuggestionData => ({
    name,
    description: `${name}, simply.`,
    durationLabel: '30 min',
    difficulty: 'Easy',
    estimatedCost: 5,
    tags: [],
    ingredients: [{ name: 'Onion', quantity: null }],
    ...extra,
});

type PostOptions = { onError: (errors: Record<string, string>) => void; onFinish: () => void };

describe('useMealPlanSuggestions', () => {
    const days = ref([day(0), day(1)]);
    const budget = ref<number | null>(null);
    const lists = [{ id: 7, name: 'Tesco' } as ShoppingListData];
    const setUp = () =>
        withSetup(() =>
            provideMealPlanSuggestions(
                computed(() => days.value),
                computed(() => budget.value),
                () => lists,
            ),
        );
    const names = (s: ReturnType<typeof setUp>) => s.assignment.value.map((entry) => entry.suggestion.name);

    beforeEach(() => {
        budget.value = null;
    });

    it('lays the pool over the chosen days in order', () => {
        const s = setUp();

        expect(s.show('ai', [meal('Chilli'), meal('Curry'), meal('Risotto'), meal('Tacos')])).toBe(true);
        expect(names(s)).toEqual(['Chilli', 'Curry']);
        expect(s.show('ai', [])).toBe(false);
    });

    it('lets an AI plan regenerate once, then swap to the next suggestion not on the plan', () => {
        const s = setUp();
        s.show('ai', [meal('Chilli'), meal('Curry'), meal('Risotto'), meal('Tacos')]);

        expect(s.canRegenerate.value).toBe(true);
        expect(s.canSwap.value).toBe(false);

        s.toggleMarked(1);
        expect(s.regenerateLabel.value).toBe('Regenerate 1');
        s.regenerate();
        expect(names(s)).toEqual(['Chilli', 'Risotto']);

        // That was the AI plan's one regenerate; swapping still works, through what isn't on the plan.
        expect(s.canRegenerate.value).toBe(false);
        expect(s.canSwap.value).toBe(true);
        s.swap(0);
        expect(names(s)).toEqual(['Curry', 'Risotto']);
        s.swap(0);
        expect(names(s)).toEqual(['Tacos', 'Risotto']);
    });

    it('lets a library plan regenerate as often as there are other recipes', () => {
        const s = setUp();
        s.show('library', [meal('Chilli'), meal('Curry'), meal('Risotto'), meal('Tacos')]);

        s.regenerate();
        expect(names(s)).toEqual(['Risotto', 'Tacos']);
        expect(s.canRegenerate.value).toBe(true);
    });

    it('adds up the cost and says when it’s over the weekly budget', () => {
        budget.value = 9;
        const s = setUp();
        s.show('ai', [meal('Chilli', { estimatedCost: 4.5 }), meal('Curry', { estimatedCost: 6 })]);

        expect(s.estimatedTotal.value).toBe(10.5);
        expect(s.overBudget.value).toBe(true);
    });

    it('saves each dinner in turn: a library recipe by id, a new one with its details', () => {
        const s = setUp();
        s.reset();
        s.show('ai', [meal('Chilli', { recipeId: 12 }), meal('Curry')]);
        const done = vi.fn();

        s.accept(done);
        const [firstUrl, firstData, firstOptions] = vi.mocked(router.post).mock.calls[0];
        expect(firstUrl).toBe('/meals.store');
        expect(firstData).toEqual({ recipe_id: 12, new_recipe_shopping_list_id: 7, planned_on: '2026-10-19', slot: 'dinner', cook_id: null });

        (firstOptions as PostOptions).onFinish();
        const [, secondData, secondOptions] = vi.mocked(router.post).mock.calls[1];
        expect(secondData).toMatchObject({
            recipe_id: null,
            new_recipe_name: 'Curry',
            new_recipe_ingredients: [{ name: 'Onion', quantity: '' }],
            new_recipe_tint: 1,
        });

        (secondOptions as PostOptions).onFinish();
        expect(done).toHaveBeenCalledOnce();
        expect(s.accepting.value).toBe(false);
    });

    it('stays open to say which dinners didn’t save', () => {
        const s = setUp();
        s.show('ai', [meal('Chilli'), meal('Curry')]);
        const done = vi.fn();

        s.accept(done);
        const first = vi.mocked(router.post).mock.calls[0][2] as PostOptions;
        first.onError({ new_recipe_name: 'You already have a recipe with this name.' });
        first.onFinish();
        (vi.mocked(router.post).mock.calls[1][2] as PostOptions).onFinish();

        expect(done).not.toHaveBeenCalled();
        expect(s.acceptFailureMessage.value).toBe(
            "Added 1 of 2. Couldn't add Day 0 (Chilli): You already have a recipe with this name.. The rest are on your planner.",
        );
    });
});
