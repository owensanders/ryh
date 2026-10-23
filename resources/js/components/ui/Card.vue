<script setup lang="ts">
/** The app's card: a rounded, bordered panel. `as` picks the element (a section, say); padding and layout classes pass through. */
withDefaults(defineProps<{ as?: string }>(), { as: 'div' });
</script>

<template>
    <component :is="as" class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]"><slot /></component>
</template>
