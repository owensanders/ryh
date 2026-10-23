import type { CalendarDayData, CalendarEventData, CalendarMonthData, MemberData } from '@/types/generated';
import { describe, expect, it } from 'vitest';
import { defaultDay, eventsFor, isEditable } from './calendar';

const member = (id: number): MemberData => ({ id, name: `Member ${id}`, initials: 'M', colour: 'mint', status: null });

const event = (id: number, attendees: MemberData[] = [], overrides: Partial<CalendarEventData> = {}): CalendarEventData =>
    ({ id, title: `Event ${id}`, attendees, can: { update: true, delete: true }, hasEnded: false, ...overrides }) as CalendarEventData;

const day = (date: string, events: CalendarEventData[] = [], flags: Partial<CalendarDayData> = {}): CalendarDayData =>
    ({ date, events, isToday: false, isCurrentMonth: true, ...flags }) as CalendarDayData;

describe('eventsFor', () => {
    const everyone = event(1);
    const sarahs = event(2, [member(1)]);
    const jamess = event(3, [member(2)]);
    const tuesday = day('2026-10-20', [everyone, sarahs, jamess]);

    it('shows every event with no one picked', () => {
        expect(eventsFor(tuesday, null)).toEqual([everyone, sarahs, jamess]);
    });

    it("shows a member's own events and the whole house's", () => {
        expect(eventsFor(tuesday, 1)).toEqual([everyone, sarahs]);
    });
});

describe('defaultDay', () => {
    it('opens on today when it’s on screen', () => {
        const today = day('2026-10-23', [], { isToday: true });

        expect(defaultDay({ days: [day('2026-09-28', [], { isCurrentMonth: false }), today] } as CalendarMonthData)).toBe(today);
    });

    it("otherwise on the first day of the month shown, not last month's tail", () => {
        const first = day('2026-12-01');

        expect(defaultDay({ days: [day('2026-11-30', [], { isCurrentMonth: false }), first] } as CalendarMonthData)).toBe(first);
    });
});

describe('isEditable', () => {
    it('needs the policy to allow it and the event not to be over', () => {
        expect(isEditable(event(1))).toBe(true);
        expect(isEditable(event(1, [], { hasEnded: true }))).toBe(false);
        expect(isEditable(event(1, [], { can: { update: false, delete: false } }))).toBe(false);
    });
});
