<script setup lang="ts">
import RyhLogo from '@/components/ryh/RyhLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh items-center justify-center bg-ryh-bg p-3 text-ryh-ink transition-colors sm:p-7">
        <div class="w-full max-w-[420px] animate-ryh-rise rounded-[22px] border border-ryh-line bg-ryh-card p-[22px] shadow-ryh sm:p-7">
            <Link :href="route('home')" class="mb-6 flex" aria-label="RYH home">
                <RyhLogo class="h-9" />
            </Link>

            <h1 class="text-[19px] font-extrabold tracking-tight">{{ title }}</h1>
            <p v-if="description" class="mt-1 text-[13px] text-ryh-ink3">{{ description }}</p>

            <div class="mt-6">
                <slot />
            </div>
        </div>
    </div>
</template>
