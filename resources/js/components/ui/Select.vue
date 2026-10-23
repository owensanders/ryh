<script setup lang="ts">
import { controlClasses, type ControlSize } from './controlClasses';
import { useFieldAria } from './field';

/** A select in the design's style; the options are the slot. */
const props = withDefaults(defineProps<{ size?: ControlSize; bare?: boolean }>(), { size: 'md', bare: false });
const model = defineModel<string | number | null>();
const aria = useFieldAria();
</script>

<template>
    <select v-model="model" :class="controlClasses(props.size, props.bare)" v-bind="aria">
        <slot />
    </select>
</template>
