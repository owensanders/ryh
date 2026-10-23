<script setup lang="ts">
import { computed, provide } from 'vue';
import { fieldKey } from './field';

/**
 * A label, its control (the slot) and the control's error, stacked. `for` is
 * the control's id, so the label names it; the error, when there is one, is
 * announced with it (aria-describedby) and marks it invalid. The `aside`
 * slot sits at the label's end (a "Forgot password?" link), and `hideLabel`
 * keeps the label for screen readers only, where the context already says
 * what the field is.
 */
const props = defineProps<{ label: string; for: string; error?: string | null; hint?: string; hideLabel?: boolean }>();

const errorId = computed(() => `${props.for}-error`);
const hintId = computed(() => `${props.for}-hint`);

provide(fieldKey, {
    invalid: computed(() => !!props.error),
    describedBy: computed(() => [props.hint ? hintId.value : null, props.error ? errorId.value : null].filter(Boolean).join(' ') || undefined),
});
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div v-if="$slots.aside" class="flex items-center justify-between gap-2">
            <label :for="props.for" class="ryh-label">{{ props.label }}</label>
            <slot name="aside" />
        </div>
        <label v-else :for="props.for" :class="props.hideLabel ? 'sr-only' : 'ryh-label'">{{ props.label }}</label>
        <slot />
        <p v-if="props.hint" :id="hintId" class="text-[12.5px] text-ryh-ink3">{{ props.hint }}</p>
        <p v-if="props.error" :id="errorId" class="text-[12.5px] text-ryh-coral">{{ props.error }}</p>
    </div>
</template>
