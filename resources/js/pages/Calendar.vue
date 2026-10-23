<script setup lang="ts">
import CalendarDayDialog from '@/components/calendar/CalendarDayDialog.vue';
import CalendarEventDialog from '@/components/calendar/CalendarEventDialog.vue';
import CalendarMonthGrid from '@/components/calendar/CalendarMonthGrid.vue';
import CalendarPhoneMonth from '@/components/calendar/CalendarPhoneMonth.vue';
import CalendarToolbar from '@/components/calendar/CalendarToolbar.vue';
import { provideCalendarEditor } from '@/composables/useCalendarEditor';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { CalendarMonthData } from '@/types/generated';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{ calendar: CalendarMonthData }>();

const can = useHouseholdAbilities();

// What's being added or edited, shared with the grids and dialogs below.
provideCalendarEditor(() => props.calendar.todayDate);

/** The member the month is filtered on, or null for everyone. */
const filter = ref<number | null>(null);
</script>

<template>
    <RyhLayout title="Calendar" :subtitle="calendar.monthLabel">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <CalendarToolbar v-model:filter="filter" :calendar="calendar" />

            <div v-if="can?.createCalendarEvents" class="hidden text-[13px] text-ryh-ink3 md:block">
                Click any day to add an event there, or an event to edit it.
            </div>

            <CalendarPhoneMonth :calendar="calendar" :filter="filter" />
            <CalendarMonthGrid :calendar="calendar" :filter="filter" />
        </div>

        <CalendarDayDialog :filter="filter" />
        <CalendarEventDialog :members="calendar.members" :today-date="calendar.todayDate" />
    </RyhLayout>
</template>
