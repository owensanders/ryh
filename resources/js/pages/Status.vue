<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PlatformStatus, ServiceState } from '@/types/househub';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{ status: PlatformStatus }>();

// Matches the server-side cache, so each refresh can bring fresh results.
const REFRESH_MS = 60_000;

const dot: Record<ServiceState, string> = {
    operational: 'var(--hh-mint)',
    degraded: 'var(--hh-sun)',
    down: 'var(--hh-coral)',
};

const headline: Record<ServiceState, { title: string; body: string }> = {
    operational: { title: 'All systems operational', body: 'Everything in HouseHub is running normally.' },
    degraded: {
        title: 'Some features are degraded',
        body: "HouseHub is up, but part of it is slow or unavailable. We're on it — your household's information is safe.",
    },
    down: {
        title: 'Some features are down',
        body: "Part of HouseHub isn't working right now. We're working to restore it — your household's information is safe.",
    },
};

const checkedAt = computed(() =>
    new Date(props.status.checkedAt).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
);

let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => router.reload({ only: ['status'] }), REFRESH_MS);
});

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <Head title="Status" />

    <PublicLayout>
        <div class="mx-auto max-w-[760px] px-5 pb-20 pt-12 sm:px-8 lg:pt-16">
            <h1 class="text-[34px] font-black leading-[1.05] tracking-[-0.035em] sm:text-[44px]">Platform status</h1>

            <section
                class="mt-8 flex items-start gap-4 rounded-[22px] border border-hh-line bg-hh-panel p-6 sm:p-7"
                role="status"
                aria-live="polite"
                :data-overall="status.overall"
            >
                <span class="mt-1.5 h-3.5 w-3.5 flex-none rounded-full" :style="{ background: dot[status.overall] }" aria-hidden="true"></span>
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight">{{ headline[status.overall].title }}</h2>
                    <p class="mt-1 text-[15px] leading-relaxed text-hh-ink2">{{ headline[status.overall].body }}</p>
                </div>
            </section>

            <ul class="mt-6 divide-y divide-hh-line rounded-[22px] border border-hh-line bg-hh-card">
                <li
                    v-for="service in status.services"
                    :key="service.name"
                    class="flex items-center gap-4 px-5 py-4 sm:px-6"
                    :data-state="service.state"
                >
                    <div class="min-w-0 flex-1">
                        <div class="font-bold">{{ service.name }}</div>
                        <div class="text-[13.5px] text-hh-ink3">{{ service.description }}</div>
                    </div>
                    <span class="flex flex-none items-center gap-2 text-sm font-semibold">
                        <span class="h-2.5 w-2.5 rounded-full" :style="{ background: dot[service.state] }" aria-hidden="true"></span>
                        {{ service.stateLabel }}
                    </span>
                </li>
            </ul>

            <p class="mt-4 text-[13px] text-hh-ink3">
                Last checked at {{ checkedAt }}. This page refreshes itself every minute. Still having trouble?
                <Link :href="route('support')" class="font-semibold text-hh-ink underline underline-offset-2">Contact support</Link>.
            </p>
        </div>
    </PublicLayout>
</template>
