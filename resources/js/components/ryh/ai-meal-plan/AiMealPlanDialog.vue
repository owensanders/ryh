<script setup lang="ts">
import AiPlanDaysStep from '@/components/ryh/ai-meal-plan/AiPlanDaysStep.vue';
import AiPlanExtrasStep from '@/components/ryh/ai-meal-plan/AiPlanExtrasStep.vue';
import AiPlanFoodStep from '@/components/ryh/ai-meal-plan/AiPlanFoodStep.vue';
import AiPlanKitchenStep from '@/components/ryh/ai-meal-plan/AiPlanKitchenStep.vue';
import AiPlanLoading from '@/components/ryh/ai-meal-plan/AiPlanLoading.vue';
import AiPlanResult from '@/components/ryh/ai-meal-plan/AiPlanResult.vue';
import AiPlanStepIndicator from '@/components/ryh/ai-meal-plan/AiPlanStepIndicator.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { provideAiMealPlanForm, type AiMealPlanSettings } from '@/composables/useAiMealPlanForm';
import { hasSeenPlan, markPlanSeen, useAiPlanUpdates } from '@/composables/useAiPlanUpdates';
import { provideMealPlanSuggestions } from '@/composables/useMealPlanSuggestions';
import type { SharedData } from '@/types';
import type { AiMealPlanData, MealSuggestionData } from '@/types/generated';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<AiMealPlanSettings & { open: boolean; aiPlan: AiMealPlanData | null }>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const page = usePage<SharedData>();

// The questions and the suggestions each live in a composable, shared with the step and result components.
const { form, step, steps, isLastStep, canContinue, aiUnavailable, selectedDays, plannedDays, budgetAmount, reset, next, back, goToFirstError } =
    provideAiMealPlanForm(props);
const suggestions = provideMealPlanSuggestions(plannedDays, budgetAmount, () => props.shoppingLists);

const stage = ref<'form' | 'loading' | 'result'>('form');

// The AI plan is written by a queued job. While waiting, any plan newer than this one is the answer.
const waitingAfterId = ref<number | null>(null);
const waiting = computed(() => stage.value === 'loading' && waitingAfterId.value !== null);
const planError = ref<string | null>(null);

useAiPlanUpdates(waiting);

watch(
    () => props.aiPlan,
    (plan) => {
        if (!waiting.value || plan === null || plan.id <= (waitingAfterId.value ?? 0) || plan.status === 'pending') {
            return;
        }

        waitingAfterId.value = null;

        if (plan.status === 'ready') {
            markPlanSeen(plan.id);
            showSuggestions('ai', plan.meals);
        } else {
            planError.value = plan.error;
            stage.value = 'form';
        }
    },
);

const STEP_COMPONENTS = { days: AiPlanDaysStep, food: AiPlanFoodStep, kitchen: AiPlanKitchenStep, extras: AiPlanExtrasStep };

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        stage.value = 'form';
        planError.value = null;
        waitingAfterId.value = null;
        reset();
        suggestions.reset();

        // Pick up where they left off: a plan still being written, or one that finished while they were away.
        const plan = props.aiPlan;

        if (plan?.status === 'pending') {
            waitingAfterId.value = plan.id - 1;
            stage.value = 'loading';
        } else if (plan?.status === 'ready' && !hasSeenPlan(plan.id)) {
            markPlanSeen(plan.id);
            showSuggestions('ai', plan.meals);
        }
    },
);

function close(): void {
    emit('update:open', false);
}

function showSuggestions(from: 'ai' | 'library', meals: MealSuggestionData[]): void {
    stage.value = suggestions.show(from, meals) ? 'result' : 'form';
}

function generate(): void {
    stage.value = 'loading';
    planError.value = null;
    waitingAfterId.value = props.aiPlan?.id ?? 0;

    form.transform((data) => ({
        ...data,
        budget: data.budget === '' ? null : data.budget,
    })).post(route('meals.aiPlan'), {
        preserveScroll: true,
        // Nothing to do on success: the plan arrives as the aiPlan prop, now (a sync queue) or later.
        onError: (errors) => {
            waitingAfterId.value = null;
            stage.value = 'form';
            goToFirstError(errors);
        },
    });
}

/** A week from the household's saved recipes - no AI call, doesn't use the weekly plan. */
function planFromLibrary(): void {
    form.transform((data) => ({ diets: data.diets, avoid: data.avoid })).post(route('meals.libraryPlan'), {
        preserveScroll: true,
        onSuccess: () => showSuggestions('library', page.props.flash?.libraryMeals ?? []),
    });
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
                <AiPlanStepIndicator />

                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="translate-x-2 opacity-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-to-class="-translate-x-2 opacity-0"
                >
                    <div :key="step" class="flex min-h-[210px] flex-col gap-4">
                        <p class="text-[13.5px] text-ryh-ink2">{{ steps[step].hint }}</p>
                        <component :is="STEP_COMPONENTS[steps[step].key]" />
                    </div>
                </Transition>

                <p v-if="isLastStep && (page.props.errors.ai || planError)" class="text-[12.5px] text-ryh-coral">
                    {{ page.props.errors.ai ?? planError }}
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <button v-if="step > 0" type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" :disabled="form.processing" @click="back">
                        Back
                    </button>
                    <button v-else type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="close">Cancel</button>

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

            <AiPlanLoading v-else-if="stage === 'loading'" />

            <AiPlanResult v-else @close="close" />
        </DialogContent>
    </Dialog>
</template>
