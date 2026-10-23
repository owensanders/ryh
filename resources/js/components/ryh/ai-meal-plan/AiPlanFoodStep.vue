<script setup lang="ts">
import Field from '@/components/ryh/ui/Field.vue';
import Input from '@/components/ryh/ui/Input.vue';
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

    <Field label="Avoid" for="ai_avoid" :error="fieldError('avoid')">
        <Input id="ai_avoid" v-model="form.avoid" type="text" placeholder="e.g. mushrooms, shellfish" />
    </Field>

    <div v-if="!aiUnavailable" class="flex flex-col gap-1.5">
        <label for="ai_use_up" class="ryh-label">Anything to use up?</label>
        <Input id="ai_use_up" v-model="form.use_up" type="text" placeholder="e.g. half a butternut squash, chickpeas" />
        <p v-if="fieldError('use_up')" class="text-[12.5px] text-ryh-coral">{{ fieldError('use_up') }}</p>
    </div>
</template>
