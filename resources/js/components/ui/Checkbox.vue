<script setup lang="ts">
/**
 * A checkbox in the design's style. `v-model` a boolean, or an array with a
 * `value` per box for a set of choices. Other attributes (id, value) pass through.
 */
const props = withDefaults(defineProps<{ size?: 'sm' | 'md' }>(), { size: 'md' });
const model = defineModel<boolean | Array<string | number>>();
</script>

<template>
    <input
        v-model="model"
        type="checkbox"
        :class="[
            props.size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4',
            'shrink-0 accent-ryh-coral focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral',
        ]"
    />
</template>
