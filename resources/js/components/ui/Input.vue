<script setup lang="ts">
import { useFieldAria } from './field';

/** A text-like input in the design's style. Other attributes (id, type, placeholder) pass through. */
const model = defineModel<string | number | null>();
const aria = useFieldAria();
</script>

<template>
    <input v-model="model" class="ryh-input" v-bind="aria" />
</template>
