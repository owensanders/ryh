<script setup lang="ts">
import { useCalendarEditor } from '@/composables/useCalendarEditor';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { eventsFor, isEditable, todayIso, VISIBLE_PER_CELL } from '@/lib/calendar';
import type { CalendarMonthData } from '@/types/generated';
import { Plus } from 'lucide-vue-next';

/** The month as a seven-column grid, on screens wide enough for it. */
defineProps<{ calendar: CalendarMonthData; filter: number | null }>();

const can = useHouseholdAbilities();
const { openNew, openEdit, expandedDay } = useCalendarEditor();
</script>

<template>
    <div class="hidden md:block">
        <div class="mb-1.5 grid grid-cols-7 gap-2">
            <div v-for="label in calendar.weekdayLabels" :key="label" class="text-center text-[11.5px] font-bold text-ryh-ink3">
                {{ label }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <div
                v-for="day in calendar.days"
                :key="day.date"
                class="group flex min-h-[116px] min-w-0 flex-col gap-1 rounded-[14px] border border-ryh-line p-2 transition"
                :class="[day.isToday ? 'bg-ryh-sunk' : 'bg-ryh-card', day.isCurrentMonth ? '' : 'opacity-50']"
            >
                <div class="flex items-center">
                    <span class="px-1 text-[12px] font-bold" :class="day.isToday ? 'text-ryh-coral' : 'text-ryh-ink2'">
                        {{ day.dateLabel }}
                    </span>
                    <button
                        v-if="can?.createCalendarEvents && day.date >= todayIso()"
                        type="button"
                        class="ml-auto grid h-7 w-7 place-items-center rounded-md text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-ink [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100"
                        :aria-label="`Add an event on ${day.dateLabel}`"
                        @click="openNew(day)"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    </button>
                </div>

                <button
                    v-for="event in eventsFor(day, filter).slice(0, VISIBLE_PER_CELL)"
                    :key="event.id"
                    type="button"
                    :disabled="!isEditable(event)"
                    class="flex items-center gap-1.5 bg-ryh-soft px-1.5 py-1 text-left transition hover:translate-x-[2px] disabled:opacity-50 disabled:hover:translate-x-0"
                    :class="[event.isSpanStart ? 'rounded-l-[9px]' : '', event.isSpanEnd ? 'rounded-r-[9px]' : '']"
                    @click="openEdit(event)"
                >
                    <span class="h-[22px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[11.5px] font-semibold leading-tight">{{ event.title }}</span>
                        <span class="block truncate font-mono text-[10px] text-ryh-ink3">{{ event.time }}</span>
                    </span>
                    <span
                        v-for="member in event.attendees.slice(0, 2)"
                        :key="member.id"
                        class="grid h-[17px] w-[17px] flex-none place-items-center rounded-[5px] text-[8px] font-extrabold text-[#0E1A2B]"
                        :style="{ background: member.colour }"
                    >
                        {{ member.initials }}
                    </span>
                </button>

                <button
                    v-if="eventsFor(day, filter).length > VISIBLE_PER_CELL"
                    type="button"
                    class="self-start px-1 text-[11px] font-semibold text-ryh-coral hover:opacity-75"
                    @click="expandedDay = day"
                >
                    +{{ eventsFor(day, filter).length - VISIBLE_PER_CELL }} more
                </button>
            </div>
        </div>
    </div>
</template>
