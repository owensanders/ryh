<script setup lang="ts">
/**
 * A button that shows only an icon (the slot). `label` is required: it's the
 * name a screen reader announces and the tooltip on hover, so an icon-only
 * button can't be left unnamed.
 */
const props = withDefaults(defineProps<{ label: string; type?: 'button' | 'submit' }>(), { type: 'button' });
</script>

<template>
    <button :type="props.type" :aria-label="props.label" :title="props.label"><slot /></button>
</template>
