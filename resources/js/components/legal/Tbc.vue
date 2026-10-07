<script setup lang="ts">
// Prints a legal detail, or a loud placeholder while it's still unset in config/legal.php.
defineProps<{ value: string | null | undefined; label: string }>();
</script>

<template>
    <span v-if="value">{{ value }}</span>
    <mark v-else class="rounded px-1 font-semibold text-hh-ink" style="background: color-mix(in srgb, var(--hh-sun) 45%, transparent)" data-tbc
        >[{{ label }} to be confirmed]</mark
    >
</template>
