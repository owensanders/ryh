<script setup lang="ts">
import { useFieldAria } from './field';

/** A multi-line text box in the design's style. */
const model = defineModel<string | null>();
const aria = useFieldAria();
</script>

<template>
    <textarea v-model="model" class="ryh-input" v-bind="aria" />
</template>
