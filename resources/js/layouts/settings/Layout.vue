<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const tabs = [
    { label: 'Profile', route: 'profile.edit' },
    { label: 'Password', route: 'password.edit' },
    { label: 'Appearance', route: 'appearance' },
    { label: 'Notifications', route: 'notifications.edit' },
    { label: 'Households', route: 'households.edit' },
];
</script>

<template>
    <div class="flex animate-ryh-rise flex-col gap-5">
        <nav class="flex flex-wrap gap-1.5 rounded-[15px] border border-ryh-line bg-ryh-panel p-1.5">
            <Link
                v-for="tab in tabs"
                :key="tab.route"
                :href="route(tab.route)"
                class="rounded-[11px] px-4 py-2 text-[13px] transition-colors"
                :class="route().current(tab.route) ? 'bg-ryh-card font-bold text-ryh-ink shadow-ryh' : 'font-medium text-ryh-ink2 hover:bg-ryh-soft'"
            >
                {{ tab.label }}
            </Link>
        </nav>

        <div class="flex max-w-2xl flex-col gap-5">
            <slot />
        </div>
    </div>
</template>
