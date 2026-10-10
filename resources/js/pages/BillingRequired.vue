<script setup lang="ts">
import RyhLogo from '@/components/ryh/RyhLogo.vue';
import { logout } from '@/lib/logout';
import { formatPlanPrice } from '@/lib/ryh';
import type { Plan } from '@/types/ryh';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    plan: Plan;
    isOwner: boolean;
    paymentIssue: boolean;
    billingConfigured: boolean;
}>();

const page = usePage();
const interval = ref<'monthly' | 'annual'>('monthly');

function subscribe(): void {
    router.post(route('billing.subscribe'), { interval: interval.value });
}
</script>

<template>
    <Head title="Subscribe to continue" />

    <div class="flex min-h-svh items-center justify-center bg-ryh-bg p-4 text-ryh-ink transition-colors">
        <div class="w-full max-w-[440px] animate-ryh-rise rounded-3xl border border-ryh-line bg-ryh-shell p-8 shadow-ryh">
            <RyhLogo class="h-9" />

            <h1 class="mt-6 text-[26px] font-black leading-tight tracking-[-0.03em]">
                {{ props.paymentIssue ? 'Your last payment didn’t go through' : 'Your free month has ended' }}
            </h1>

            <template v-if="!props.billingConfigured">
                <p class="mt-2.5 text-[14.5px] leading-relaxed text-ryh-ink2">
                    Billing isn't set up yet on this household — please contact support to keep using RYH.
                </p>
            </template>
            <template v-else-if="props.isOwner && props.paymentIssue">
                <p class="mt-2.5 text-[14.5px] leading-relaxed text-ryh-ink2">
                    Update your card and we’ll retry the payment — your subscription carries on as before.
                </p>

                <button
                    type="button"
                    class="mt-4 flex h-[50px] w-full items-center justify-center rounded-[14px] bg-ryh-coral text-[15px] font-bold text-white transition hover:-translate-y-0.5"
                    @click="subscribe"
                >
                    Update your card
                </button>
                <p v-if="page.props.errors.plan" class="mt-2 text-[12.5px] text-ryh-coral">{{ page.props.errors.plan }}</p>
            </template>
            <template v-else-if="props.isOwner">
                <p class="mt-2.5 text-[14.5px] leading-relaxed text-ryh-ink2">Subscribe to keep everyone in the house up and running.</p>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="flex h-[46px] flex-col items-center justify-center rounded-[12px] border-[1.5px] text-[13.5px] font-bold transition"
                        :class="interval === 'monthly' ? 'bg-ryh-coral/10 border-ryh-coral text-ryh-coral' : 'border-ryh-line text-ryh-ink2'"
                        @click="interval = 'monthly'"
                    >
                        {{ formatPlanPrice(props.plan.priceMonthly) }}/month
                    </button>
                    <button
                        type="button"
                        class="flex h-[46px] flex-col items-center justify-center rounded-[12px] border-[1.5px] text-[13.5px] font-bold transition"
                        :class="interval === 'annual' ? 'bg-ryh-coral/10 border-ryh-coral text-ryh-coral' : 'border-ryh-line text-ryh-ink2'"
                        @click="interval = 'annual'"
                    >
                        {{ formatPlanPrice(props.plan.priceAnnual) }}/year
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-4 flex h-[50px] w-full items-center justify-center rounded-[14px] bg-ryh-coral text-[15px] font-bold text-white transition hover:-translate-y-0.5"
                    @click="subscribe"
                >
                    Subscribe now
                </button>
                <p v-if="page.props.errors.plan" class="mt-2 text-[12.5px] text-ryh-coral">{{ page.props.errors.plan }}</p>
            </template>
            <template v-else>
                <p class="mt-2.5 text-[14.5px] leading-relaxed text-ryh-ink2">
                    Ask the household owner to subscribe ({{ formatPlanPrice(props.plan.priceMonthly) }}/month or
                    {{ formatPlanPrice(props.plan.priceAnnual) }}/year) to keep everyone up and running.
                </p>
            </template>

            <button
                type="button"
                class="mt-4 flex h-[44px] w-full items-center justify-center rounded-[14px] bg-ryh-soft text-[14px] font-semibold text-ryh-ink2 transition-colors hover:bg-ryh-line"
                @click="logout"
            >
                Log out
            </button>
        </div>
    </div>
</template>
