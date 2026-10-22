<script setup lang="ts">
import { dial } from '@/lib/ryh';
import type { MemberScoreData } from '@/types/generated';

/** How far each member is through their chores this week. */
defineProps<{ scores: MemberScoreData[] }>();
</script>

<template>
    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="score in scores"
            :key="score.member.id"
            class="flex items-center gap-3.5 rounded-[20px] border border-ryh-line bg-ryh-card px-[18px] py-4"
        >
            <div
                class="grid h-[60px] w-[60px] flex-none place-items-center rounded-full transition-[background] duration-500"
                :style="{ background: dial(score.percentage, score.member.colour) }"
            >
                <div class="grid h-[46px] w-[46px] place-items-center rounded-full bg-ryh-card text-xs font-extrabold">
                    {{ score.member.initials }}
                </div>
            </div>
            <div>
                <div class="text-[14.5px] font-extrabold">{{ score.member.name.split(' ')[0] }}</div>
                <div class="mt-[3px] text-[12.5px] text-ryh-ink3">{{ score.done }} of {{ score.total }} this week</div>
            </div>
        </div>
    </div>
</template>
