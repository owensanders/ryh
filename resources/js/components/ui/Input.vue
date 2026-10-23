<script setup lang="ts">
import { controlClasses, type ControlSize } from './controlClasses';
import { useFieldAria } from './field';

/** A text-like input in the design's style. Other attributes (id, type, placeholder) pass through. */
const props = withDefaults(defineProps<{ size?: ControlSize; bare?: boolean }>(), { size: 'md', bare: false });
const model = defineModel<string | number | null>();
const aria = useFieldAria();
</script>

<template>
    <input v-model="model" :class="controlClasses(props.size, props.bare)" v-bind="aria" />
</template>
