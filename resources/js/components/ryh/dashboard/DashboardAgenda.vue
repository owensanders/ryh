<script setup lang="ts">
import type { AgendaGroupData } from '@/types/generated';

/** The next three days in the calendar. */
defineProps<{ agenda: AgendaGroupData[] }>();
</script>

<template>
    <section aria-label="Coming up" class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <div class="mb-3.5 flex items-center gap-3">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Coming up</h3>
            <span class="ml-auto text-xs text-ryh-ink3">Next 3 days</span>
        </div>

        <div class="flex flex-col gap-3">
            <div v-for="group in agenda" :key="group.label">
                <div class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.09em] text-ryh-ink3">{{ group.label }}</div>
                <div class="flex flex-col gap-[5px]">
                    <div
                        v-for="entry in group.items"
                        :key="entry.id"
                        class="flex items-center gap-3 rounded-[13px] bg-ryh-sunk px-3 py-2.5 transition hover:translate-x-[3px]"
                    >
                        <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: entry.colour }"></span>
                        <span class="w-[50px] flex-none font-mono text-[11.5px] text-ryh-ink2">{{ entry.time }}</span>
                        <span class="flex-1 text-[13.5px] font-medium">{{ entry.title }}</span>
                        <span class="text-[11.5px] text-ryh-ink3">{{ entry.who }}</span>
                    </div>
                </div>
            </div>
            <p v-if="agenda.length === 0" class="text-sm text-ryh-ink3">Nothing in the diary.</p>
        </div>
    </section>
</template>
