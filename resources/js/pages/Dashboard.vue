<script setup lang="ts">
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { useOptimistic } from '@/composables/useOptimistic';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import { dial, tickStyle } from '@/lib/ryh';
import type { ChoreData, DashboardData, ShoppingItemData } from '@/types/generated';
import { Link, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: RyhShell });

const props = defineProps<{ dashboard: DashboardData }>();

const can = useHouseholdAbilities();

// Ticks show at once; the server's dashboard replaces this when it answers.
const { state: view, apply: updateView } = useOptimistic(() => props.dashboard);

const draft = useForm({ name: '' });

function addItem(): void {
    const slug = props.dashboard.shoppingList?.slug;

    if (!slug || draft.name.trim() === '') {
        return;
    }

    draft.post(route('shopping.items.store', { slug }), {
        preserveScroll: true,
        onSuccess: () => draft.reset(),
    });
}

function toggleItem(item: ShoppingItemData): void {
    if (!item.can.toggle) {
        return;
    }

    updateView(
        (dashboard) => {
            const shown = dashboard.shoppingList?.items.find((candidate) => candidate.id === item.id);

            if (shown && dashboard.shoppingList) {
                shown.done = !shown.done;
                dashboard.shoppingList.remaining += shown.done ? -1 : 1;
            }
        },
        (callbacks) => router.patch(route('shopping.items.toggle', { item: item.id }), {}, { preserveScroll: true, ...callbacks }),
    );
}

function toggleChore(chore: ChoreData): void {
    if (!chore.can.toggle) {
        return;
    }

    updateView(
        (dashboard) => {
            const shown = dashboard.chores.find((candidate) => candidate.id === chore.id);

            if (shown) {
                shown.done = !shown.done;
                const progress = dashboard.choreProgress;
                progress.done += shown.done ? 1 : -1;
                progress.percentage = progress.total === 0 ? 0 : Math.round((progress.done / progress.total) * 100);
            }
        },
        (callbacks) => router.patch(route('chores.toggle', { chore: chore.id }), {}, { preserveScroll: true, ...callbacks }),
    );
}
</script>

<template>
    <RyhLayout title="Dashboard" :subtitle="view.dateLine">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <!-- Hero -->
            <section
                class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-6 text-ryh-onhero lg:flex-row"
                style="background: linear-gradient(118deg, var(--ryh-hero1) 0%, var(--ryh-hero2) 100%)"
            >
                <div class="absolute -top-24 right-44 h-72 w-72 rounded-full bg-ryh-coral opacity-[0.22] blur-3xl"></div>

                <div class="relative min-w-0 flex-1">
                    <div
                        class="inline-flex h-[26px] items-center gap-2 rounded-lg border border-ryh-heroline bg-ryh-herofilm px-[11px] text-[11.5px] font-bold uppercase tracking-[0.06em]"
                    >
                        🔥 {{ view.streakDays }} day streak
                    </div>
                    <div class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-[-0.03em]">{{ view.greeting }}</div>
                    <div class="mt-2 text-sm text-ryh-onhero2">{{ view.dateLine }} · {{ view.summaryLine }}</div>

                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <div
                            v-for="person in view.family"
                            :key="person.id"
                            class="flex h-[42px] items-center gap-2.5 rounded-[13px] border border-ryh-heroline bg-ryh-herofilm py-0 pl-1 pr-3.5"
                        >
                            <div
                                class="grid h-[34px] w-[34px] place-items-center rounded-[10px] text-xs font-extrabold text-[#0E1A2B]"
                                :style="{ background: person.colour }"
                            >
                                {{ person.initials }}
                            </div>
                            <div class="flex flex-col leading-[1.15]">
                                <span class="text-[13px] font-bold">{{ person.name.split(' ')[0] }}</span>
                                <span class="text-[11px] text-ryh-onhero2">{{ person.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 items-stretch gap-4 xl:grid-cols-[1.45fr_1fr]">
                <!-- Tonight's dinner -->
                <section
                    aria-label="Tonight's dinner"
                    class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 transition hover:-translate-y-0.5 hover:shadow-ryh sm:p-[22px]"
                >
                    <div class="mb-3.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Tonight's dinner</h3>
                        <span class="text-xs text-ryh-ink3">Meal plan</span>
                        <Link
                            :href="route('meals.index')"
                            class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75"
                        >
                            Meal planner →
                        </Link>
                    </div>

                    <div v-if="view.tonight" class="flex flex-col gap-4 sm:flex-row">
                        <div class="flex h-[132px] w-full flex-none flex-col justify-end rounded-[18px] bg-ryh-coral p-4 sm:h-[172px] sm:w-[172px]">
                            <div
                                class="line-clamp-4 hyphens-auto break-words font-extrabold leading-[1.1] tracking-[-0.025em] text-white"
                                :class="view.tonight.name.length > 24 ? 'text-[20px]' : 'text-[22px] sm:text-[26px]'"
                                :title="view.tonight.name"
                            >
                                {{ view.tonight.name }}
                            </div>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex flex-wrap gap-[7px]">
                                <span
                                    v-for="chip in [...view.tonight.tags, view.tonight.duration, view.tonight.difficulty].filter(Boolean)"
                                    :key="chip as string"
                                    class="rounded-[9px] bg-ryh-soft px-2.5 py-[5px] text-xs font-semibold text-ryh-ink2"
                                >
                                    {{ chip }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm leading-[1.55] text-ryh-ink2">{{ view.tonight.description }}</p>
                            <div class="mt-auto flex items-center gap-3 pt-3.5">
                                <div v-if="view.tonight.cook" class="flex items-center gap-2">
                                    <div
                                        class="grid h-7 w-7 place-items-center rounded-[9px] text-[11px] font-extrabold text-[#0E1A2B]"
                                        :style="{ background: view.tonight.cook.colour }"
                                    >
                                        {{ view.tonight.cook.initials }}
                                    </div>
                                    <span class="text-[13px] text-ryh-ink2"> {{ view.tonight.cook.name.split(' ')[0] }} is cooking </span>
                                </div>
                                <Link
                                    :href="route('meals.index')"
                                    class="ml-auto flex h-[42px] flex-none items-center whitespace-nowrap rounded-[13px] bg-ryh-coral px-5 text-[13.5px] font-bold text-white transition hover:-translate-y-0.5"
                                >
                                    View recipe
                                </Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-10 text-center text-sm text-ryh-ink3">Nothing planned for tonight yet.</p>
                </section>

                <!-- Shopping preview -->
                <section
                    aria-label="Shopping"
                    class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 transition hover:-translate-y-0.5 hover:shadow-ryh sm:p-[22px]"
                >
                    <template v-if="view.shoppingList">
                        <div class="mb-2.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">{{ view.shoppingList.name }} list</h3>
                            <span class="text-xs text-ryh-ink3">{{ view.shoppingList.remaining }} left</span>
                            <Link
                                :href="route('shopping.index')"
                                class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75"
                            >
                                All lists →
                            </Link>
                        </div>

                        <div class="flex flex-col gap-px">
                            <button
                                v-for="item in view.shoppingList.items.slice(0, 5)"
                                :key="item.id"
                                type="button"
                                :disabled="!item.can.toggle"
                                class="flex min-h-[44px] w-full items-center gap-3 rounded-[11px] px-2 text-left transition-colors hover:bg-ryh-soft disabled:cursor-not-allowed"
                                @click="toggleItem(item)"
                            >
                                <span
                                    class="grid h-[21px] w-[21px] flex-none place-items-center rounded-[7px] border-[1.5px] text-[11px] text-[#0E1A2B] transition"
                                    :style="tickStyle(item.done)"
                                >
                                    {{ item.done ? '✓' : '' }}
                                </span>
                                <span class="flex-1 text-sm" :class="item.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                                    {{ item.name }}
                                </span>
                                <span class="font-mono text-[11.5px] text-ryh-ink3">{{ item.quantity }}</span>
                            </button>
                        </div>

                        <form v-if="can?.createShoppingItems" class="mt-auto flex gap-2 pt-3" @submit.prevent="addItem">
                            <input
                                v-model="draft.name"
                                placeholder="Add an item…"
                                aria-label="Add an item"
                                class="h-11 flex-1 rounded-[13px] border border-ryh-line bg-ryh-sunk px-3.5 text-sm text-ryh-ink placeholder:text-ryh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral"
                            />
                            <button
                                type="submit"
                                :disabled="draft.processing"
                                class="h-11 w-11 rounded-[13px] bg-ryh-coral text-xl text-white transition hover:scale-105 disabled:opacity-50"
                            >
                                +
                            </button>
                        </form>
                    </template>
                    <template v-else>
                        <div class="mb-2.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Shopping list</h3>
                            <Link
                                :href="route('shopping.index')"
                                class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75"
                            >
                                All lists →
                            </Link>
                        </div>
                        <p class="py-10 text-center text-sm text-ryh-ink3">No shopping list yet.</p>
                    </template>
                </section>
            </div>

            <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <!-- Chores -->
                <section aria-label="Today's chores" class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                    <div class="mb-3.5 flex items-center gap-3">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Today's chores</h3>
                        <Link
                            :href="route('chores.index')"
                            class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75"
                        >
                            Board →
                        </Link>
                    </div>

                    <div class="mb-3.5 flex items-center gap-4">
                        <div
                            class="grid h-[74px] w-[74px] flex-none place-items-center rounded-full transition-[background] duration-500"
                            :style="{ background: dial(view.choreProgress.percentage) }"
                        >
                            <div class="flex h-[57px] w-[57px] items-center justify-center rounded-full bg-ryh-card text-[16.5px] font-extrabold">
                                {{ view.choreProgress.percentage }}%
                            </div>
                        </div>
                        <div class="text-[13px] leading-[1.55] text-ryh-ink2">
                            {{ view.choreProgress.done }} of {{ view.choreProgress.total }} done today.<br />
                            Tap a chore to tick it off.
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <button
                            v-for="chore in view.chores.slice(0, 4)"
                            :key="chore.id"
                            type="button"
                            :disabled="!chore.can.toggle"
                            class="flex min-h-[48px] w-full items-center gap-3 rounded-[14px] border border-ryh-line px-3 text-left transition hover:translate-x-0.5 disabled:cursor-not-allowed"
                            :class="chore.done ? 'bg-ryh-sunk' : 'bg-ryh-card'"
                            @click="toggleChore(chore)"
                        >
                            <span
                                class="grid h-[21px] w-[21px] flex-none place-items-center rounded-full border-[1.5px] text-[11px] text-[#0E1A2B]"
                                :style="tickStyle(chore.done)"
                            >
                                {{ chore.done ? '✓' : '' }}
                            </span>
                            <span class="flex-1 text-sm font-medium" :class="chore.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                                {{ chore.name }}
                            </span>
                            <span
                                v-if="chore.assignee"
                                class="grid h-[26px] w-[26px] flex-none place-items-center rounded-lg text-[10px] font-extrabold text-[#0E1A2B]"
                                :style="{ background: chore.assignee.colour }"
                            >
                                {{ chore.assignee.initials }}
                            </span>
                        </button>
                    </div>
                </section>

                <!-- Agenda -->
                <section aria-label="Coming up" class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                    <div class="mb-3.5 flex items-center gap-3">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Coming up</h3>
                        <span class="ml-auto text-xs text-ryh-ink3">Next 3 days</span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div v-for="group in view.agenda" :key="group.label">
                            <div class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.09em] text-ryh-ink3">{{ group.label }}</div>
                            <div class="flex flex-col gap-[5px]">
                                <div
                                    v-for="entry in group.items"
                                    :key="entry.id"
                                    class="flex items-center gap-3 rounded-[13px] bg-ryh-sunk px-3 py-2.5 transition hover:translate-x-[3px]"
                                >
                                    <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: entry.colour }"></span>
                                    <span class="w-[50px] flex-none font-mono text-[11.5px] text-ryh-ink2">{{ entry.time }}</span>
                                    <span class="flex-1 text-[13.5px] font-medium">{{ entry.title }}</span>
                                    <span class="text-[11.5px] text-ryh-ink3">{{ entry.who }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-if="view.agenda.length === 0" class="text-sm text-ryh-ink3">Nothing in the diary.</p>
                    </div>
                </section>

                <!-- Budget -->
                <section aria-label="Budget" class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                    <div class="mb-1 flex items-center gap-3">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">{{ view.budget.monthLabel }} budget</h3>
                        <span class="ml-auto text-xs text-ryh-ink3">{{ view.budget.daysLeft }} days left</span>
                    </div>

                    <div class="flex items-baseline gap-2.5">
                        <span class="font-mono text-[40px] leading-[1.15]">{{ view.budget.budgeted }}</span>
                        <span class="text-[13px] text-ryh-ink3">assigned</span>
                    </div>

                    <div class="my-3 flex h-2.5 overflow-hidden rounded-md bg-ryh-sunk">
                        <span
                            v-for="category in view.budget.categories"
                            :key="category.id"
                            :style="{ width: `${category.shareOfTotal}%`, background: category.colour }"
                        ></span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div v-for="category in view.budget.categories" :key="category.id" class="flex items-center gap-3">
                            <span class="h-2 w-2 flex-none rounded-[3px]" :style="{ background: category.colour }"></span>
                            <span class="w-24 flex-none text-[13px] font-medium">{{ category.label }}</span>
                            <span class="ml-auto font-mono text-[12.5px]">{{ category.budgeted }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </RyhLayout>
</template>
