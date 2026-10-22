<script setup lang="ts">
import { formatPlanPrice, HERO_GRADIENT } from '@/lib/ryh';
import type { PlanData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';

defineProps<{ plan: PlanData }>();
</script>

<template>
    <section id="pricing" class="scroll-mt-6 px-5 pb-6 pt-[76px] sm:px-8 lg:px-14">
        <div class="mb-6">
            <h2 class="text-[28px] font-black tracking-[-0.03em] sm:text-[34px]">One price, everything included</h2>
            <p class="mt-2.5 max-w-[560px] text-[15.5px] leading-relaxed text-ryh-ink2">
                {{ plan.sub }}
            </p>
        </div>

        <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[400px_minmax(0,1fr)]">
            <div class="relative flex flex-col overflow-hidden rounded-3xl p-8 text-ryh-onhero" :style="{ background: HERO_GRADIENT }">
                <div class="absolute -right-[70px] -top-20 h-[280px] w-[280px] rounded-full bg-ryh-coral opacity-20 blur-[46px]"></div>

                <span
                    class="relative self-start rounded-[9px] bg-ryh-coral px-2.5 py-[5px] text-[11.5px] font-extrabold uppercase tracking-[0.05em] text-white"
                >
                    First month free
                </span>

                <div class="relative mt-[22px] flex items-baseline gap-2">
                    <span class="text-[46px] font-black leading-none tracking-[-0.045em] sm:text-[58px]">{{
                        formatPlanPrice(plan.priceMonthly)
                    }}</span>
                    <span class="text-sm font-semibold text-ryh-onhero2">/month</span>
                </div>

                <div class="relative mt-1 text-[13.5px] font-semibold text-ryh-onhero2">or {{ formatPlanPrice(plan.priceAnnual) }}/year</div>

                <div class="relative mt-2.5 max-w-[290px] text-[14.5px] leading-relaxed text-ryh-onhero2">
                    {{ plan.sub }}
                </div>

                <Link
                    :href="route('register')"
                    class="relative mt-6 flex h-[54px] items-center justify-center rounded-[15px] bg-ryh-coral text-[15.5px] font-bold text-white transition hover:-translate-y-0.5"
                >
                    {{ plan.cta }}
                </Link>

                <div class="relative mt-auto pt-6 text-[13px] text-ryh-onhero2">{{ plan.note }}</div>
            </div>

            <div class="rounded-3xl border-[1.5px] border-ryh-line bg-ryh-card p-8">
                <div class="text-[12.5px] font-extrabold uppercase tracking-[0.09em] text-ryh-ink3">Everything included</div>
                <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-3.5 sm:grid-cols-2">
                    <div v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5">
                        <span
                            class="mt-0.5 grid h-[18px] w-[18px] flex-none place-items-center rounded-md bg-ryh-mint text-[10px] font-extrabold text-[#0E1A2B]"
                        >
                            ✓
                        </span>
                        <span class="text-sm leading-snug text-ryh-ink">{{ feature }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
