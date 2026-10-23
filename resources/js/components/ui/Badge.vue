<script setup lang="ts">
/** A small soft label: a recipe's time or cost (`mono`), or a tag. */
withDefaults(defineProps<{ mono?: boolean }>(), { mono: false });
</script>

<template>
    <span class="rounded-md bg-ryh-soft px-1.5 py-[3px] text-[10.5px] text-ryh-ink2" :class="mono ? 'font-mono' : 'font-semibold'"><slot /></span>
</template>
