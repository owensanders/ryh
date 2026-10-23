<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * The app's button: `ryh-btn` with one of the design's colourings. Full width
 * by default, as on a form; pass `class="w-auto"` beside other controls. With
 * `href` it's an Inertia link that looks the same.
 */
const props = withDefaults(
    defineProps<{
        /** primary (coral), secondary (soft), danger (soft, coral text), dark (ink), card. Leave it out to colour it yourself. */
        variant?: keyof typeof VARIANTS | null;
        type?: 'button' | 'submit';
        href?: string;
    }>(),
    { variant: 'primary', type: 'button', href: undefined },
);
</script>

<script lang="ts">
const VARIANTS = {
    primary: 'bg-ryh-coral text-white',
    secondary: 'bg-ryh-soft text-ryh-ink',
    danger: 'bg-ryh-soft text-ryh-coral',
    dark: 'bg-ryh-ink text-ryh-shell',
    card: 'bg-ryh-card text-ryh-ink',
} as const;
</script>

<template>
    <Link v-if="props.href !== undefined" :href="props.href" class="ryh-btn" :class="props.variant ? VARIANTS[props.variant] : ''"><slot /></Link>
    <button v-else :type="props.type" class="ryh-btn" :class="props.variant ? VARIANTS[props.variant] : ''"><slot /></button>
</template>
