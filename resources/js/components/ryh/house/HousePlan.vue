<script setup lang="ts">
import { formatPlanPrice } from '@/lib/ryh';
import type { HouseData } from '@/types/generated';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

/** The household's plan: trial, subscription or a failed payment, and what to do about it. */
defineProps<{ house: HouseData }>();

const page = usePage();
const interval = ref<'monthly' | 'annual'>('monthly');

function subscribe(): void {
    router.post(route('house.subscribe'), { interval: interval.value }, { preserveScroll: true });
}

function manageBilling(): void {
    router.post(route('billing.portal'), {}, { preserveScroll: true });
}
</script>

<template>
    <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Plan</h3>

        <div class="mt-3 rounded-2xl border border-ryh-line bg-ryh-sunk p-3.5">
            <template v-if="house.planStatus.subscribed">
                <div class="text-[13.5px] font-bold">
                    <template v-if="house.planStatus.interval === 'annual'">
                        Home · {{ formatPlanPrice(house.planStatus.priceAnnual) }}/year
                    </template>
                    <template v-else>Home · {{ formatPlanPrice(house.planStatus.priceMonthly) }}/month</template>
                </div>
                <div class="mt-0.5 text-[12px] text-ryh-ink3">
                    <template v-if="house.planStatus.endsLabel">Cancelled · access until {{ house.planStatus.endsLabel }}</template>
                    <template v-else>Change plan, update your card or cancel anytime.</template>
                </div>

                <button
                    type="button"
                    class="mt-3 flex h-[38px] w-full items-center justify-center rounded-[11px] border-[1.5px] border-ryh-line px-4 text-[13px] font-bold text-ryh-ink2 transition hover:-translate-y-0.5"
                    @click="manageBilling"
                >
                    {{ house.planStatus.endsLabel ? 'Resume or manage billing' : 'Manage billing' }}
                </button>
            </template>
            <template v-else>
                <template v-if="house.planStatus.onTrial">
                    <div class="text-[13.5px] font-bold">Free trial · ends {{ house.planStatus.trialEndsLabel }}</div>
                </template>
                <template v-else-if="house.planStatus.paymentIssue">
                    <div class="text-[13.5px] font-bold">Payment failed</div>
                    <div class="mt-0.5 text-[12px] text-ryh-ink3">Update your card and we’ll retry the payment.</div>
                </template>
                <template v-else>
                    <div class="text-[13.5px] font-bold">Trial ended</div>
                    <div class="mt-0.5 text-[12px] text-ryh-ink3">Subscribe to keep using RYH.</div>
                </template>

                <div v-if="!house.planStatus.paymentIssue" class="mt-3 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                        :class="interval === 'monthly' ? 'bg-ryh-coral/10 border-ryh-coral text-ryh-coral' : 'border-ryh-line text-ryh-ink2'"
                        @click="interval = 'monthly'"
                    >
                        {{ formatPlanPrice(house.planStatus.priceMonthly) }}/month
                    </button>
                    <button
                        type="button"
                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                        :class="interval === 'annual' ? 'bg-ryh-coral/10 border-ryh-coral text-ryh-coral' : 'border-ryh-line text-ryh-ink2'"
                        @click="interval = 'annual'"
                    >
                        {{ formatPlanPrice(house.planStatus.priceAnnual) }}/year
                    </button>
                </div>

                <button
                    type="button"
                    class="mt-2.5 flex h-[38px] w-full items-center justify-center rounded-[11px] bg-ryh-coral px-4 text-[13px] font-bold text-white transition hover:-translate-y-0.5"
                    @click="subscribe"
                >
                    <template v-if="house.planStatus.paymentIssue">Update your card</template>
                    <template v-else>{{ house.planStatus.onTrial ? 'Start subscription now' : 'Subscribe now' }}</template>
                </button>
            </template>
        </div>
        <p v-if="page.props.errors.plan" class="mt-2 text-[12.5px] text-ryh-coral">{{ page.props.errors.plan }}</p>
    </section>
</template>
