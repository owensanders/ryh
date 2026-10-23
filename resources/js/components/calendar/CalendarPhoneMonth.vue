<script setup lang="ts">
import { useCalendarEditor } from '@/composables/useCalendarEditor';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { defaultDay, eventsFor, isEditable } from '@/lib/calendar';
import type { CalendarMonthData } from '@/types/generated';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/** Phones get a compact month (dots per day) with the chosen day's events listed underneath. */
const props = defineProps<{ calendar: CalendarMonthData; filter: number | null }>();

const can = useHouseholdAbilities();
const { openNew, openEdit } = useCalendarEditor();

const selectedDate = ref(defaultDay(props.calendar).date);
const selectedDay = computed(() => props.calendar.days.find((day) => day.date === selectedDate.value) ?? defaultDay(props.calendar));
</script>

<template>
    <div class="md:hidden">
        <div class="mb-1 grid grid-cols-7">
            <div v-for="label in calendar.weekdayLabels" :key="label" class="text-center text-[11px] font-bold text-ryh-ink3">
                {{ label.slice(0, 1) }}
            </div>
        </div>
        <div class="grid grid-cols-7 gap-1">
            <button
                v-for="day in calendar.days"
                :key="day.date"
                type="button"
                class="flex h-[52px] flex-col items-center justify-center gap-1 rounded-xl transition-colors"
                :class="[
                    day.date === selectedDay.date ? 'bg-ryh-ink text-ryh-shell' : day.isToday ? 'bg-ryh-sunk' : '',
                    day.isCurrentMonth ? '' : 'opacity-40',
                ]"
                :aria-label="`${day.dayLabel} ${day.dateLabel}, ${eventsFor(day, filter).length} events`"
                :aria-pressed="day.date === selectedDay.date"
                @click="selectedDate = day.date"
            >
                <span class="text-[14px] font-bold" :class="day.isToday && day.date !== selectedDay.date ? 'text-ryh-coral' : ''">
                    {{ day.dateLabel }}
                </span>
                <span class="flex h-1.5 gap-[3px]">
                    <span
                        v-for="event in eventsFor(day, filter).slice(0, 3)"
                        :key="event.id"
                        class="h-1.5 w-1.5 rounded-full"
                        :style="{ background: event.colour }"
                    ></span>
                </span>
            </button>
        </div>

        <div class="mt-4 rounded-[18px] border border-ryh-line bg-ryh-card p-3">
            <div class="mb-2 flex items-center gap-2 px-1">
                <span class="text-[15px] font-extrabold tracking-tight">{{ selectedDay.dayLabel }} {{ selectedDay.dateLabel }}</span>
                <button
                    v-if="can?.createCalendarEvents && selectedDay.date >= calendar.todayDate"
                    type="button"
                    class="ml-auto flex h-9 items-center gap-1.5 rounded-xl bg-ryh-soft px-3 text-[13px] font-semibold"
                    @click="openNew(selectedDay)"
                >
                    <Plus class="h-4 w-4" :stroke-width="2.5" /> Add
                </button>
            </div>
            <p v-if="eventsFor(selectedDay, filter).length === 0" class="px-1 py-4 text-center text-[13px] text-ryh-ink3">Nothing planned.</p>
            <div class="flex flex-col gap-1.5">
                <button
                    v-for="event in eventsFor(selectedDay, filter)"
                    :key="event.id"
                    type="button"
                    :disabled="!isEditable(event)"
                    class="flex items-center gap-3 rounded-[13px] bg-ryh-sunk px-3 py-2.5 text-left disabled:opacity-60"
                    @click="openEdit(event)"
                >
                    <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14px] font-semibold">{{ event.title }}</span>
                        <span class="block truncate font-mono text-[11.5px] text-ryh-ink3">{{ event.time }}</span>
                    </span>
                    <span
                        v-for="member in event.attendees.slice(0, 3)"
                        :key="member.id"
                        class="grid h-[22px] w-[22px] flex-none place-items-center rounded-md text-[9px] font-extrabold text-[#0E1A2B]"
                        :style="{ background: member.colour }"
                    >
                        {{ member.initials }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
