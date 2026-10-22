<script setup lang="ts">
import LandingCta from '@/components/landing/LandingCta.vue';
import LandingFeatures from '@/components/landing/LandingFeatures.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingPhone from '@/components/landing/LandingPhone.vue';
import LandingPricing from '@/components/landing/LandingPricing.vue';
import LandingPromises from '@/components/landing/LandingPromises.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PlanData } from '@/types/generated';
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps<{ plan: PlanData }>();

onMounted(() => {
    // The header's links from other pages (e.g. /#pricing) load this page fresh.
    // The browser looks for the section before it has rendered, and Inertia then
    // restores the scroll position to the top before firing `navigate`, so go to
    // the section on that first `navigate`.
    if (window.location.hash) {
        const stopListening = router.on('navigate', () => {
            stopListening();
            document.getElementById(window.location.hash.slice(1))?.scrollIntoView();
        });
    }
});
</script>

<template>
    <Head title="RYH — your home, organised" />

    <PublicLayout>
        <LandingHero :plan="plan" />
        <LandingFeatures />
        <LandingPhone />
        <LandingPricing :plan="plan" />
        <LandingPromises />
        <LandingCta :plan="plan" />
    </PublicLayout>
</template>
