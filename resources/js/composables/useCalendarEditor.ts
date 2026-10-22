import { isEditable, todayIso } from '@/lib/calendar';
import type { CalendarDayData, CalendarEventData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { inject, nextTick, provide, ref, type InjectionKey } from 'vue';

/** The calendar's add/edit dialog and its "+N more" day list: what's open, the form, and the writes. */
function createCalendarEditor() {
    const open = ref(false);
    const editing = ref<CalendarEventData | null>(null);
    const expandedDay = ref<CalendarDayData | null>(null);

    const form = useForm({
        title: '',
        starts_at: '',
        ends_at: '',
        is_all_day: false as boolean,
        location: '',
        notes: '',
        colour: 'mint',
        attendees: [] as number[],
    });

    function openNew(day: CalendarDayData): void {
        const today = todayIso();

        editing.value = null;
        expandedDay.value = null;
        form.clearErrors();
        form.defaults({
            title: '',
            starts_at: `${day.date < today ? today : day.date}T09:00`,
            ends_at: '',
            is_all_day: false,
            location: '',
            notes: '',
            colour: 'mint',
            attendees: [],
        });
        form.reset();
        open.value = true;
    }

    async function openEdit(event: CalendarEventData): Promise<void> {
        if (!isEditable(event)) {
            return;
        }

        editing.value = event;

        // Let the "+N more" dialog finish unmounting before the editor mounts, or the
        // two scroll locks race and can leave the page unclickable.
        if (expandedDay.value !== null) {
            expandedDay.value = null;
            await nextTick();
        }

        form.clearErrors();
        form.defaults({
            title: event.title,
            starts_at: event.startsAt,
            ends_at: event.endsAt ?? '',
            is_all_day: event.isAllDay,
            location: event.location ?? '',
            notes: event.notes ?? '',
            colour: event.colourKey,
            attendees: event.attendees.map((member) => member.id),
        });
        form.reset();
        open.value = true;
    }

    function toggleAttendee(id: number): void {
        form.attendees = form.attendees.includes(id) ? form.attendees.filter((memberId) => memberId !== id) : [...form.attendees, id];
    }

    function submit(): void {
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        };

        // An empty end time has to go as null, not '', or the date rule rejects it.
        form.transform((data) => ({ ...data, ends_at: data.ends_at === '' ? null : data.ends_at }));

        if (editing.value) {
            form.patch(route('calendar.events.update', { event: editing.value.id }), options);
        } else {
            form.post(route('calendar.events.store'), options);
        }
    }

    function destroy(): void {
        if (editing.value === null) {
            return;
        }

        router.delete(route('calendar.events.destroy', { event: editing.value.id }), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        });
    }

    return { open, editing, expandedDay, form, openNew, openEdit, toggleAttendee, submit, destroy };
}

export type CalendarEditor = ReturnType<typeof createCalendarEditor>;

const key: InjectionKey<CalendarEditor> = Symbol('CalendarEditor');

/** Called by the Calendar page; its grids and dialogs reach the same editor with useCalendarEditor(). */
export function provideCalendarEditor(): CalendarEditor {
    const editor = createCalendarEditor();
    provide(key, editor);

    return editor;
}

export function useCalendarEditor(): CalendarEditor {
    const editor = inject(key);

    if (editor === undefined) {
        throw new Error('useCalendarEditor() needs provideCalendarEditor() in a parent: the Calendar page.');
    }

    return editor;
}
