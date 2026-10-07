<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import HouseHubShell from '@/layouts/HouseHubShell.vue';
import type { CalendarDay, CalendarEvent, CalendarMonth } from '@/types/househub';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

defineOptions({ layout: HouseHubShell });

const props = defineProps<{ calendar: CalendarMonth }>();

/** How many event pills fit in a cell before it collapses to "+N more". */
const VISIBLE_PER_CELL = 3;

const COLOURS = ['mint', 'lilac', 'sun', 'sky', 'coral'];

const { canManage, isTeen } = useHouseholdRole();
// Calendar events are shared and handy for a Teen to manage themselves (e.g. school schedules).
const canEditCalendar = computed(() => canManage.value || isTeen.value);

const filter = ref<number | null>(null);
const open = ref(false);
const editing = ref<CalendarEvent | null>(null);
const expandedDay = ref<CalendarDay | null>(null);

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

/**
 * Filtering is client-side — the whole month is already in props. An event with
 * no attendees is for the whole house, so it applies to whoever is filtered on.
 */
function visibleEvents(day: CalendarDay): CalendarEvent[] {
    if (filter.value === null) {
        return day.events;
    }

    return day.events.filter((event) => event.attendees.length === 0 || event.attendees.some((member) => member.id === filter.value));
}

const dialogTitle = computed(() => (editing.value ? 'Edit event' : 'New event'));

const todayIso = computed(() => new Date().toISOString().slice(0, 10));

/** What the toolbar button defaults to: today if it's on screen, else the 1st of the month shown. */
const defaultDay = computed(() => props.calendar.days.find((day) => day.isToday) ?? props.calendar.days.find((day) => day.isCurrentMonth)!);

// Phones get a compact month (dots per day) with the chosen day's events listed underneath.
const selectedDate = ref(defaultDay.value.date);
const selectedDay = computed(() => props.calendar.days.find((day) => day.date === selectedDate.value) ?? defaultDay.value);

function openNew(day: CalendarDay): void {
    editing.value = null;
    expandedDay.value = null;
    form.clearErrors();
    form.defaults({
        title: '',
        starts_at: `${day.date < todayIso.value ? todayIso.value : day.date}T09:00`,
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

async function openEdit(event: CalendarEvent): Promise<void> {
    if (!canEditCalendar.value || event.date < todayIso.value) {
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
</script>

<template>
    <HouseHubLayout title="Calendar" :subtitle="props.calendar.monthLabel">
        <div class="flex animate-hh-rise flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('calendar.index', { month: props.calendar.previousMonth })"
                    aria-label="Previous month"
                    class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line"
                >
                    ‹
                </Link>
                <Link
                    :href="route('calendar.index', { month: props.calendar.nextMonth })"
                    aria-label="Next month"
                    class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line"
                >
                    ›
                </Link>
                <Link
                    v-if="props.calendar.month !== props.calendar.today"
                    :href="route('calendar.index')"
                    class="flex h-[38px] items-center rounded-xl bg-hh-soft px-3.5 text-[13px] font-semibold transition-colors hover:bg-hh-line"
                >
                    Today
                </Link>

                <span class="ml-1 text-[15px] font-extrabold tracking-tight">{{ props.calendar.monthLabel }}</span>

                <button v-if="canEditCalendar" type="button" class="hh-btn ml-2 w-auto bg-hh-coral text-white" @click="openNew(defaultDay)">
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    New event
                </button>

                <div
                    class="-mx-4 flex w-[calc(100%+2rem)] items-center gap-1.5 overflow-x-auto px-4 pb-1 md:mx-0 md:ml-auto md:w-auto md:flex-wrap md:overflow-visible md:p-0"
                >
                    <button
                        type="button"
                        class="h-[34px] flex-none rounded-[11px] px-3 text-[12.5px] transition-colors"
                        :class="filter === null ? 'bg-hh-card font-bold shadow-hh' : 'bg-hh-soft font-medium text-hh-ink2 hover:bg-hh-line'"
                        @click="filter = null"
                    >
                        Everyone
                    </button>
                    <button
                        v-for="member in props.calendar.members"
                        :key="member.id"
                        type="button"
                        :title="member.name"
                        class="flex h-[34px] flex-none items-center gap-2 rounded-[11px] py-1 pl-1 pr-3 text-[12.5px] transition-colors"
                        :class="filter === member.id ? 'bg-hh-card font-bold shadow-hh' : 'bg-hh-soft font-medium text-hh-ink2 hover:bg-hh-line'"
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

            <div v-if="canEditCalendar" class="hidden text-[13px] text-hh-ink3 md:block">
                Click any day to add an event there, or an event to edit it.
            </div>

            <!-- Phones: compact month + the selected day's agenda -->
            <div class="md:hidden">
                <div class="mb-1 grid grid-cols-7">
                    <div v-for="label in props.calendar.weekdayLabels" :key="label" class="text-center text-[11px] font-bold text-hh-ink3">
                        {{ label.slice(0, 1) }}
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="day in props.calendar.days"
                        :key="day.date"
                        type="button"
                        class="flex h-[52px] flex-col items-center justify-center gap-1 rounded-xl transition-colors"
                        :class="[
                            day.date === selectedDay.date ? 'bg-hh-ink text-hh-shell' : day.isToday ? 'bg-hh-sunk' : '',
                            day.isCurrentMonth ? '' : 'opacity-40',
                        ]"
                        :aria-label="`${day.dayLabel} ${day.dateLabel}, ${visibleEvents(day).length} events`"
                        :aria-pressed="day.date === selectedDay.date"
                        @click="selectedDate = day.date"
                    >
                        <span class="text-[14px] font-bold" :class="day.isToday && day.date !== selectedDay.date ? 'text-hh-coral' : ''">
                            {{ day.dateLabel }}
                        </span>
                        <span class="flex h-1.5 gap-[3px]">
                            <span
                                v-for="event in visibleEvents(day).slice(0, 3)"
                                :key="event.id"
                                class="h-1.5 w-1.5 rounded-full"
                                :style="{ background: event.colour }"
                            ></span>
                        </span>
                    </button>
                </div>

                <div class="mt-4 rounded-[18px] border border-hh-line bg-hh-card p-3">
                    <div class="mb-2 flex items-center gap-2 px-1">
                        <span class="text-[15px] font-extrabold tracking-tight">{{ selectedDay.dayLabel }} {{ selectedDay.dateLabel }}</span>
                        <button
                            v-if="canEditCalendar && selectedDay.date >= todayIso"
                            type="button"
                            class="ml-auto flex h-9 items-center gap-1.5 rounded-xl bg-hh-soft px-3 text-[13px] font-semibold"
                            @click="openNew(selectedDay)"
                        >
                            <Plus class="h-4 w-4" :stroke-width="2.5" /> Add
                        </button>
                    </div>
                    <p v-if="visibleEvents(selectedDay).length === 0" class="px-1 py-4 text-center text-[13px] text-hh-ink3">Nothing planned.</p>
                    <div class="flex flex-col gap-1.5">
                        <button
                            v-for="event in visibleEvents(selectedDay)"
                            :key="event.id"
                            type="button"
                            :disabled="!canEditCalendar || event.date < todayIso"
                            class="flex items-center gap-3 rounded-[13px] bg-hh-sunk px-3 py-2.5 text-left disabled:opacity-60"
                            @click="openEdit(event)"
                        >
                            <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[14px] font-semibold">{{ event.title }}</span>
                                <span class="block truncate font-mono text-[11.5px] text-hh-ink3">{{ event.time }}</span>
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

            <div class="hidden md:block">
                <div>
                    <div class="mb-1.5 grid grid-cols-7 gap-2">
                        <div v-for="label in props.calendar.weekdayLabels" :key="label" class="text-center text-[11.5px] font-bold text-hh-ink3">
                            {{ label }}
                        </div>
                    </div>

                    <div class="grid grid-cols-7 gap-2">
                        <div
                            v-for="day in props.calendar.days"
                            :key="day.date"
                            class="group flex min-h-[116px] min-w-0 flex-col gap-1 rounded-[14px] border border-hh-line p-2 transition"
                            :class="[day.isToday ? 'bg-hh-sunk' : 'bg-hh-card', day.isCurrentMonth ? '' : 'opacity-50']"
                        >
                            <div class="flex items-center">
                                <span class="px-1 text-[12px] font-bold" :class="day.isToday ? 'text-hh-coral' : 'text-hh-ink2'">
                                    {{ day.dateLabel }}
                                </span>
                                <button
                                    v-if="canEditCalendar && day.date >= todayIso"
                                    type="button"
                                    class="ml-auto grid h-7 w-7 place-items-center rounded-md text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-ink [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100"
                                    :aria-label="`Add an event on ${day.dateLabel}`"
                                    @click="openNew(day)"
                                >
                                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                                </button>
                            </div>

                            <button
                                v-for="event in visibleEvents(day).slice(0, VISIBLE_PER_CELL)"
                                :key="event.id"
                                type="button"
                                :disabled="!canEditCalendar || event.date < todayIso"
                                class="flex items-center gap-1.5 bg-hh-soft px-1.5 py-1 text-left transition hover:translate-x-[2px] disabled:opacity-50 disabled:hover:translate-x-0"
                                :class="[event.isSpanStart ? 'rounded-l-[9px]' : '', event.isSpanEnd ? 'rounded-r-[9px]' : '']"
                                @click="openEdit(event)"
                            >
                                <span class="h-[22px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11.5px] font-semibold leading-tight">{{ event.title }}</span>
                                    <span class="block truncate font-mono text-[10px] text-hh-ink3">{{ event.time }}</span>
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
                                v-if="visibleEvents(day).length > VISIBLE_PER_CELL"
                                type="button"
                                class="self-start px-1 text-[11px] font-semibold text-hh-coral hover:opacity-75"
                                @click="expandedDay = day"
                            >
                                +{{ visibleEvents(day).length - VISIBLE_PER_CELL }} more
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whole day, for cells with more events than fit. -->
        <Dialog :open="expandedDay !== null" @update:open="expandedDay = null">
            <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">
                        {{ expandedDay?.dayLabel }} {{ expandedDay?.dateLabel }}
                    </DialogTitle>
                </DialogHeader>

                <div class="flex flex-col gap-1.5">
                    <button
                        v-for="event in expandedDay ? visibleEvents(expandedDay) : []"
                        :key="event.id"
                        type="button"
                        :disabled="!canEditCalendar || event.date < todayIso"
                        class="flex items-center gap-3 rounded-[13px] bg-hh-sunk px-3 py-2.5 text-left transition hover:translate-x-[3px] disabled:opacity-50 disabled:hover:translate-x-0"
                        @click="openEdit(event)"
                    >
                        <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                        <span class="w-[92px] flex-none font-mono text-[11.5px] text-hh-ink2">{{ event.time }}</span>
                        <span class="flex-1 text-[13.5px] font-medium">{{ event.title }}</span>
                        <span class="text-[11.5px] text-hh-ink3">{{ event.who }}</span>
                    </button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Add / edit -->
        <Dialog :open="open" @update:open="open = $event">
            <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label for="title" class="hh-label">Title</label>
                        <input id="title" v-model="form.title" type="text" class="hh-input" required placeholder="What's happening?" />
                        <p v-if="form.errors.title" class="text-[12.5px] text-hh-coral">{{ form.errors.title }}</p>
                    </div>

                    <label for="is_all_day" class="flex items-center gap-2.5 text-[13px] text-hh-ink2">
                        <input id="is_all_day" v-model="form.is_all_day" type="checkbox" class="h-4 w-4 accent-hh-coral" />
                        All day
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="starts_at" class="hh-label">Starts</label>
                            <input
                                id="starts_at"
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="hh-input"
                                :min="`${todayIso}T00:00`"
                                required
                            />
                            <p v-if="form.errors.starts_at" class="text-[12.5px] text-hh-coral">{{ form.errors.starts_at }}</p>
                        </div>

                        <div v-if="!form.is_all_day" class="flex flex-col gap-1.5">
                            <label for="ends_at" class="hh-label">Ends</label>
                            <input id="ends_at" v-model="form.ends_at" type="datetime-local" class="hh-input" />
                            <p v-if="form.errors.ends_at" class="text-[12.5px] text-hh-coral">{{ form.errors.ends_at }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="location" class="hh-label">Location</label>
                        <input id="location" v-model="form.location" type="text" class="hh-input" maxlength="120" placeholder="Optional" />
                        <p v-if="form.errors.location" class="text-[12.5px] text-hh-coral">{{ form.errors.location }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="hh-label">Who's it for?</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="member in props.calendar.members"
                                :key="member.id"
                                type="button"
                                class="flex h-[34px] items-center gap-2 rounded-[11px] py-1 pl-1 pr-3 text-[12.5px] transition-colors"
                                :class="
                                    form.attendees.includes(member.id)
                                        ? 'bg-hh-sunk font-bold ring-2 ring-hh-coral'
                                        : 'bg-hh-soft font-medium text-hh-ink2 hover:bg-hh-line'
                                "
                                :aria-pressed="form.attendees.includes(member.id)"
                                @click="toggleAttendee(member.id)"
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
                        <p class="text-[12px] text-hh-ink3">
                            {{ form.attendees.length === 0 ? 'Nobody picked — this one is for the whole house.' : 'Only the people picked above.' }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="hh-label">Colour</span>
                        <div class="flex gap-2">
                            <button
                                v-for="colour in COLOURS"
                                :key="colour"
                                type="button"
                                :title="colour"
                                :aria-label="colour"
                                :aria-pressed="form.colour === colour"
                                class="h-7 w-7 rounded-[9px] transition"
                                :class="form.colour === colour ? 'ring-2 ring-hh-ink ring-offset-2 ring-offset-hh-card' : ''"
                                :style="{ background: `var(--hh-${colour})` }"
                                @click="form.colour = colour"
                            ></button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="notes" class="hh-label">Notes</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            maxlength="2000"
                            class="hh-input h-auto py-2.5"
                            placeholder="Optional"
                        ></textarea>
                        <p v-if="form.errors.notes" class="text-[12.5px] text-hh-coral">{{ form.errors.notes }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="hh-btn w-auto bg-hh-coral text-white" :disabled="form.processing">
                            {{ editing ? 'Save changes' : 'Add event' }}
                        </button>
                        <button type="button" class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="open = false">Cancel</button>
                        <button v-if="editing" type="button" class="hh-btn ml-auto w-auto bg-hh-soft text-hh-coral" @click="destroy">Delete</button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </HouseHubLayout>
</template>
