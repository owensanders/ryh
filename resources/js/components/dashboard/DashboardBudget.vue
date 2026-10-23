<script setup lang="ts">
import Card from '@/components/ui/Card.vue';
import type { BudgetSummaryData } from '@/types/generated';

/** This month's budget at a glance. */
defineProps<{ budget: BudgetSummaryData }>();
</script>

<template>
    <Card as="section" aria-label="Budget" class="flex flex-col">
        <div class="mb-1 flex items-center gap-3">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">{{ budget.monthLabel }} budget</h3>
            <span class="ml-auto text-xs text-ryh-ink3">{{ budget.daysLeft }} days left</span>
        </div>

        <div class="flex items-baseline gap-2.5">
            <span class="font-mono text-[40px] leading-[1.15]">{{ budget.budgeted }}</span>
            <span class="text-[13px] text-ryh-ink3">assigned</span>
        </div>

        <div class="my-3 flex h-2.5 overflow-hidden rounded-md bg-ryh-sunk">
            <span
                v-for="category in budget.categories"
                :key="category.id"
                :style="{ width: `${category.shareOfTotal}%`, background: category.colour }"
            ></span>
        </div>

        <div class="flex flex-col gap-3">
            <div v-for="category in budget.categories" :key="category.id" class="flex items-center gap-3">
                <span class="h-2 w-2 flex-none rounded-[3px]" :style="{ background: category.colour }"></span>
                <span class="w-24 flex-none text-[13px] font-medium">{{ category.label }}</span>
                <span class="ml-auto font-mono text-[12.5px]">{{ category.budgeted }}</span>
            </div>
        </div>
    </Card>
</template>
