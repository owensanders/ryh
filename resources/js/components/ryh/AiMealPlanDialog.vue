<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PLANNED_MEAL_PROPS } from '@/lib/ryh';
import type { SharedData } from '@/types';
import type { MealSuggestionData, PlannerDayData, ShoppingListData } from '@/types/generated';
import type { AiPlannerOptions } from '@/types/ryh';
import type { RequestPayload } from '@inertiajs/core';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, Check, Sparkles } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    aiConfigured: boolean;
    aiPaused: boolean;
    aiAvailableFrom: string | null;
    aiSuggestedBudget: number | null;
    aiOptions: AiPlannerOptions;
    days: PlannerDayData[];
    shoppingLists: ShoppingListData[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const page = usePage<SharedData>();

const stage = ref<'form' | 'loading' | 'result'>('form');
// Which question step of the form is showing.
const step = ref(0);
const pool = ref<MealSuggestionData[]>([]);
// Pool index shown for each selected day, in day order.
const choices = ref<number[]>([]);
const regenerated = ref(false);
// Where the pool came from: the AI (one regenerate) or the recipe library (free, unlimited).
const source = ref<'ai' | 'library'>('ai');
// Plan positions ticked for replacement before regenerating.
const marked = ref<number[]>([]);
const selectedDays = ref<number[]>([]);
const targetListId = ref<number | null>(null);
const accepting = ref(false);
const acceptIndex = ref(0);
// Days whose dinner couldn't be saved on accept, with the first reason given.
const acceptFailures = ref<string[]>([]);
const acceptError = ref<string | null>(null);

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

function freeDayIndexes(): number[] {
    // Past days are never preselected - planning dinners for them is wasted.
    const today = props.days.find((day) => day.isToday)?.date ?? new Date().toLocaleDateString('en-CA');
    const upcoming = props.days.map((day, index) => ({ day, index })).filter(({ day }) => day.date >= today);
    const free = upcoming.filter(({ day }) => !day.meals.some((m) => m.slotKey === 'dinner'));

    return (free.length ? free : upcoming).map(({ index }) => index);
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        stage.value = 'form';
        step.value = 0;
        pool.value = [];
        choices.value = [];
        regenerated.value = false;
        marked.value = [];
        acceptFailures.value = [];
        acceptError.value = null;
        selectedDays.value = freeDayIndexes();
        targetListId.value = props.shoppingLists[0]?.id ?? null;
        if (form.budget === '' && props.aiSuggestedBudget !== null) {
            form.budget = props.aiSuggestedBudget;
        }
        form.clearErrors();
    },
);

function toggleDay(index: number): void {
    selectedDays.value = selectedDays.value.includes(index) ? selectedDays.value.filter((i) => i !== index) : [...selectedDays.value, index];
}

const plannedDays = computed(() =>
    [...selectedDays.value]
        .sort((a, b) => a - b)
        .map((dayIndex) => props.days[dayIndex])
        .filter((day) => day !== undefined),
);

const assignment = computed(() =>
    plannedDays.value
        .map((day, position) => ({ day, position, suggestion: pool.value[choices.value[position]] }))
        .filter((entry) => entry.suggestion !== undefined),
);

/** Pool indexes not currently on the plan, in pool order. */
const unusedChoices = computed(() => pool.value.map((_, index) => index).filter((index) => !choices.value.includes(index)));

const canRegenerate = computed(() => unusedChoices.value.length > 0 && (source.value === 'library' || !regenerated.value));

const canSwap = computed(() => unusedChoices.value.length > 0 && (source.value === 'library' || regenerated.value));

/** Why the AI option can't be used right now, if it can't. Planning from recipes still works. */
const aiUnavailable = computed(() => {
    if (!props.aiConfigured) {
        return "AI meal planning isn't available right now.";
    }

    if (props.aiPaused) {
        return "AI meal planning is taking a short break. Your weekly plan hasn't been used.";
    }

    return props.aiAvailableFrom ? `You've used this week's AI plan — the next one is available from ${props.aiAvailableFrom}.` : null;
});

const allSteps = [
    { key: 'days', label: 'Days', hint: "Pick the dinners to plan and who's eating." },
    { key: 'food', label: 'Food', hint: 'Any diets or ingredients to steer around?' },
    { key: 'kitchen', label: 'Kitchen', hint: 'What can you cook with, and how adventurous are you feeling?' },
    { key: 'extras', label: 'Finishing touches', hint: "A few last details, then we'll plan your week." },
];

// Planning from recipes only uses days, diets and avoid, so skip the AI-only questions when the AI can't be used.
const steps = computed(() => (aiUnavailable.value ? allSteps.slice(0, 2) : allSteps));

const isLastStep = computed(() => step.value === steps.value.length - 1);

const canContinue = computed(() => step.value !== 0 || selectedDays.value.length > 0);

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

/** Enter in a text field moves on a step rather than generating early. */
function submit(): void {
    if (!isLastStep.value) {
        next();
    } else if (aiUnavailable.value) {
        planFromLibrary();
    } else {
        generate();
    }
}

// Fields not listed here (budget, goals, notes, ai) live on the last step.
const fieldSteps: Record<string, number> = { people: 0, diets: 1, avoid: 1, use_up: 1, equipment: 2, confidence: 2 };

function firstStepWithError(errors: Record<string, string>): number {
    return Math.min(...Object.keys(errors).map((key) => fieldSteps[key.split('.')[0]] ?? steps.value.length - 1));
}

/** The first error for a field, including per-item errors on arrays (e.g. equipment.0). */
function fieldError(field: string): string | undefined {
    const errors = form.errors as Record<string, string>;

    return Object.entries(errors).find(([key]) => key === field || key.startsWith(`${field}.`))?.[1];
}

function toggleEquipment(item: string): void {
    form.equipment = form.equipment.includes(item) ? form.equipment.filter((e) => e !== item) : [...form.equipment, item];
}

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

const estimatedTotal = computed(() => assignment.value.reduce((sum, entry) => sum + (entry.suggestion.estimated_cost ?? 0), 0));

const budgetAmount = computed(() => {
    const amount = Number(form.budget);

    return form.budget === '' || Number.isNaN(amount) ? null : amount;
});

const overBudget = computed(() => budgetAmount.value !== null && estimatedTotal.value > budgetAmount.value);

const costSummary = computed(() => {
    const dinners = `for ${assignment.value.length} ${assignment.value.length === 1 ? 'dinner' : 'dinners'}`;

    return budgetAmount.value === null ? `${dinners}.` : `${dinners} vs your ${pounds(budgetAmount.value)} weekly food budget.`;
});

function pounds(amount: number): string {
    return `£${amount.toFixed(2)}`;
}

function showPool(from: 'ai' | 'library', meals: MealSuggestionData[]): void {
    source.value = from;
    pool.value = meals;
    choices.value = plannedDays.value.map((_, position) => position).filter((index) => index < meals.length);
    regenerated.value = false;
    marked.value = [];
    stage.value = meals.length > 0 ? 'result' : 'form';
}

// The AI call is one request with no real progress to report, so the waiting
// screen paces a checklist of what it was asked to do against the clock.
const LOADING_STEP_SECONDS = 6;
const elapsed = ref(0);
let ticker: ReturnType<typeof setInterval> | undefined;

watch(stage, (value) => {
    clearInterval(ticker);

    if (value === 'loading') {
        elapsed.value = 0;
        ticker = setInterval(() => (elapsed.value += 0.5), 500);
    }
});

onBeforeUnmount(() => clearInterval(ticker));

/** "a, b and c" */
function sentenceList(items: string[]): string {
    return items.length < 2 ? (items[0] ?? '') : `${items.slice(0, -1).join(', ')} and ${items[items.length - 1]}`;
}

const loadingSteps = computed(() => {
    const steps = ['Reading your answers'];

    if (form.diets.length > 0) {
        steps.push(`Making every dinner ${sentenceList(form.diets).toLowerCase()}`);
    }

    if (form.avoid.trim() !== '') {
        steps.push(`Steering clear of ${form.avoid.trim()}`);
    }

    if (form.use_up.trim() !== '') {
        steps.push(`Finding ways to use up ${form.use_up.trim()}`);
    }

    if (form.equipment.length > 0) {
        steps.push(`Sticking to your ${sentenceList(form.equipment).toLowerCase()}`);
    }

    steps.push("Picking a fortnight of dinners, so there's room to swap");
    steps.push(budgetAmount.value !== null ? `Pricing it against your ${pounds(budgetAmount.value)} budget` : 'Estimating ingredient costs');
    steps.push('Writing up the recipes');

    return steps;
});

// The last step stays in progress until the plan arrives.
const currentLoadingStep = computed(() => Math.min(Math.floor(elapsed.value / LOADING_STEP_SECONDS), loadingSteps.value.length - 1));

// Eases towards 95% (about 60% at 20s, 90% at 45s) and never claims to be done.
const loadingProgress = computed(() => 95 * (1 - Math.exp(-elapsed.value / 20)));

function generate(): void {
    stage.value = 'loading';

    form.transform((data) => ({
        ...data,
        budget: data.budget === '' ? null : data.budget,
    })).post(route('meals.aiPlan'), {
        preserveScroll: true,
        onSuccess: () => showPool('ai', page.props.flash?.aiMeals ?? []),
        onError: (errors) => {
            stage.value = 'form';
            step.value = firstStepWithError(errors);
        },
    });
}

/** A week from the household's saved recipes - no AI call, doesn't use the weekly plan. */
function planFromLibrary(): void {
    form.transform((data) => ({ diets: data.diets, avoid: data.avoid })).post(route('meals.libraryPlan'), {
        preserveScroll: true,
        onSuccess: () => showPool('library', page.props.flash?.libraryMeals ?? []),
    });
}

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

const acceptFailureMessage = computed(() => {
    const added = assignment.value.length - acceptFailures.value.length;
    const reason = acceptError.value ? `: ${acceptError.value}` : '';

    return `Added ${added} of ${assignment.value.length}. Couldn't add ${acceptFailures.value.join(', ')}${reason}. The rest are on your planner.`;
});

function mealPayload(entry: { day: PlannerDayData; suggestion: MealSuggestionData }, index: number): RequestPayload {
    const common = { new_recipe_shopping_list_id: targetListId.value, planned_on: entry.day.date, slot: 'dinner', cook_id: null };

    // A library recipe is planned by id alone, which leaves its saved details untouched.
    if (entry.suggestion.recipe_id !== undefined) {
        return { recipe_id: entry.suggestion.recipe_id, ...common };
    }

    return {
        recipe_id: null,
        new_recipe_name: entry.suggestion.name,
        new_recipe_description: entry.suggestion.description,
        new_recipe_duration_label: entry.suggestion.duration_label,
        new_recipe_difficulty: entry.suggestion.difficulty,
        new_recipe_tags: [],
        new_recipe_ingredients: entry.suggestion.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' })),
        new_recipe_tint: index % 5,
        new_recipe_is_favourite: false,
        ...common,
    };
}

function acceptNext(entries: { day: PlannerDayData; suggestion: MealSuggestionData }[]): void {
    if (acceptIndex.value >= entries.length) {
        accepting.value = false;

        // Keep the dialog open to say what didn't save, rather than dropping it silently.
        if (acceptFailures.value.length === 0) {
            emit('update:open', false);
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
            acceptNext(entries);
        },
    });
}

function accept(): void {
    if (assignment.value.length === 0) {
        return;
    }

    accepting.value = true;
    acceptIndex.value = 0;
    acceptFailures.value = [];
    acceptError.value = null;
    acceptNext(assignment.value);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-lg rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">
                    {{ stage === 'result' ? 'Your week, ready to go' : 'Plan my week' }}
                </DialogTitle>
            </DialogHeader>

            <form v-if="stage === 'form'" class="flex flex-col gap-4" @submit.prevent="submit">
                <ol class="flex items-start" aria-label="Plan my week steps">
                    <li v-for="(item, index) in steps" :key="item.key" class="relative flex flex-1 flex-col items-center gap-1">
                        <span
                            v-if="index > 0"
                            class="absolute right-1/2 top-[13px] h-0.5 w-full transition-colors"
                            :class="index <= step ? 'bg-ryh-ink' : 'bg-ryh-line'"
                            aria-hidden="true"
                        ></span>
                        <button
                            type="button"
                            class="relative z-10 grid h-7 w-7 place-items-center rounded-full text-[12px] font-extrabold transition"
                            :class="
                                index < step
                                    ? 'bg-ryh-ink text-ryh-shell'
                                    : index === step
                                      ? 'border-2 border-ryh-coral bg-ryh-card text-ryh-coral'
                                      : 'border border-ryh-line bg-ryh-soft text-ryh-ink3'
                            "
                            :disabled="index >= step"
                            :aria-current="index === step ? 'step' : undefined"
                            :aria-label="index < step ? `Back to ${item.label}` : item.label"
                            @click="goTo(index)"
                        >
                            <Check v-if="index < step" class="h-3.5 w-3.5" :stroke-width="3" />
                            <template v-else>{{ index + 1 }}</template>
                        </button>
                        <span class="text-center text-[11px] font-semibold" :class="index === step ? 'text-ryh-ink' : 'text-ryh-ink3'">
                            {{ item.label }}
                        </span>
                    </li>
                </ol>

                <p class="sr-only" aria-live="polite">Step {{ step + 1 }} of {{ steps.length }}: {{ steps[step].label }}</p>

                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="translate-x-2 opacity-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-to-class="-translate-x-2 opacity-0"
                >
                    <div :key="step" class="flex min-h-[210px] flex-col gap-4">
                        <p class="text-[13.5px] text-ryh-ink2">{{ steps[step].hint }}</p>

                        <template v-if="steps[step].key === 'days'">
                            <p v-if="aiUnavailable" class="rounded-xl bg-ryh-soft px-3 py-2 text-[12.5px] text-ryh-ink2">
                                {{ aiUnavailable }} You can still plan the week from your saved recipes.
                            </p>

                            <div class="flex flex-col gap-1.5">
                                <span class="ryh-label">Which days?</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="(day, index) in days"
                                        :key="day.date"
                                        type="button"
                                        class="rounded-lg border px-2.5 py-1.5 text-[12.5px] font-semibold"
                                        :class="
                                            selectedDays.includes(index)
                                                ? 'border-ryh-ink bg-ryh-ink text-ryh-shell'
                                                : 'border-ryh-line bg-ryh-card text-ryh-ink2'
                                        "
                                        :aria-pressed="selectedDays.includes(index)"
                                        @click="toggleDay(index)"
                                    >
                                        {{ day.dayLabel }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="!aiUnavailable" class="flex flex-col gap-1.5">
                                <label for="ai_people" class="ryh-label">People</label>
                                <input id="ai_people" v-model.number="form.people" type="number" min="1" max="12" class="ryh-input w-28" />
                                <p v-if="fieldError('people')" class="text-[12.5px] text-ryh-coral">{{ fieldError('people') }}</p>
                            </div>
                        </template>

                        <template v-else-if="steps[step].key === 'food'">
                            <div class="flex flex-col gap-1.5">
                                <span class="ryh-label">Diets</span>
                                <div class="flex flex-wrap gap-x-3 gap-y-1.5">
                                    <label v-for="diet in aiOptions.diets" :key="diet" class="flex items-center gap-1.5 text-[12.5px] text-ryh-ink2">
                                        <input v-model="form.diets" type="checkbox" :value="diet" class="h-3.5 w-3.5 accent-ryh-coral" />
                                        {{ diet }}
                                    </label>
                                </div>
                                <p v-if="fieldError('diets')" class="text-[12.5px] text-ryh-coral">{{ fieldError('diets') }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="ai_avoid" class="ryh-label">Avoid</label>
                                <input id="ai_avoid" v-model="form.avoid" type="text" class="ryh-input" placeholder="e.g. mushrooms, shellfish" />
                                <p v-if="fieldError('avoid')" class="text-[12.5px] text-ryh-coral">{{ fieldError('avoid') }}</p>
                            </div>

                            <div v-if="!aiUnavailable" class="flex flex-col gap-1.5">
                                <label for="ai_use_up" class="ryh-label">Anything to use up?</label>
                                <input
                                    id="ai_use_up"
                                    v-model="form.use_up"
                                    type="text"
                                    class="ryh-input"
                                    placeholder="e.g. half a butternut squash, chickpeas"
                                />
                                <p v-if="fieldError('use_up')" class="text-[12.5px] text-ryh-coral">{{ fieldError('use_up') }}</p>
                            </div>
                        </template>

                        <template v-else-if="steps[step].key === 'kitchen'">
                            <div class="flex flex-col gap-1.5">
                                <span class="ryh-label">Kitchen equipment</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="item in aiOptions.equipment"
                                        :key="item"
                                        type="button"
                                        class="rounded-lg border px-2.5 py-1.5 text-[12.5px] font-semibold"
                                        :class="
                                            form.equipment.includes(item)
                                                ? 'border-ryh-ink bg-ryh-ink text-ryh-shell'
                                                : 'border-ryh-line bg-ryh-card text-ryh-ink2'
                                        "
                                        :aria-pressed="form.equipment.includes(item)"
                                        @click="toggleEquipment(item)"
                                    >
                                        {{ item }}
                                    </button>
                                </div>
                                <p v-if="fieldError('equipment')" class="text-[12.5px] text-ryh-coral">{{ fieldError('equipment') }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <span class="ryh-label">Cooking confidence</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="level in aiOptions.confidence"
                                        :key="level.value"
                                        type="button"
                                        class="rounded-lg border px-2.5 py-1.5 text-[12.5px] font-semibold"
                                        :class="
                                            form.confidence === level.value
                                                ? 'border-ryh-ink bg-ryh-ink text-ryh-shell'
                                                : 'border-ryh-line bg-ryh-card text-ryh-ink2'
                                        "
                                        :aria-pressed="form.confidence === level.value"
                                        @click="form.confidence = level.value"
                                    >
                                        {{ level.label }}
                                    </button>
                                </div>
                                <p v-if="fieldError('confidence')" class="text-[12.5px] text-ryh-coral">{{ fieldError('confidence') }}</p>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex flex-col gap-1.5">
                                <label for="ai_budget" class="ryh-label">Weekly budget (£)</label>
                                <input
                                    id="ai_budget"
                                    v-model="form.budget"
                                    type="text"
                                    inputmode="decimal"
                                    class="ryh-input w-36"
                                    placeholder="Optional"
                                />
                                <p v-if="fieldError('budget')" class="text-[12.5px] text-ryh-coral">{{ fieldError('budget') }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <span class="ryh-label">Goals</span>
                                <div class="flex flex-wrap gap-x-3 gap-y-1.5">
                                    <label v-for="goal in aiOptions.goals" :key="goal" class="flex items-center gap-1.5 text-[12.5px] text-ryh-ink2">
                                        <input v-model="form.goals" type="checkbox" :value="goal" class="h-3.5 w-3.5 accent-ryh-coral" />
                                        {{ goal }}
                                    </label>
                                </div>
                                <p v-if="fieldError('goals')" class="text-[12.5px] text-ryh-coral">{{ fieldError('goals') }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="ai_notes" class="ryh-label">Notes for the AI</label>
                                <textarea
                                    id="ai_notes"
                                    v-model="form.notes"
                                    rows="2"
                                    maxlength="500"
                                    class="ryh-input min-h-[64px] resize-y"
                                    placeholder="e.g. My wife is pescatarian but I eat meat — can we share meals with a different protein?"
                                ></textarea>
                                <p v-if="fieldError('notes')" class="text-[12.5px] text-ryh-coral">{{ fieldError('notes') }}</p>
                                <p class="text-[12px] text-ryh-ink3">
                                    Leave out names and anything personal — your answers are sent to our AI provider to write the plan. See our
                                    <a :href="route('privacy') + '#ai-meal-plans'" target="_blank" class="font-semibold underline underline-offset-2"
                                        >Privacy Policy</a
                                    >.
                                </p>
                            </div>

                            <div class="rounded-xl bg-ryh-soft px-3 py-2 text-[12.5px] text-ryh-ink2">
                                <p class="font-semibold text-ryh-ink">{{ summary }}</p>
                                <p class="mt-1">
                                    Your household gets one AI plan a week. Not keen on it? You can regenerate once for a completely different set of
                                    meals, then swap any dinner you don't fancy. Planning from your saved recipes is free and unlimited.
                                </p>
                            </div>
                        </template>
                    </div>
                </Transition>

                <p v-if="isLastStep && page.props.errors.ai" class="text-[12.5px] text-ryh-coral">{{ page.props.errors.ai }}</p>

                <div class="flex flex-wrap items-center gap-2">
                    <button v-if="step > 0" type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" :disabled="form.processing" @click="back">
                        Back
                    </button>
                    <button v-else type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="emit('update:open', false)">Cancel</button>

                    <div class="ml-auto flex flex-wrap justify-end gap-2">
                        <button v-if="!isLastStep" type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="!canContinue">Next</button>
                        <button
                            v-else-if="aiUnavailable"
                            type="submit"
                            class="ryh-btn w-auto bg-ryh-coral text-white"
                            :disabled="selectedDays.length === 0 || form.processing"
                        >
                            Use my recipes
                        </button>
                        <template v-else>
                            <button
                                type="button"
                                class="ryh-btn w-auto bg-ryh-soft text-ryh-ink"
                                :disabled="selectedDays.length === 0 || form.processing"
                                @click="planFromLibrary"
                            >
                                Use my recipes
                            </button>
                            <button
                                type="submit"
                                class="ryh-btn w-auto bg-ryh-coral text-white"
                                :disabled="selectedDays.length === 0 || form.processing"
                            >
                                Generate my week
                            </button>
                        </template>
                    </div>
                </div>
            </form>

            <div v-else-if="stage === 'loading'" class="flex flex-col gap-4 py-1">
                <div class="flex items-center gap-3">
                    <span class="bg-ryh-coral/10 grid h-10 w-10 flex-none place-items-center rounded-full text-ryh-coral">
                        <Sparkles class="h-5 w-5 motion-safe:animate-pulse" :stroke-width="2.25" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[14px] font-extrabold text-ryh-ink">Planning your week</p>
                        <p class="text-[12.5px] text-ryh-ink2">This usually takes 30–60 seconds. Keep this window open.</p>
                    </div>
                </div>

                <div class="h-1.5 overflow-hidden rounded-full bg-ryh-soft" aria-hidden="true">
                    <div
                        class="h-full rounded-full bg-ryh-coral transition-[width] duration-500 ease-linear"
                        :style="{ width: `${loadingProgress}%` }"
                    ></div>
                </div>

                <ol class="flex flex-col gap-2">
                    <li
                        v-for="(text, index) in loadingSteps"
                        :key="text"
                        class="flex items-center gap-2.5 text-[13px] transition-colors duration-300"
                        :class="index <= currentLoadingStep ? 'text-ryh-ink' : 'text-ryh-ink3'"
                    >
                        <span class="grid h-5 w-5 flex-none place-items-center">
                            <Check v-if="index < currentLoadingStep" class="h-4 w-4 text-ryh-coral" :stroke-width="3" />
                            <span
                                v-else-if="index === currentLoadingStep"
                                class="h-4 w-4 rounded-full border-2 border-ryh-line border-t-ryh-coral motion-safe:animate-spin"
                            ></span>
                            <span v-else class="h-1.5 w-1.5 rounded-full bg-ryh-line"></span>
                        </span>
                        <span class="min-w-0 truncate">{{ text }}</span>
                    </li>
                </ol>

                <p v-if="elapsed >= 60" class="text-[12.5px] text-ryh-ink2">Still working. A busy week can take a little longer.</p>
                <p class="sr-only" role="status">{{ loadingSteps[currentLoadingStep] }}</p>
            </div>

            <div v-else class="flex flex-col gap-4">
                <p class="rounded-xl bg-ryh-soft px-3 py-2 text-[12.5px] text-ryh-ink2">{{ planHint }}</p>
                <p v-if="source === 'ai'" role="note" class="rounded-xl border border-ryh-sun px-3 py-2 text-[12.5px] text-ryh-ink2">
                    <span class="font-bold text-ryh-ink">Check before you cook.</span> AI suggestions can get things wrong — always check ingredients
                    for allergies, intolerances and dietary needs yourself.
                </p>

                <div class="flex flex-col gap-2">
                    <div v-for="entry in assignment" :key="entry.day.date" class="rounded-2xl border border-ryh-line bg-ryh-panel p-3">
                        <div class="flex items-start gap-1.5">
                            <span class="pt-[3px] text-[12px] font-extrabold">{{ entry.day.dayLabel }}</span>
                            <span class="mr-auto text-[15px] font-extrabold tracking-tight">{{ entry.suggestion.name }}</span>
                            <button
                                v-if="canRegenerate"
                                type="button"
                                class="ml-auto flex-none rounded-md border px-2 py-1 text-[11px] font-semibold transition"
                                :class="
                                    marked.includes(entry.position)
                                        ? 'border-ryh-coral bg-ryh-coral text-white'
                                        : 'border-ryh-line bg-ryh-card text-ryh-ink2'
                                "
                                :aria-pressed="marked.includes(entry.position)"
                                :aria-label="`Replace ${entry.day.dayLabel}'s dinner when regenerating`"
                                :disabled="accepting"
                                @click="toggleMarked(entry.position)"
                            >
                                Replace
                            </button>
                            <button
                                v-if="canSwap"
                                type="button"
                                class="grid h-7 w-7 flex-none place-items-center rounded-md text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-ink"
                                :aria-label="`Swap ${entry.day.dayLabel}'s dinner`"
                                title="Swap this dinner"
                                :disabled="accepting"
                                @click="swap(entry.position)"
                            >
                                <ArrowLeftRight class="h-3.5 w-3.5" :stroke-width="2.5" />
                            </button>
                        </div>
                        <p v-if="entry.suggestion.description" class="mt-1 text-[12.5px] text-ryh-ink2">{{ entry.suggestion.description }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span
                                v-if="entry.suggestion.duration_label"
                                class="rounded-md bg-ryh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-ryh-ink2"
                            >
                                {{ entry.suggestion.duration_label }}
                            </span>
                            <span
                                v-if="entry.suggestion.difficulty"
                                class="rounded-md bg-ryh-soft px-1.5 py-[3px] text-[10.5px] font-semibold text-ryh-ink2"
                            >
                                {{ entry.suggestion.difficulty }}
                            </span>
                            <span
                                v-if="entry.suggestion.estimated_cost"
                                class="rounded-md bg-ryh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-ryh-ink2"
                            >
                                ~{{ pounds(entry.suggestion.estimated_cost) }}
                            </span>
                        </div>
                    </div>
                </div>

                <p v-if="estimatedTotal > 0" class="text-[12.5px] text-ryh-ink2">
                    Estimated
                    <span class="font-bold" :class="overBudget ? 'text-ryh-coral' : 'text-ryh-ink'">{{ pounds(estimatedTotal) }}</span>
                    {{ costSummary }}
                </p>

                <div v-if="shoppingLists.length > 0" class="flex flex-col gap-1.5 border-t border-ryh-line pt-4">
                    <label for="ai_shopping_list" class="ryh-label">Add missing ingredients to</label>
                    <select id="ai_shopping_list" v-model="targetListId" class="ryh-input">
                        <option :value="null">Don't add</option>
                        <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                    </select>
                </div>

                <p v-if="acceptFailures.length > 0" class="text-[12.5px] text-ryh-coral">{{ acceptFailureMessage }}</p>

                <div class="flex items-center gap-2">
                    <button
                        v-if="acceptFailures.length === 0"
                        type="button"
                        class="ryh-btn w-auto bg-ryh-coral text-white"
                        :disabled="accepting"
                        @click="accept"
                    >
                        {{ accepting ? `Adding ${acceptIndex + 1} of ${assignment.length}…` : 'Accept this week' }}
                    </button>
                    <button
                        v-if="canRegenerate"
                        type="button"
                        class="ryh-btn w-auto bg-ryh-soft text-ryh-ink"
                        :disabled="accepting"
                        @click="regenerate"
                    >
                        {{ regenerateLabel }}
                    </button>
                    <button
                        type="button"
                        class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-ink"
                        :disabled="accepting"
                        @click="emit('update:open', false)"
                    >
                        Close
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
