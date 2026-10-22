<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';

const { form, aiOptions, aiUnavailable, fieldError } = useAiMealPlanForm();
</script>

<template>
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
        <input id="ai_use_up" v-model="form.use_up" type="text" class="ryh-input" placeholder="e.g. half a butternut squash, chickpeas" />
        <p v-if="fieldError('use_up')" class="text-[12.5px] text-ryh-coral">{{ fieldError('use_up') }}</p>
    </div>
</template>
