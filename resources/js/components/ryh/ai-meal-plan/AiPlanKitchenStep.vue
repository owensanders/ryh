<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';

const { form, aiOptions, toggleEquipment, fieldError } = useAiMealPlanForm();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Kitchen equipment</span>
        <div class="flex flex-wrap gap-1.5">
            <button
                v-for="item in aiOptions.equipment"
                :key="item"
                type="button"
                class="rounded-lg border px-2.5 py-1.5 text-[12.5px] font-semibold"
                :class="form.equipment.includes(item) ? 'border-ryh-ink bg-ryh-ink text-ryh-shell' : 'border-ryh-line bg-ryh-card text-ryh-ink2'"
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
                :class="form.confidence === level.value ? 'border-ryh-ink bg-ryh-ink text-ryh-shell' : 'border-ryh-line bg-ryh-card text-ryh-ink2'"
                :aria-pressed="form.confidence === level.value"
                @click="form.confidence = level.value"
            >
                {{ level.label }}
            </button>
        </div>
        <p v-if="fieldError('confidence')" class="text-[12.5px] text-ryh-coral">{{ fieldError('confidence') }}</p>
    </div>
</template>
