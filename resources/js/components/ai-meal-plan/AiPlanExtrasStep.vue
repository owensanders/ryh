<script setup lang="ts">
import Checkbox from '@/components/ui/Checkbox.vue';
import Field from '@/components/ui/Field.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';

const { form, aiOptions, summary, fieldError } = useAiMealPlanForm();
</script>

<template>
    <Field label="Weekly budget (£)" for="ai_budget" :error="fieldError('budget')">
        <Input id="ai_budget" v-model="form.budget" type="text" inputmode="decimal" class="w-36" placeholder="Optional" />
    </Field>

    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Goals</span>
        <div class="flex flex-wrap gap-x-3 gap-y-1.5">
            <label v-for="goal in aiOptions.goals" :key="goal" class="flex items-center gap-1.5 text-[12.5px] text-ryh-ink2">
                <Checkbox v-model="form.goals" :value="goal" size="sm" />
                {{ goal }}
            </label>
        </div>
        <p v-if="fieldError('goals')" class="text-[12.5px] text-ryh-coral">{{ fieldError('goals') }}</p>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="ai_notes" class="ryh-label">Notes for the AI</label>
        <Textarea
            id="ai_notes"
            v-model="form.notes"
            rows="2"
            maxlength="500"
            class="min-h-[64px] resize-y"
            placeholder="e.g. My wife is pescatarian but I eat meat — can we share meals with a different protein?"
        />
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
