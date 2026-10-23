import { pounds } from '@/lib/ryh';
import type { PlannerDayData, ShoppingListData } from '@/types/generated';
import type { AiPlannerOptions } from '@/types/ryh';
import { useForm } from '@inertiajs/vue3';
import { computed, inject, provide, ref, type InjectionKey } from 'vue';

/** What the Meal Planner tells the "Plan my week" dialog. */
export interface AiMealPlanSettings {
    aiConfigured: boolean;
    aiPaused: boolean;
    aiAvailableFrom: string | null;
    aiSuggestedBudget: number | null;
    aiOptions: AiPlannerOptions;
    days: PlannerDayData[];
    /** The household's today, from the server, so any week shown is judged by its clock. */
    todayDate: string;
    shoppingLists: ShoppingListData[];
}

const STEPS = [
    { key: 'days', label: 'Days', hint: "Pick the dinners to plan and who's eating." },
    { key: 'food', label: 'Food', hint: 'Any diets or ingredients to steer around?' },
    { key: 'kitchen', label: 'Kitchen', hint: 'What can you cook with, and how adventurous are you feeling?' },
    { key: 'extras', label: 'Finishing touches', hint: "A few last details, then we'll plan your week." },
] as const;

// Fields not listed here (budget, goals, notes, ai) live on the last step.
const FIELD_STEPS: Record<string, number> = { people: 0, diets: 1, avoid: 1, use_up: 1, equipment: 2, confidence: 2 };

/** The questions half of "Plan my week": the answers, which step is showing, and which days to plan. */
function createAiMealPlanForm(settings: AiMealPlanSettings) {
    const form = useForm({
        people: 4,
        budget: '' as string | number,
        diets: [] as string[],
        avoid: '',
        goals: [] as string[],
        confidence: 'comfortable',
        // Nearly every UK kitchen has these two; unticking them is the exception.
        equipment: ['Oven', 'Hob'] as string[],
        use_up: '',
        notes: '',
    });

    // Which question step is showing.
    const step = ref(0);
    const selectedDays = ref<number[]>([]);

    const days = computed(() => settings.days);
    const aiOptions = computed(() => settings.aiOptions);

    /** Why the AI option can't be used right now, if it can't. Planning from recipes still works. */
    const aiUnavailable = computed(() => {
        if (!settings.aiConfigured) {
            return "AI meal planning isn't available right now.";
        }

        if (settings.aiPaused) {
            return "AI meal planning is taking a short break. Your weekly plan hasn't been used.";
        }

        return settings.aiAvailableFrom ? `You've used this week's AI plan — the next one is available from ${settings.aiAvailableFrom}.` : null;
    });

    // Planning from recipes only uses days, diets and avoid, so skip the AI-only questions when the AI can't be used.
    const steps = computed(() => (aiUnavailable.value ? STEPS.slice(0, 2) : [...STEPS]));
    const isLastStep = computed(() => step.value === steps.value.length - 1);
    const canContinue = computed(() => step.value !== 0 || selectedDays.value.length > 0);

    const plannedDays = computed(() =>
        [...selectedDays.value]
            .sort((a, b) => a - b)
            .map((dayIndex) => settings.days[dayIndex])
            .filter((day) => day !== undefined),
    );

    const budgetAmount = computed(() => {
        const amount = Number(form.budget);

        return form.budget === '' || Number.isNaN(amount) ? null : amount;
    });

    const summary = computed(() => {
        const dinners = plannedDays.value.length;
        const parts = [`${dinners} ${dinners === 1 ? 'dinner' : 'dinners'}`, `${form.people} ${form.people === 1 ? 'person' : 'people'}`];

        if (form.diets.length > 0) {
            parts.push(form.diets.join(', '));
        }

        if (form.equipment.length > 0) {
            parts.push(form.equipment.join(', '));
        }

        if (budgetAmount.value !== null) {
            parts.push(pounds(budgetAmount.value));
        }

        return parts.join(' · ');
    });

    function freeDayIndexes(): number[] {
        // Past days are never preselected - planning dinners for them is wasted.
        const upcoming = settings.days.map((day, index) => ({ day, index })).filter(({ day }) => day.date >= settings.todayDate);
        const free = upcoming.filter(({ day }) => !day.meals.some((m) => m.slotKey === 'dinner'));

        return (free.length ? free : upcoming).map(({ index }) => index);
    }

    /** Back to the first question, with the free days picked. The answers are kept for next time. */
    function reset(): void {
        step.value = 0;
        selectedDays.value = freeDayIndexes();

        if (form.budget === '' && settings.aiSuggestedBudget !== null) {
            form.budget = settings.aiSuggestedBudget;
        }

        form.clearErrors();
    }

    function toggleDay(index: number): void {
        selectedDays.value = selectedDays.value.includes(index) ? selectedDays.value.filter((i) => i !== index) : [...selectedDays.value, index];
    }

    function toggleEquipment(item: string): void {
        form.equipment = form.equipment.includes(item) ? form.equipment.filter((e) => e !== item) : [...form.equipment, item];
    }

    function next(): void {
        if (canContinue.value && !isLastStep.value) {
            step.value++;
        }
    }

    function back(): void {
        step.value = Math.max(0, step.value - 1);
    }

    /** Jump back to an answered step from the indicator. */
    function goTo(index: number): void {
        if (index < step.value) {
            step.value = index;
        }
    }

    /** After a failed submit, show the first step with something to fix. */
    function goToFirstError(errors: Record<string, string>): void {
        step.value = Math.min(...Object.keys(errors).map((key) => FIELD_STEPS[key.split('.')[0]] ?? steps.value.length - 1));
    }

    /** The first error for a field, including per-item errors on arrays (e.g. equipment.0). */
    function fieldError(field: string): string | undefined {
        const errors = form.errors as Record<string, string>;

        return Object.entries(errors).find(([key]) => key === field || key.startsWith(`${field}.`))?.[1];
    }

    return {
        form,
        step,
        steps,
        isLastStep,
        canContinue,
        days,
        aiOptions,
        aiUnavailable,
        selectedDays,
        plannedDays,
        budgetAmount,
        summary,
        reset,
        toggleDay,
        toggleEquipment,
        next,
        back,
        goTo,
        goToFirstError,
        fieldError,
    };
}

export type AiMealPlanForm = ReturnType<typeof createAiMealPlanForm>;

const key: InjectionKey<AiMealPlanForm> = Symbol('AiMealPlanForm');

/** Called by the dialog; its step components read the same form with useAiMealPlanForm(). */
export function provideAiMealPlanForm(settings: AiMealPlanSettings): AiMealPlanForm {
    const state = createAiMealPlanForm(settings);
    provide(key, state);

    return state;
}

export function useAiMealPlanForm(): AiMealPlanForm {
    const state = inject(key);

    if (state === undefined) {
        throw new Error('useAiMealPlanForm() needs provideAiMealPlanForm() in a parent: the AiMealPlanDialog.');
    }

    return state;
}
