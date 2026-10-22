<script setup lang="ts">
import type { HouseData } from '@/types/generated';

defineProps<{ house: HouseData }>();
</script>

<template>
    <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">What each role can do</h3>
        <div class="mt-3 flex flex-col gap-2.5">
            <div v-for="role in house.roles" :key="role.name" class="rounded-2xl bg-ryh-sunk p-3.5">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-sm" :style="{ background: role.colour }"></span>
                    <span class="text-[13.5px] font-bold">{{ role.name }}</span>
                    <span class="ml-auto text-[11.5px] text-ryh-ink3">{{ role.count }} {{ role.count === 1 ? 'person' : 'people' }}</span>
                </div>
                <div class="mt-1.5 text-[12.5px] leading-relaxed text-ryh-ink2">{{ role.body }}</div>
            </div>
        </div>
    </section>
</template>
