<script setup lang="ts">
import Toast from '@/components/househub/Toast.vue';
import type { SharedData } from '@/types';
import type { HouseholdOption } from '@/types/househub';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { House, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    households: HouseholdOption[];
    currentHouseholdId: number | null;
}>();

const page = usePage<SharedData>();

const form = useForm({
    household_id: props.currentHouseholdId ?? props.households[0]?.id ?? null,
});

const selectedHousehold = computed(() => props.households.find((household) => household.id === form.household_id) ?? null);

const initials = computed(() =>
    (page.props.auth.user.name ?? '')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

// Someone who has just accepted their first invite lands here with a single household.
const inSeveral = computed(() => props.households.length > 1);

const notes = [
    { icon: '🔒', title: 'Kept apart', body: 'Budgets, documents and shopping lists never mix between households.' },
    { icon: '🔁', title: 'Switch any time', body: 'Change household whenever you like from the settings page once you are logged in.' },
    { icon: '👥', title: 'Different roles', body: 'You can be the owner in one house and a housemate in another.' },
];

function submit(): void {
    if (form.household_id === null) return;
    form.post(route('household.select.store'));
}
</script>

<template>
    <div class="flex min-h-svh justify-center bg-hh-bg p-4 text-hh-ink transition-colors sm:p-7">
        <Head title="Select household" />
        <Toast />

        <div class="flex w-full max-w-[1240px] animate-hh-rise overflow-hidden rounded-[24px] bg-hh-shell shadow-hh">
            <aside
                class="relative hidden w-[340px] flex-none flex-col overflow-hidden p-8 text-hh-onhero lg:flex"
                style="background: linear-gradient(150deg, var(--hh-hero1), var(--hh-hero2))"
            >
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-hh-coral opacity-20 blur-3xl"></div>

                <div class="relative flex items-center gap-2.5">
                    <div class="grid h-9 w-9 flex-none place-items-center rounded-xl bg-hh-coral text-white">
                        <House class="h-[19px] w-[19px]" :stroke-width="2.5" />
                    </div>
                    <span class="text-[17px] font-extrabold tracking-tight">HouseHub</span>
                </div>

                <div class="relative mt-11 flex items-center gap-3.5">
                    <span
                        class="grid h-[46px] w-[46px] flex-none place-items-center rounded-[15px] text-[15px] font-extrabold text-[#0E1A2B]"
                        style="background: var(--hh-t5)"
                    >
                        {{ initials }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-[15.5px] font-extrabold tracking-[-0.02em]">{{ page.props.auth.user.name }}</div>
                        <div class="mt-0.5 truncate text-[12.5px] text-hh-onhero2">{{ page.props.auth.user.email }}</div>
                    </div>
                </div>

                <div class="relative mt-7 max-w-[280px] text-[22px] font-black leading-tight tracking-[-0.03em]">
                    {{ inSeveral ? 'You are in more than one household' : 'Here is your household' }}
                </div>
                <p class="relative mt-3 max-w-[290px] text-[13.5px] leading-relaxed text-hh-onhero2">
                    <template v-if="inSeveral">
                        Pick the one you want to open. Meals, lists, chores and documents stay separate — nothing crosses between them.
                    </template>
                    <template v-else>
                        Open it to get started. If you join another household later, its meals, lists, chores and documents stay separate.
                    </template>
                </p>

                <div class="relative mt-8 flex flex-col gap-3.5 border-t border-hh-heroline pt-6">
                    <div v-for="note in notes" :key="note.title" class="flex items-start gap-3">
                        <span class="grid h-7 w-7 flex-none place-items-center rounded-[9px] border border-hh-heroline bg-hh-herofilm text-xs">
                            {{ note.icon }}
                        </span>
                        <div class="flex-1">
                            <div class="text-[13.5px] font-bold">{{ note.title }}</div>
                            <div class="mt-0.5 max-w-[260px] text-[12.5px] leading-relaxed text-hh-onhero2">{{ note.body }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex-1"></div>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="relative self-start rounded-[13px] border border-hh-heroline bg-hh-herofilm px-4 py-2.5 text-[13.5px] font-bold text-hh-onhero transition-colors hover:bg-hh-heroline"
                >
                    Sign out
                </Link>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col overflow-y-auto p-8">
                <div class="max-w-[720px]">
                    <h1 class="text-[32px] font-black tracking-[-0.035em] sm:text-[38px]">
                        {{ inSeveral ? 'Which house are you in today?' : "You're all set" }}
                    </h1>
                    <p class="mt-3 text-[15.5px] leading-relaxed text-hh-ink2">
                        <template v-if="inSeveral">
                            Choose a household to open its dashboard. You can switch to another one at any time from the settings page.
                        </template>
                        <template v-else>
                            Open your household's dashboard to get started. If you join another one later, you can switch between them from the
                            settings page.
                        </template>
                    </p>

                    <div class="mt-6 flex flex-col gap-2.5">
                        <button
                            v-for="household in props.households"
                            :key="household.id"
                            type="button"
                            class="flex items-center gap-4 rounded-[20px] border-[1.5px] p-5 text-left transition-transform hover:-translate-y-0.5"
                            :class="form.household_id === household.id ? 'border-hh-coral bg-hh-card' : 'border-hh-line bg-transparent'"
                            @click="form.household_id = household.id"
                        >
                            <span
                                class="grid h-[52px] w-[52px] flex-none place-items-center rounded-[17px] text-[#0E1A2B]"
                                style="background: var(--hh-t1)"
                            >
                                <House class="h-6 w-6" :stroke-width="1.8" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2.5">
                                    <span class="text-[17.5px] font-extrabold tracking-[-0.02em]">{{ household.name }}</span>
                                    <span class="rounded-lg bg-hh-sunk px-2 py-[3px] text-[11px] font-extrabold text-hh-ink2">
                                        {{ household.roleLabel }}
                                    </span>
                                </span>
                                <span class="mt-1 block text-[13px] text-hh-ink3">{{ household.memberCount }} members</span>
                            </span>
                            <span class="flex items-center">
                                <span
                                    v-for="avatar in household.memberAvatars"
                                    :key="avatar.initials"
                                    class="-ml-2 grid h-[30px] w-[30px] place-items-center rounded-[10px] border-2 border-hh-shell text-[10.5px] font-extrabold text-[#0E1A2B]"
                                    :style="{ background: avatar.colour }"
                                >
                                    {{ avatar.initials }}
                                </span>
                            </span>
                            <span
                                class="grid h-[30px] w-[30px] flex-none place-items-center rounded-full text-[13px] font-extrabold transition-colors"
                                :class="form.household_id === household.id ? 'bg-hh-coral text-white' : 'bg-hh-sunk text-hh-ink3'"
                            >
                                {{ form.household_id === household.id ? '✓' : '' }}
                            </span>
                        </button>
                    </div>

                    <div class="p-4.5 mt-3.5 flex items-center gap-3 rounded-[18px] border border-dashed border-hh-line bg-hh-sunk">
                        <span class="grid h-[38px] w-[38px] flex-none place-items-center rounded-xl bg-hh-card text-base">🔑</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold">Somewhere new to add?</span>
                            <span class="mt-0.5 block text-[12.5px] text-hh-ink3"
                                >Join another household with an invite code, or set one up from scratch.</span
                            >
                        </span>
                        <Link
                            :href="route('household.setup')"
                            class="rounded-[13px] border border-hh-line bg-hh-card px-4 py-2.5 text-[13.5px] font-bold transition-transform hover:-translate-y-0.5"
                        >
                            Add household
                        </Link>
                    </div>

                    <div class="mt-6 flex items-center gap-3.5">
                        <button
                            type="button"
                            class="hh-btn bg-hh-coral px-6 text-white"
                            :disabled="form.household_id === null || form.processing"
                            @click="submit"
                        >
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            {{ selectedHousehold ? `Open ${selectedHousehold.name}` : 'Open household' }}
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
