<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const tabs = [
    { label: 'Profile', route: 'profile.edit' },
    { label: 'Password', route: 'password.edit' },
    { label: 'Appearance', route: 'appearance' },
    { label: 'Households', route: 'households.edit' },
];
</script>

<template>
    <div class="flex animate-hh-rise flex-col gap-5">
        <nav class="flex flex-wrap gap-1.5 rounded-[15px] border border-hh-line bg-hh-panel p-1.5">
            <Link
                v-for="tab in tabs"
                :key="tab.route"
                :href="route(tab.route)"
                class="rounded-[11px] px-4 py-2 text-[13px] transition-colors"
                :class="route().current(tab.route) ? 'bg-hh-card font-bold text-hh-ink shadow-hh' : 'font-medium text-hh-ink2 hover:bg-hh-soft'"
            >
                {{ tab.label }}
            </Link>
        </nav>

        <div class="flex max-w-2xl flex-col gap-5">
            <slot />
        </div>
    </div>
</template>
