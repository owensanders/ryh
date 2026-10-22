<script setup lang="ts">
import type { HouseData } from '@/types/generated';

defineProps<{ house: HouseData }>();
</script>

<template>
    <section
        class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-5 text-ryh-onhero sm:p-6 md:flex-row md:items-center"
        style="background: linear-gradient(118deg, var(--ryh-hero1) 0%, var(--ryh-hero2) 100%)"
    >
        <div class="absolute -top-24 right-52 h-72 w-72 rounded-full bg-ryh-coral opacity-20 blur-3xl"></div>

        <div class="relative min-w-0 flex-1">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.09em] text-ryh-onhero2">Household</div>
            <div class="mt-2 break-words text-[32px] font-black leading-[1.05] tracking-[-0.03em] sm:text-4xl">
                {{ house.houseName }}
            </div>
            <div class="mt-2 text-sm text-ryh-onhero2">created {{ house.houseCreated }}</div>
        </div>

        <div class="relative grid grid-cols-3 gap-2 sm:gap-2.5 md:flex">
            <div
                v-for="stat in house.houseStats"
                :key="stat.label"
                class="min-w-0 rounded-2xl border border-ryh-heroline bg-ryh-herofilm p-3 sm:p-4 md:w-[132px]"
            >
                <div class="text-[10px] font-bold uppercase leading-tight tracking-[0.08em] text-ryh-onhero2 sm:text-[11px]">
                    {{ stat.label }}
                </div>
                <div class="mt-1 text-2xl font-extrabold tracking-[-0.03em]">{{ stat.value }}</div>
            </div>
        </div>
    </section>
</template>
