<script setup lang="ts">
import { useCalendarEditor } from '@/composables/useCalendarEditor';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { defaultDay } from '@/lib/calendar';
import type { CalendarMonthData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

defineProps<{ calendar: CalendarMonthData }>();

/** The member the month is filtered on, or null for everyone. */
const filter = defineModel<number | null>('filter', { required: true });

const can = useHouseholdAbilities();
const { openNew } = useCalendarEditor();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Link
            :href="route('calendar.index', { month: calendar.previousMonth })"
            aria-label="Previous month"
            class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-ryh-soft text-sm transition-colors hover:bg-ryh-line"
        >
            ‹
        </Link>
        <Link
            :href="route('calendar.index', { month: calendar.nextMonth })"
            aria-label="Next month"
            class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-ryh-soft text-sm transition-colors hover:bg-ryh-line"
        >
            ›
        </Link>
        <Link
            v-if="calendar.month !== calendar.today"
            :href="route('calendar.index')"
            class="flex h-[38px] items-center rounded-xl bg-ryh-soft px-3.5 text-[13px] font-semibold transition-colors hover:bg-ryh-line"
        >
            Today
        </Link>

        <span class="ml-1 text-[15px] font-extrabold tracking-tight">{{ calendar.monthLabel }}</span>

        <button
            v-if="can?.createCalendarEvents"
            type="button"
            class="ryh-btn ml-2 w-auto bg-ryh-coral text-white"
            @click="openNew(defaultDay(calendar))"
        >
            <Plus class="h-4 w-4" :stroke-width="2.5" />
            New event
        </button>

        <div
            class="-mx-4 flex w-[calc(100%+2rem)] items-center gap-1.5 overflow-x-auto px-4 pb-1 md:mx-0 md:ml-auto md:w-auto md:flex-wrap md:overflow-visible md:p-0"
        >
            <button
                type="button"
                class="h-[34px] flex-none rounded-[11px] px-3 text-[12.5px] transition-colors"
                :class="filter === null ? 'bg-ryh-card font-bold shadow-ryh' : 'bg-ryh-soft font-medium text-ryh-ink2 hover:bg-ryh-line'"
                @click="filter = null"
            >
                Everyone
            </button>
            <button
                v-for="member in calendar.members"
                :key="member.id"
                type="button"
                :title="member.name"
                class="flex h-[34px] flex-none items-center gap-2 rounded-[11px] py-1 pl-1 pr-3 text-[12.5px] transition-colors"
                :class="filter === member.id ? 'bg-ryh-card font-bold shadow-ryh' : 'bg-ryh-soft font-medium text-ryh-ink2 hover:bg-ryh-line'"
                @click="filter = filter === member.id ? null : member.id"
            >
                <span
                    class="grid h-[26px] w-[26px] place-items-center rounded-lg text-[9.5px] font-extrabold text-[#0E1A2B]"
                    :style="{ background: member.colour }"
                >
                    {{ member.initials }}
                </span>
                {{ member.name.split(' ')[0] }}
            </button>
        </div>
    </div>
</template>
