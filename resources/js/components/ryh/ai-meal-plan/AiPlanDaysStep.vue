<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';

const { form, days, selectedDays, toggleDay, aiUnavailable, fieldError } = useAiMealPlanForm();
</script>

<template>
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
                :class="selectedDays.includes(index) ? 'border-ryh-ink bg-ryh-ink text-ryh-shell' : 'border-ryh-line bg-ryh-card text-ryh-ink2'"
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
