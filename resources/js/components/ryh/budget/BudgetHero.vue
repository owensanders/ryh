<script setup lang="ts">
import { percentOf } from '@/lib/ryh';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    month: string;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    daysLeftLabel: string;
    leftToSpend: string;
    budgetedLabel: string;
    perDay: string;
    budgetedPence: number;
    incomeTotalPence: number;
    incomeTotal: string;
}>();

const assignedPct = computed(() => percentOf(props.budgetedPence, props.incomeTotalPence));
const isCurrentMonth = computed(() => props.month === new Date().toISOString().slice(0, 7));
</script>

<template>
    <section
        class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-5 text-ryh-onhero sm:p-6 lg:flex-row"
        style="background: linear-gradient(118deg, var(--ryh-hero1) 0%, var(--ryh-hero2) 100%)"
    >
        <div class="absolute -top-24 right-52 h-72 w-72 rounded-full bg-ryh-coral opacity-20 blur-3xl"></div>

        <div class="relative min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2.5">
                <Link
                    :href="route('budget.index', { month: previousMonth })"
                    class="grid h-8 w-8 place-items-center rounded-[10px] border border-ryh-heroline bg-ryh-herofilm text-[13px]"
                >
                    ‹
                </Link>
                <span class="text-[13px] font-bold uppercase tracking-[0.08em] text-ryh-onhero2">{{ monthLabel }}</span>
                <Link
                    :href="route('budget.index', { month: nextMonth })"
                    class="grid h-8 w-8 place-items-center rounded-[10px] border border-ryh-heroline bg-ryh-herofilm text-[13px]"
                >
                    ›
                </Link>
                <Link v-if="!isCurrentMonth" :href="route('budget.index')" class="ml-1.5 text-[12.5px] text-ryh-onhero2"> This month </Link>
                <span class="ml-1.5 text-xs text-ryh-onhero2">{{ daysLeftLabel }}</span>
            </div>

            <div class="mt-3.5 text-[15px] text-ryh-onhero2">Left to spend this month</div>
            <div class="mt-1 text-[44px] font-black leading-none tracking-[-0.04em] sm:text-[52px]">{{ leftToSpend }}</div>

            <div class="mt-3.5 flex h-3 max-w-[560px] overflow-hidden rounded-lg bg-ryh-herofilm">
                <span class="bg-ryh-coral" :style="{ width: `${assignedPct}%` }"></span>
            </div>

            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-ryh-onhero2">
                <span
                    ><b class="text-ryh-onhero">{{ budgetedLabel }}</b> assigned</span
                >
                <span
                    ><b class="text-ryh-onhero">{{ perDay }}</b> a day to stay on track</span
                >
            </div>
        </div>

        <div class="relative grid w-full flex-none grid-cols-2 gap-2.5 lg:flex lg:w-[230px] lg:flex-col">
            <div class="flex-1 rounded-2xl border border-ryh-heroline bg-ryh-herofilm p-4">
                <div class="text-[11px] font-bold uppercase tracking-[0.09em] text-ryh-onhero2">Money in</div>
                <div class="mt-1.5 text-[22px] font-extrabold tracking-[-0.03em]">{{ incomeTotal }}</div>
            </div>
            <div class="flex-1 rounded-2xl border border-ryh-heroline bg-ryh-herofilm p-4">
                <div class="text-[11px] font-bold uppercase tracking-[0.09em] text-ryh-onhero2">Money out</div>
                <div class="mt-1.5 text-[22px] font-extrabold tracking-[-0.03em]">{{ budgetedLabel }}</div>
            </div>
        </div>
    </section>
</template>
