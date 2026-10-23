import { PLANNED_MEAL_PROPS, pounds } from '@/lib/ryh';
import type { MealSuggestionData, PlannerDayData, ShoppingListData } from '@/types/generated';
import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { computed, inject, provide, ref, type ComputedRef, type InjectionKey } from 'vue';

interface PlannedDinner {
    day: PlannerDayData;
    position: number;
    suggestion: MealSuggestionData;
}

/**
 * The answers half of "Plan my week": the pool of suggested dinners, which
 * one each day shows, swapping and regenerating them (no request), and
 * saving the week one dinner at a time.
 */
function createMealPlanSuggestions(
    plannedDays: ComputedRef<PlannerDayData[]>,
    budgetAmount: ComputedRef<number | null>,
    shoppingLists: () => ShoppingListData[],
) {
    const pool = ref<MealSuggestionData[]>([]);
    // Pool index shown for each selected day, in day order.
    const choices = ref<number[]>([]);
    // Where the pool came from: the AI (one regenerate) or the recipe library (free, unlimited).
    const source = ref<'ai' | 'library'>('ai');
    const regenerated = ref(false);
    // Plan positions ticked for replacement before regenerating.
    const marked = ref<number[]>([]);
    const targetListId = ref<number | null>(null);
    const accepting = ref(false);
    const acceptIndex = ref(0);
    // Days whose dinner couldn't be saved on accept, with the first reason given.
    const acceptFailures = ref<string[]>([]);
    const acceptError = ref<string | null>(null);

    const assignment = computed<PlannedDinner[]>(() =>
        plannedDays.value
            .map((day, position) => ({ day, position, suggestion: pool.value[choices.value[position]] }))
            .filter((entry) => entry.suggestion !== undefined),
    );

    /** Pool indexes not currently on the plan, in pool order. */
    const unusedChoices = computed(() => pool.value.map((_, index) => index).filter((index) => !choices.value.includes(index)));

    const canRegenerate = computed(() => unusedChoices.value.length > 0 && (source.value === 'library' || !regenerated.value));

    const canSwap = computed(() => unusedChoices.value.length > 0 && (source.value === 'library' || regenerated.value));

    const planHint = computed(() => {
        if (source.value === 'library') {
            return canSwap.value
                ? "From your saved recipes, so it doesn't use your AI plan. Swap any dinner, or tick some and regenerate as often as you like."
                : "From your saved recipes, so it doesn't use your AI plan. Add more recipes for more variety.";
        }

        if (canRegenerate.value) {
            return "Not quite right? Tick the dinners you'd like replaced and regenerate once — or regenerate them all.";
        }

        return canSwap.value
            ? "Tap the swap icon on any dinner you're not keen on to switch it for another suggestion."
            : "That's every suggestion for this week — your next AI plan is available in a week.";
    });

    const regenerateLabel = computed(() => (marked.value.length === 0 ? 'Regenerate all' : `Regenerate ${marked.value.length}`));

    const estimatedTotal = computed(() => assignment.value.reduce((sum, entry) => sum + (entry.suggestion.estimatedCost ?? 0), 0));

    const overBudget = computed(() => budgetAmount.value !== null && estimatedTotal.value > budgetAmount.value);

    const costSummary = computed(() => {
        const dinners = `for ${assignment.value.length} ${assignment.value.length === 1 ? 'dinner' : 'dinners'}`;

        return budgetAmount.value === null ? `${dinners}.` : `${dinners} vs your ${pounds(budgetAmount.value)} weekly food budget.`;
    });

    const acceptFailureMessage = computed(() => {
        const added = assignment.value.length - acceptFailures.value.length;
        const reason = acceptError.value ? `: ${acceptError.value}` : '';

        return `Added ${added} of ${assignment.value.length}. Couldn't add ${acceptFailures.value.join(', ')}${reason}. The rest are on your planner.`;
    });

    function reset(): void {
        pool.value = [];
        choices.value = [];
        regenerated.value = false;
        marked.value = [];
        acceptFailures.value = [];
        acceptError.value = null;
        targetListId.value = shoppingLists()[0]?.id ?? null;
    }

    /** Lays the new suggestions over the chosen days. False when there were none. */
    function show(from: 'ai' | 'library', meals: MealSuggestionData[]): boolean {
        source.value = from;
        pool.value = meals;
        choices.value = plannedDays.value.map((_, position) => position).filter((index) => index < meals.length);
        regenerated.value = false;
        marked.value = [];

        return meals.length > 0;
    }

    function toggleMarked(position: number): void {
        marked.value = marked.value.includes(position) ? marked.value.filter((p) => p !== position) : [...marked.value, position];
    }

    /** Replace the ticked dinners (or all of them) with meals not on the plan. No API call. */
    function regenerate(): void {
        const fresh = [...unusedChoices.value];
        const positions = marked.value.length > 0 ? marked.value : choices.value.map((_, position) => position);

        choices.value = choices.value.map((current, position) => (positions.includes(position) ? (fresh.shift() ?? current) : current));
        marked.value = [];

        if (source.value === 'ai') {
            regenerated.value = true;
        }
    }

    /** Replace one day's dinner with the next meal from the pool that isn't on the plan. */
    function swap(position: number): void {
        const current = choices.value[position];
        const next = unusedChoices.value.find((index) => index > current) ?? unusedChoices.value[0];

        if (next !== undefined) {
            choices.value = choices.value.map((choice, i) => (i === position ? next : choice));
        }
    }

    function mealPayload(entry: PlannedDinner, index: number): RequestPayload {
        const common = { new_recipe_shopping_list_id: targetListId.value, planned_on: entry.day.date, slot: 'dinner', cook_id: null };

        // A library recipe is planned by id alone, which leaves its saved details untouched.
        if (entry.suggestion.recipeId !== undefined) {
            return { recipe_id: entry.suggestion.recipeId, ...common };
        }

        return {
            recipe_id: null,
            new_recipe_name: entry.suggestion.name,
            new_recipe_description: entry.suggestion.description,
            new_recipe_duration_label: entry.suggestion.durationLabel,
            new_recipe_difficulty: entry.suggestion.difficulty,
            new_recipe_tags: [],
            new_recipe_ingredients: entry.suggestion.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' })),
            new_recipe_tint: index % 5,
            new_recipe_is_favourite: false,
            ...common,
        };
    }

    function acceptNext(entries: PlannedDinner[], done: () => void): void {
        if (acceptIndex.value >= entries.length) {
            accepting.value = false;

            // Stay open to say what didn't save, rather than dropping it silently.
            if (acceptFailures.value.length === 0) {
                done();
            }

            return;
        }

        const entry = entries[acceptIndex.value];

        router.post(route('meals.store'), mealPayload(entry, acceptIndex.value), {
            preserveScroll: true,
            preserveState: true,
            only: PLANNED_MEAL_PROPS,
            onError: (errors) => {
                acceptFailures.value.push(`${entry.day.dayLabel} (${entry.suggestion.name})`);
                acceptError.value ??= Object.values(errors)[0] ?? null;
            },
            onFinish: () => {
                acceptIndex.value++;
                acceptNext(entries, done);
            },
        });
    }

    /** Saves each dinner in turn, then calls `done` if they all saved. */
    function accept(done: () => void): void {
        if (assignment.value.length === 0) {
            return;
        }

        accepting.value = true;
        acceptIndex.value = 0;
        acceptFailures.value = [];
        acceptError.value = null;
        acceptNext(assignment.value, done);
    }

    return {
        source,
        marked,
        targetListId,
        accepting,
        acceptIndex,
        acceptFailures,
        assignment,
        canRegenerate,
        canSwap,
        planHint,
        regenerateLabel,
        estimatedTotal,
        overBudget,
        costSummary,
        acceptFailureMessage,
        shoppingLists: computed(shoppingLists),
        reset,
        show,
        toggleMarked,
        regenerate,
        swap,
        accept,
    };
}

export type MealPlanSuggestions = ReturnType<typeof createMealPlanSuggestions>;

const key: InjectionKey<MealPlanSuggestions> = Symbol('MealPlanSuggestions');

/** Called by the dialog; AiPlanResult reads the same suggestions with useMealPlanSuggestions(). */
export function provideMealPlanSuggestions(...args: Parameters<typeof createMealPlanSuggestions>): MealPlanSuggestions {
    const state = createMealPlanSuggestions(...args);
    provide(key, state);

    return state;
}

export function useMealPlanSuggestions(): MealPlanSuggestions {
    const state = inject(key);

    if (state === undefined) {
        throw new Error('useMealPlanSuggestions() needs provideMealPlanSuggestions() in a parent: the AiMealPlanDialog.');
    }

    return state;
}
