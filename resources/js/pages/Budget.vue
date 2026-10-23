<script setup lang="ts">
import BudgetCategories from '@/components/budget/BudgetCategories.vue';
import BudgetHero from '@/components/budget/BudgetHero.vue';
import BudgetIncome from '@/components/budget/BudgetIncome.vue';
import BudgetOverview from '@/components/budget/BudgetOverview.vue';
import Button from '@/components/ui/Button.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { BudgetCategoryData, BudgetMonthSummaryData, IncomeSourceData } from '@/types/generated';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: RyhShell });

const props = defineProps<{
    month: string;
    isCurrentMonth: boolean;
    recurringToBringIn: boolean;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    daysLeftLabel: string;
    leftToSpend: string;
    budgetedLabel: string;
    perDay: string;
    budgetedPence: number;
    incomeTotalPence: number;
    income: IncomeSourceData[];
    incomeTotal: string;
    categories: BudgetCategoryData[];
    history: BudgetMonthSummaryData[];
}>();

// Recurring items arrive on the 1st; a month ahead can have them brought in early.
function bringInRecurring(): void {
    router.post(route('budget.setUp'), { month: props.month }, { preserveScroll: true });
}
</script>

<template>
    <RyhLayout title="Budget" :subtitle="monthLabel">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <BudgetHero
                :month="month"
                :is-current-month="isCurrentMonth"
                :month-label="monthLabel"
                :previous-month="previousMonth"
                :next-month="nextMonth"
                :days-left-label="daysLeftLabel"
                :left-to-spend="leftToSpend"
                :budgeted-label="budgetedLabel"
                :per-day="perDay"
                :budgeted-pence="budgetedPence"
                :income-total-pence="incomeTotalPence"
                :income-total="incomeTotal"
            />

            <div
                v-if="recurringToBringIn"
                class="flex flex-wrap items-center gap-3 rounded-[18px] border border-ryh-line bg-ryh-panel px-4 py-3 text-[13.5px] text-ryh-ink2"
            >
                <span class="min-w-0 flex-1">Your recurring categories and income come into {{ monthLabel }} on the 1st.</span>
                <Button variant="secondary" class="w-auto" @click="bringInRecurring">Bring them in now</Button>
            </div>

            <BudgetOverview
                :month="month"
                :left-to-spend="leftToSpend"
                :income-total="incomeTotal"
                :budgeted-label="budgetedLabel"
                :income-total-pence="incomeTotalPence"
                :budgeted-pence="budgetedPence"
                :history="history"
            />

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(360px,1fr)_minmax(460px,1.4fr)]">
                <BudgetIncome :month="month" :income="income" :income-total="incomeTotal" />
                <BudgetCategories :month="month" :categories="categories" />
            </div>
        </div>
    </RyhLayout>
</template>
