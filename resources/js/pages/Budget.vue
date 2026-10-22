<script setup lang="ts">
import BudgetCategories from '@/components/ryh/budget/BudgetCategories.vue';
import BudgetHero from '@/components/ryh/budget/BudgetHero.vue';
import BudgetIncome from '@/components/ryh/budget/BudgetIncome.vue';
import BudgetOverview from '@/components/ryh/budget/BudgetOverview.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { BudgetCategoryData, BudgetMonthSummaryData, IncomeSourceData } from '@/types/generated';

defineOptions({ layout: RyhShell });

defineProps<{
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
    income: IncomeSourceData[];
    incomeTotal: string;
    categories: BudgetCategoryData[];
    history: BudgetMonthSummaryData[];
}>();
</script>

<template>
    <RyhLayout title="Budget" :subtitle="monthLabel">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <BudgetHero
                :month="month"
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
