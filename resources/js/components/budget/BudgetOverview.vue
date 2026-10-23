<script setup lang="ts">
import Card from '@/components/ui/Card.vue';
import { donut, percentOf } from '@/lib/ryh';
import type { BudgetMonthSummaryData } from '@/types/generated';
import { computed } from 'vue';

/** Income against what's assigned, and the last three months side by side. */
const props = defineProps<{
    month: string;
    leftToSpend: string;
    incomeTotal: string;
    budgetedLabel: string;
    incomeTotalPence: number;
    budgetedPence: number;
    history: BudgetMonthSummaryData[];
}>();

const incomePct = computed(() => percentOf(props.incomeTotalPence, props.incomeTotalPence + props.budgetedPence));
const incomeExpenseDonut = computed(() => donut(incomePct.value, 'var(--ryh-mint)', 'var(--ryh-coral)'));

const maxHistoryPence = computed(() => Math.max(1, ...props.history.map((entry) => entry.totalPence)));

function historyBarHeight(entry: BudgetMonthSummaryData): number {
    return Math.round((entry.totalPence / maxHistoryPence.value) * 100);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Income vs. expenses -->
        <Card as="section" class="flex flex-col">
            <h3 class="mb-3.5 text-[15px] font-extrabold tracking-[-0.01em]">Income vs. expenses</h3>

            <div class="flex flex-col gap-5 min-[420px]:flex-row min-[420px]:items-center">
                <div class="grid h-[120px] w-[120px] flex-none place-items-center rounded-full" :style="{ background: incomeExpenseDonut }">
                    <div class="flex h-[92px] w-[92px] flex-col items-center justify-center rounded-full bg-ryh-card text-center">
                        <span class="text-[11px] text-ryh-ink3">Left over</span>
                        <span class="text-[17px] font-extrabold tracking-[-0.02em]">{{ leftToSpend }}</span>
                    </div>
                </div>

                <div class="flex w-full flex-1 flex-col gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <span class="h-2.5 w-2.5 flex-none rounded-[3px]" style="background: var(--ryh-mint)"></span>
                        <span class="flex-1 text-[13px] font-medium">Income</span>
                        <span class="font-mono text-[13px]">{{ incomeTotal }}</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="h-2.5 w-2.5 flex-none rounded-[3px]" style="background: var(--ryh-coral)"></span>
                        <span class="flex-1 text-[13px] font-medium">Assigned</span>
                        <span class="font-mono text-[13px]">{{ budgetedLabel }}</span>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Last 3 months -->
        <Card as="section" class="flex flex-col">
            <h3 class="mb-3.5 text-[15px] font-extrabold tracking-[-0.01em]">Last 3 months</h3>

            <div class="flex h-[120px] flex-1 items-end justify-around gap-4">
                <div v-for="entry in history" :key="entry.month" class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                    <span class="font-mono text-[12px] text-ryh-ink3">{{ entry.total }}</span>
                    <div
                        class="w-full max-w-10 rounded-t-md transition-[height]"
                        :class="entry.month === month ? 'bg-ryh-coral' : 'bg-ryh-sunk'"
                        :style="{ height: `${historyBarHeight(entry)}%` }"
                    ></div>
                    <span class="text-[12px] font-semibold text-ryh-ink3">{{ entry.monthLabel }}</span>
                </div>
            </div>
        </Card>
    </div>
</template>
