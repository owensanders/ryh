<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';

const { form, aiOptions, summary, fieldError } = useAiMealPlanForm();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label for="ai_budget" class="ryh-label">Weekly budget (£)</label>
        <input id="ai_budget" v-model="form.budget" type="text" inputmode="decimal" class="ryh-input w-36" placeholder="Optional" />
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
            <a :href="route('privacy') + '#ai-meal-plans'" target="_blank" class="font-semibold underline underline-offset-2">Privacy Policy</a>.
        </p>
    </div>

    <div class="rounded-xl bg-ryh-soft px-3 py-2 text-[12.5px] text-ryh-ink2">
        <p class="font-semibold text-ryh-ink">{{ summary }}</p>
        <p class="mt-1">
            Your household gets one AI plan a week. Not keen on it? You can regenerate once for a completely different set of meals, then swap any
            dinner you don't fancy. Planning from your saved recipes is free and unlimited.
        </p>
    </div>
</template>
