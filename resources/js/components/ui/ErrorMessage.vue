<script setup lang="ts">
/**
 * A form error that isn't about one field (a ticked box, a list being built
 * up, a whole form). An error about one field goes in that Field's `error`.
 */
defineProps<{ message?: string | null }>();
</script>

<template>
    <p v-if="message" class="text-[12.5px] text-ryh-coral">{{ message }}</p>
</template>
