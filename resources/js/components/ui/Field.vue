<script setup lang="ts">
import { computed, provide } from 'vue';
import { fieldKey } from './field';

/**
 * A label, its control (the slot) and the control's error, stacked. `for` is
 * the control's id, so the label names it; the error, when there is one, is
 * announced with it (aria-describedby) and marks it invalid.
 */
const props = defineProps<{ label: string; for: string; error?: string | null; hint?: string }>();

const errorId = computed(() => `${props.for}-error`);
const hintId = computed(() => `${props.for}-hint`);

provide(fieldKey, {
    invalid: computed(() => !!props.error),
    describedBy: computed(() => [props.hint ? hintId.value : null, props.error ? errorId.value : null].filter(Boolean).join(' ') || undefined),
});
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="props.for" class="ryh-label">{{ props.label }}</label>
        <slot />
        <p v-if="props.hint" :id="hintId" class="text-[12.5px] text-ryh-ink3">{{ props.hint }}</p>
        <p v-if="props.error" :id="errorId" class="text-[12.5px] text-ryh-coral">{{ props.error }}</p>
    </div>
</template>
