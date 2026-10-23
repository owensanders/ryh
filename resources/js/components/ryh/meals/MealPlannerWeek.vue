<script setup lang="ts">
import IconButton from '@/components/ryh/ui/IconButton.vue';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { useMealEditor } from '@/composables/useMealEditor';
import { PLANNED_DAY_PROPS, tint } from '@/lib/ryh';
import type { PlannerDayData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

/** The week's days and their meals. Drag a meal onto another day to reschedule it. */
defineProps<{ days: PlannerDayData[] }>();

const can = useHouseholdAbilities();
const { openNew, openEdit } = useMealEditor();

const draggingId = ref<number | null>(null);
const dropTarget = ref<string | null>(null);

function startDrag(event: DragEvent, id: number): void {
    draggingId.value = id;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function endDrag(): void {
    draggingId.value = null;
    dropTarget.value = null;
}

function drop(date: string): void {
    const meal = draggingId.value;
    endDrag();

    if (meal === null) {
        return;
    }

    router.patch(route('meals.reschedule', { meal }), { planned_on: date }, { preserveScroll: true, only: PLANNED_DAY_PROPS });
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        <div
            v-for="day in days"
            :key="day.date"
            class="group flex min-h-[112px] flex-col gap-2.5 rounded-[18px] border border-dashed p-3 transition xl:min-h-[400px]"
            :style="{
                borderColor: dropTarget === day.date ? 'var(--ryh-mint)' : 'var(--ryh-line)',
                background: dropTarget === day.date ? 'var(--ryh-soft)' : day.isToday ? 'var(--ryh-sunk)' : 'transparent',
            }"
            @dragover.prevent="dropTarget = day.date"
            @dragleave="dropTarget === day.date && (dropTarget = null)"
            @drop.prevent="drop(day.date)"
        >
            <div class="flex items-baseline gap-1.5">
                <span class="text-[13px] font-extrabold">{{ day.dayLabel }}</span>
                <span class="font-mono text-[11px] text-ryh-ink3">{{ day.dateLabel }}</span>
                <IconButton
                    :label="`Add a meal on ${day.dateLabel}`"
                    v-if="can?.createPlannedMeals"
                    class="ml-auto grid h-8 w-8 place-items-center rounded-md text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-ink [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100"
                    @click="openNew(day.date)"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                </IconButton>
            </div>

            <button
                v-for="meal in day.meals"
                :key="meal.id"
                type="button"
                :draggable="meal.can.update"
                class="overflow-hidden rounded-2xl border border-ryh-line bg-ryh-card text-left transition hover:-translate-y-[3px] hover:shadow-ryh"
                :class="meal.can.update ? 'cursor-grab' : ''"
                :style="{ opacity: draggingId === meal.id ? 0.35 : 1 }"
                @dragstart="startDrag($event, meal.id)"
                @dragend="endDrag"
                @click="meal.can.update && openEdit(meal, day.date)"
            >
                <div class="flex min-h-[76px] flex-col justify-between gap-1 px-3 py-2.5" :style="{ background: tint(meal.tint) }">
                    <span class="self-end text-[10px] font-extrabold uppercase tracking-[0.08em] text-ryh-ontint opacity-60">
                        {{ meal.slot }}
                    </span>
                    <span
                        class="line-clamp-3 hyphens-auto break-words text-[14px] font-extrabold leading-[1.2] tracking-[-0.02em] text-ryh-ontint"
                        :title="meal.name"
                    >
                        {{ meal.name }}
                    </span>
                </div>
                <div class="flex flex-col gap-[7px] px-3 py-2.5">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="rounded-md bg-ryh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-ryh-ink2">
                            {{ meal.duration }}
                        </span>
                        <span class="rounded-md bg-ryh-soft px-1.5 py-[3px] text-[10.5px] font-semibold text-ryh-ink2">
                            {{ meal.difficulty }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            v-if="meal.cook"
                            class="grid h-[21px] w-[21px] place-items-center rounded-[7px] text-[9px] font-extrabold text-[#0E1A2B]"
                            :style="{ background: meal.cook.colour }"
                        >
                            {{ meal.cook.initials }}
                        </span>
                        <span class="text-[11px] text-ryh-ink3">{{ meal.missingLabel }}</span>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>
