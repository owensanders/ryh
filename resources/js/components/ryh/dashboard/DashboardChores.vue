<script setup lang="ts">
import { useOptimistic } from '@/composables/useOptimistic';
import { dial, tickStyle } from '@/lib/ryh';
import type { ChoreData, ChoreProgressData } from '@/types/generated';
import { Link, router } from '@inertiajs/vue3';

/** Today's chores and how far through them the household is, ticked off at once. */
const props = defineProps<{ chores: ChoreData[]; progress: ChoreProgressData }>();

// Ticks show at once; the server's chores replace these when it answers.
const { state: shown, apply: updateShown } = useOptimistic(() => ({ chores: props.chores, progress: props.progress }));

function toggleChore(chore: ChoreData): void {
    if (!chore.can.toggle) {
        return;
    }

    updateShown(
        ({ chores, progress }) => {
            const ticked = chores.find((candidate) => candidate.id === chore.id);

            if (ticked) {
                ticked.done = !ticked.done;
                progress.done += ticked.done ? 1 : -1;
                progress.percentage = progress.total === 0 ? 0 : Math.round((progress.done / progress.total) * 100);
            }
        },
        (callbacks) => router.patch(route('chores.toggle', { chore: chore.id }), {}, { preserveScroll: true, ...callbacks }),
    );
}
</script>

<template>
    <section aria-label="Today's chores" class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <div class="mb-3.5 flex items-center gap-3">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Today's chores</h3>
            <Link :href="route('chores.index')" class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75">
                Board →
            </Link>
        </div>

        <div class="mb-3.5 flex items-center gap-4">
            <div
                class="grid h-[74px] w-[74px] flex-none place-items-center rounded-full transition-[background] duration-500"
                :style="{ background: dial(shown.progress.percentage) }"
            >
                <div class="flex h-[57px] w-[57px] items-center justify-center rounded-full bg-ryh-card text-[16.5px] font-extrabold">
                    {{ shown.progress.percentage }}%
                </div>
            </div>
            <div class="text-[13px] leading-[1.55] text-ryh-ink2">
                {{ shown.progress.done }} of {{ shown.progress.total }} done today.<br />
                Tap a chore to tick it off.
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <button
                v-for="chore in shown.chores.slice(0, 4)"
                :key="chore.id"
                type="button"
                :disabled="!chore.can.toggle"
                class="flex min-h-[48px] w-full items-center gap-3 rounded-[14px] border border-ryh-line px-3 text-left transition hover:translate-x-0.5 disabled:cursor-not-allowed"
                :class="chore.done ? 'bg-ryh-sunk' : 'bg-ryh-card'"
                @click="toggleChore(chore)"
            >
                <span
                    class="grid h-[21px] w-[21px] flex-none place-items-center rounded-full border-[1.5px] text-[11px] text-[#0E1A2B]"
                    :style="tickStyle(chore.done)"
                >
                    {{ chore.done ? '✓' : '' }}
                </span>
                <span class="flex-1 text-sm font-medium" :class="chore.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                    {{ chore.name }}
                </span>
                <span
                    v-if="chore.assignee"
                    class="grid h-[26px] w-[26px] flex-none place-items-center rounded-lg text-[10px] font-extrabold text-[#0E1A2B]"
                    :style="{ background: chore.assignee.colour }"
                >
                    {{ chore.assignee.initials }}
                </span>
            </button>
        </div>
    </section>
</template>
