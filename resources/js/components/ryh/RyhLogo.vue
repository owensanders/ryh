<script setup lang="ts">
/**
 * The Run Your Home logo, redrawn from the brand artwork in the app's palette:
 * the roof and "RUN YOUR" take the current text colour (so they follow dark
 * mode and the dark hero panels), the window and "HOME" are coral. Size it
 * with a height class; the width follows.
 *
 * - `mark`: the roof alone, for tight spots.
 * - `horizontal`: the roof beside the two-line wordmark, for headers.
 * - `stacked`: the full lockup, roof above the wordmark.
 *
 * Where the installed app's icon is meant, use `/icons/icon.svg` instead.
 */
withDefaults(defineProps<{ variant?: 'mark' | 'horizontal' | 'stacked' }>(), { variant: 'horizontal' });

const windowPanes = [
    [328, 144],
    [406, 144],
    [328, 222],
    [406, 222],
];
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :viewBox="variant === 'mark' ? '0 0 790 282' : variant === 'horizontal' ? '0 0 1525 282' : '0 0 972 745'"
        class="w-auto flex-none"
        role="img"
        aria-label="Run Your Home"
    >
        <g :transform="variant === 'stacked' ? 'translate(91 0)' : undefined">
            <path
                d="M4 275 L395 6 L786 275 L731 275 L395 46 L59 275 Z M560 46 H644 V195 L560 138 Z"
                fill="currentColor"
                stroke="currentColor"
                stroke-width="6"
                stroke-linejoin="round"
            />
            <rect v-for="[x, y] in windowPanes" :key="`${x}-${y}`" :x="x" :y="y" width="56" height="56" rx="7" class="fill-ryh-coral" />
        </g>
        <g v-if="variant !== 'mark'" font-family="Figtree, ui-sans-serif, system-ui, sans-serif" font-weight="800">
            <template v-if="variant === 'horizontal'">
                <text x="860" y="84" font-size="117" textLength="662" lengthAdjust="spacingAndGlyphs" fill="currentColor">RUN YOUR</text>
                <text x="860" y="276" font-size="226" textLength="662" lengthAdjust="spacingAndGlyphs" class="fill-ryh-coral">HOME</text>
            </template>
            <template v-else>
                <text x="0" y="465" font-size="171" textLength="972" lengthAdjust="spacingAndGlyphs" fill="currentColor">RUN YOUR</text>
                <text x="0" y="737" font-size="336" textLength="972" lengthAdjust="spacingAndGlyphs" class="fill-ryh-coral">HOME</text>
            </template>
        </g>
    </svg>
</template>
