<script setup lang="ts">
import DashboardAgenda from '@/components/ryh/dashboard/DashboardAgenda.vue';
import DashboardBudget from '@/components/ryh/dashboard/DashboardBudget.vue';
import DashboardChores from '@/components/ryh/dashboard/DashboardChores.vue';
import DashboardHero from '@/components/ryh/dashboard/DashboardHero.vue';
import DashboardShopping from '@/components/ryh/dashboard/DashboardShopping.vue';
import DashboardTonight from '@/components/ryh/dashboard/DashboardTonight.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import { dateLine } from '@/lib/clock';
import type { DashboardData } from '@/types/generated';

defineOptions({ layout: RyhShell });

defineProps<{ dashboard: DashboardData }>();
</script>

<template>
    <RyhLayout title="Dashboard" :subtitle="dateLine()">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <DashboardHero :dashboard="dashboard" />

            <div class="grid grid-cols-1 items-stretch gap-4 xl:grid-cols-[1.45fr_1fr]">
                <DashboardTonight :tonight="dashboard.tonight" />
                <DashboardShopping :list="dashboard.shoppingList" />
            </div>

            <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <DashboardChores :chores="dashboard.chores" :progress="dashboard.choreProgress" />
                <DashboardAgenda :agenda="dashboard.agenda" />
                <DashboardBudget :budget="dashboard.budget" />
            </div>
        </div>
    </RyhLayout>
</template>
