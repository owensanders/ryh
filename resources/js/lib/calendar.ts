import type { CalendarDayData, CalendarEventData, CalendarMonthData } from '@/types/generated';

/** How many event pills fit in a month cell before it collapses to "+N more". */
export const VISIBLE_PER_CELL = 3;

/** Today as `YYYY-MM-DD`. Past days can't take new events or edits. */
export function todayIso(): string {
    return new Date().toISOString().slice(0, 10);
}

/**
 * The day's events for the member filtered on, or all of them with no filter.
 * Filtering is client-side: the whole month is already in props. An event
 * with no attendees is for the whole house, so it applies to whoever is picked.
 */
export function eventsFor(day: CalendarDayData, memberId: number | null): CalendarEventData[] {
    if (memberId === null) {
        return day.events;
    }

    return day.events.filter((event) => event.attendees.length === 0 || event.attendees.some((member) => member.id === memberId));
}

/** Today if it's on screen, else the 1st of the month shown. */
export function defaultDay(calendar: CalendarMonthData): CalendarDayData {
    return calendar.days.find((day) => day.isToday) ?? calendar.days.find((day) => day.isCurrentMonth)!;
}

/** Whether this person may open the event to change it: their policy says so, and it hasn't happened yet. */
export function isEditable(event: CalendarEventData): boolean {
    return event.can.update === true && event.date >= todayIso();
}
