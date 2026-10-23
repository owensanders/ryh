<script setup lang="ts">
import { dateLine, greeting } from '@/lib/clock';
import type { DashboardData } from '@/types/generated';

/** The greeting, streak and who's in the household. */
defineProps<{ dashboard: DashboardData }>();
</script>

<template>
    <section
        class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-6 text-ryh-onhero lg:flex-row"
        style="background: linear-gradient(118deg, var(--ryh-hero1) 0%, var(--ryh-hero2) 100%)"
    >
        <div class="absolute -top-24 right-44 h-72 w-72 rounded-full bg-ryh-coral opacity-[0.22] blur-3xl"></div>

        <div class="relative min-w-0 flex-1">
            <div
                class="inline-flex h-[26px] items-center gap-2 rounded-lg border border-ryh-heroline bg-ryh-herofilm px-[11px] text-[11.5px] font-bold uppercase tracking-[0.06em]"
            >
                🔥 {{ dashboard.streakDays }} day streak
            </div>
            <div class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-[-0.03em]">{{ greeting() }}, {{ dashboard.firstName }}</div>
            <div class="mt-2 text-sm text-ryh-onhero2">{{ dateLine() }} · {{ dashboard.summaryLine }}</div>

            <div class="mt-5 flex flex-wrap gap-2.5">
                <div
                    v-for="person in dashboard.family"
                    :key="person.id"
                    class="flex h-[42px] items-center gap-2.5 rounded-[13px] border border-ryh-heroline bg-ryh-herofilm py-0 pl-1 pr-3.5"
                >
                    <div
                        class="grid h-[34px] w-[34px] place-items-center rounded-[10px] text-xs font-extrabold text-[#0E1A2B]"
                        :style="{ background: person.colour }"
                    >
                        {{ person.initials }}
                    </div>
                    <div class="flex flex-col leading-[1.15]">
                        <span class="text-[13px] font-bold">{{ person.name.split(' ')[0] }}</span>
                        <span class="text-[11px] text-ryh-onhero2">{{ person.status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
