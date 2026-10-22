<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';
import { pounds } from '@/lib/ryh';
import { Check, Sparkles } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const { form, budgetAmount } = useAiMealPlanForm();

// The AI call is one request with no real progress to report, so this paces
// a checklist of what it was asked to do against the clock while it's shown.
const LOADING_STEP_SECONDS = 6;
const elapsed = ref(0);
const ticker = setInterval(() => (elapsed.value += 0.5), 500);
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
</script>

<template>
    <div class="flex flex-col gap-4 py-1">
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
            <div class="h-full rounded-full bg-ryh-coral transition-[width] duration-500 ease-linear" :style="{ width: `${loadingProgress}%` }"></div>
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
</template>
