<script setup lang="ts">
import Card from '@/components/ui/Card.vue';
import type { PlannedMealData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';

/** Tonight's dinner from the meal plan, if there is one. */
defineProps<{ tonight: PlannedMealData | null }>();
</script>

<template>
    <Card as="section" aria-label="Tonight's dinner" class="transition hover:-translate-y-0.5 hover:shadow-ryh">
        <div class="mb-3.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Tonight's dinner</h3>
            <span class="text-xs text-ryh-ink3">Meal plan</span>
            <Link :href="route('meals.index')" class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75">
                Meal planner →
            </Link>
        </div>

        <div v-if="tonight" class="flex flex-col gap-4 sm:flex-row">
            <div class="flex h-[132px] w-full flex-none flex-col justify-end rounded-[18px] bg-ryh-coral p-4 sm:h-[172px] sm:w-[172px]">
                <div
                    class="line-clamp-4 hyphens-auto break-words font-extrabold leading-[1.1] tracking-[-0.025em] text-white"
                    :class="tonight.name.length > 24 ? 'text-[20px]' : 'text-[22px] sm:text-[26px]'"
                    :title="tonight.name"
                >
                    {{ tonight.name }}
                </div>
            </div>
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex flex-wrap gap-[7px]">
                    <span
                        v-for="chip in [...tonight.tags, tonight.duration, tonight.difficulty].filter(Boolean)"
                        :key="chip as string"
                        class="rounded-[9px] bg-ryh-soft px-2.5 py-[5px] text-xs font-semibold text-ryh-ink2"
                    >
                        {{ chip }}
                    </span>
                </div>
                <p class="mt-3 text-sm leading-[1.55] text-ryh-ink2">{{ tonight.description }}</p>
                <div class="mt-auto flex items-center gap-3 pt-3.5">
                    <div v-if="tonight.cook" class="flex items-center gap-2">
                        <div
                            class="grid h-7 w-7 place-items-center rounded-[9px] text-[11px] font-extrabold text-[#0E1A2B]"
                            :style="{ background: tonight.cook.colour }"
                        >
                            {{ tonight.cook.initials }}
                        </div>
                        <span class="text-[13px] text-ryh-ink2"> {{ tonight.cook.name.split(' ')[0] }} is cooking </span>
                    </div>
                    <Link
                        :href="route('meals.index')"
                        class="ml-auto flex h-[42px] flex-none items-center whitespace-nowrap rounded-[13px] bg-ryh-coral px-5 text-[13.5px] font-bold text-white transition hover:-translate-y-0.5"
                    >
                        View recipe
                    </Link>
                </div>
            </div>
        </div>
        <p v-else class="py-10 text-center text-sm text-ryh-ink3">Nothing planned for tonight yet.</p>
    </Card>
</template>
