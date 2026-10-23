<script setup lang="ts">
import { useFieldAria } from './field';

/** A select in the design's style; the options are the slot. */
const model = defineModel<string | number | null>();
const aria = useFieldAria();
</script>

<template>
    <select v-model="model" class="ryh-input" v-bind="aria">
        <slot />
    </select>
</template>
