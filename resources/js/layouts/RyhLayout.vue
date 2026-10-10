<script setup lang="ts">
import { pageHeading } from '@/lib/pageHeading';
import { Head } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

/**
 * A screen's title and subtitle. The frame around it (sidebar, header, tab
 * bar) is the persistent RyhShell layout, which each app screen opts
 * into with `defineOptions({ layout: RyhShell })` so moving between
 * screens swaps only the content instead of rebuilding the whole page.
 */
const props = defineProps<{
    title: string;
    subtitle?: string;
}>();

watchEffect(() => {
    pageHeading.title = props.title;
    pageHeading.subtitle = props.subtitle;
});
</script>

<template>
    <Head :title="props.title" />
    <slot />
</template>
