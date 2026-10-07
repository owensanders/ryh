<script setup lang="ts">
import InstallPrompt from '@/components/househub/InstallPrompt.vue';
import NotificationBell from '@/components/househub/NotificationBell.vue';
import Toast from '@/components/househub/Toast.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import { logout } from '@/lib/logout';
import { pageHeading } from '@/lib/pageHeading';
import { Link, usePage } from '@inertiajs/vue3';
import { House, LogOut } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();
const { appearance, updateAppearance } = useAppearance();
const { user, canManage, isOwner } = useHouseholdRole();

const isDark = computed(() => appearance.value === 'dark');

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const nav = computed(() =>
    [
        { label: 'Dashboard', short: 'Home', icon: '🏠', route: 'dashboard' },
        { label: 'Calendar', short: 'Calendar', icon: '📅', route: 'calendar.index' },
        { label: 'Meal Planner', short: 'Meals', icon: '🍽', route: 'meals.index' },
        { label: 'Shopping Lists', short: 'Lists', icon: '🛒', route: 'shopping.index' },
        { label: 'Chores', short: 'Chores', icon: '✅', route: 'chores.index' },
        // Budget and Documents involve money or sensitive records — Owner/Adult only.
        canManage.value ? { label: 'Budget', icon: '💰', route: 'budget.index' } : null,
        // House manages membership and household settings — Owner only, so an
        // Adult can never reassign roles or remove people (including the owner).
        isOwner.value ? { label: 'House', icon: '🏡', route: 'house.index' } : null,
        canManage.value ? { label: 'Documents', icon: '📂', route: 'documents.index' } : null,
        // Settings has three sub-pages, so match on the URL prefix rather than one route name.
        { label: 'Settings', icon: '⚙', route: 'profile.edit', prefix: '/settings' },
    ].filter((item) => item !== null),
);

function isActive(item: { route: string; prefix?: string }): boolean {
    return item.prefix ? page.url.startsWith(item.prefix) : route().current(item.route);
}

// Phones get a bottom tab bar: the everyday screens as tabs, the rest in a "More" sheet.
const tabs = computed(() => nav.value.filter((item) => 'short' in item));
const moreItems = computed(() => nav.value.filter((item) => !('short' in item)));
const moreActive = computed(() => moreItems.value.some(isActive));
const moreOpen = ref(false);

watch(
    () => page.url,
    () => (moreOpen.value = false),
);

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<!--
    The app's frame — sidebar, header, phone tab bar, bell. It's a persistent
    Inertia layout, so it stays mounted between screens and only the slot
    (the page) changes. Screens set the header's title via HouseHubLayout.
-->
<template>
    <div class="min-h-screen bg-hh-bg text-hh-ink transition-colors sm:p-7">
        <div class="mx-auto flex min-h-screen max-w-[1440px] overflow-hidden bg-hh-shell sm:min-h-[calc(100vh-3.5rem)] sm:rounded-3xl sm:shadow-hh">
            <!-- Full sidebar from `lg`, an icon rail from `sm`, and the bottom tab bar below that. -->
            <aside class="hidden w-[68px] flex-none flex-col gap-[3px] border-r border-hh-line bg-hh-panel px-[14px] py-[22px] sm:flex lg:w-[252px]">
                <div class="flex items-center gap-3 px-2 pb-5 pt-1">
                    <div class="grid h-9 w-9 flex-none place-items-center rounded-xl bg-hh-coral text-white">
                        <House class="h-[19px] w-[19px]" :stroke-width="2.5" />
                    </div>
                    <div class="hidden flex-col gap-px lg:flex">
                        <span class="text-[16.5px] font-extrabold tracking-tight">HouseHub</span>
                        <span class="text-[11px] text-hh-ink3">The home, organised.</span>
                    </div>
                </div>

                <Link
                    v-for="item in nav"
                    :key="item.route"
                    :href="route(item.route)"
                    :aria-label="item.label"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    class="flex min-h-[44px] w-full items-center gap-3 rounded-[13px] px-3 text-sm transition-colors hover:bg-hh-soft"
                    :class="isActive(item) ? 'bg-hh-card font-bold text-hh-ink' : 'font-medium text-hh-ink2'"
                >
                    <span class="w-5 flex-none text-center text-sm" aria-hidden="true">{{ item.icon }}</span>
                    <span class="hidden flex-1 lg:block">{{ item.label }}</span>
                </Link>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col bg-hh-shell">
                <header class="flex h-[64px] flex-none items-center gap-3 border-b border-hh-line px-4 sm:h-[72px] sm:gap-4 sm:px-[30px]">
                    <div class="grid h-8 w-8 flex-none place-items-center rounded-[10px] bg-hh-coral text-white sm:hidden">
                        <House class="h-4 w-4" :stroke-width="2.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-[19px] font-extrabold tracking-tight">{{ pageHeading.title }}</div>
                    </div>
                    <div v-if="pageHeading.subtitle" class="hidden truncate text-[13px] text-hh-ink3 sm:block">
                        {{ pageHeading.subtitle }}
                    </div>
                    <div class="flex-1"></div>

                    <button
                        type="button"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                        class="hidden h-[38px] w-[38px] rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line sm:block"
                        @click="toggleTheme"
                    >
                        {{ isDark ? '☀️' : '🌙' }}
                    </button>

                    <NotificationBell />

                    <Link
                        :href="route('profile.edit')"
                        title="Settings"
                        class="flex h-[38px] items-center gap-2 rounded-xl bg-hh-soft p-[3px_12px_3px_3px] transition-colors hover:bg-hh-line"
                    >
                        <div class="grid h-8 w-8 place-items-center rounded-[10px] bg-hh-mint text-xs font-extrabold text-[#0E1A2B]">
                            {{ initials }}
                        </div>
                        <span class="hidden text-[13px] font-semibold sm:block">{{ user?.name?.split(' ')[0] }}</span>
                    </Link>

                    <button
                        type="button"
                        title="Logout"
                        class="hidden h-[38px] w-[38px] place-items-center rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line sm:grid"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4" />
                    </button>
                </header>

                <!-- scroll-region: Inertia resets this to the top on a new screen and restores it on Back. -->
                <div scroll-region class="flex-1 overflow-y-auto px-4 pb-28 pt-5 sm:px-[30px] sm:pb-10 sm:pt-6">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Phone navigation -->
        <nav
            class="bg-hh-shell/95 fixed inset-x-0 bottom-0 z-40 flex border-t border-hh-line px-1 pb-[env(safe-area-inset-bottom)] backdrop-blur sm:hidden"
            aria-label="Main"
        >
            <Link
                v-for="item in tabs"
                :key="item.route"
                :href="route(item.route)"
                class="flex h-[62px] min-w-0 flex-1 flex-col items-center justify-center gap-1 text-[10.5px]"
                :class="isActive(item) ? 'font-bold text-hh-ink' : 'font-medium text-hh-ink3'"
                :aria-current="isActive(item) ? 'page' : undefined"
            >
                <span
                    class="grid h-7 w-11 place-items-center rounded-full text-[15px] transition-colors"
                    :class="isActive(item) ? 'bg-hh-soft' : ''"
                    aria-hidden="true"
                    >{{ item.icon }}</span
                >
                <span class="truncate">{{ item.short }}</span>
            </Link>
            <button
                type="button"
                class="flex h-[62px] min-w-0 flex-1 flex-col items-center justify-center gap-1 text-[10.5px]"
                :class="moreActive || moreOpen ? 'font-bold text-hh-ink' : 'font-medium text-hh-ink3'"
                :aria-expanded="moreOpen"
                aria-controls="more-sheet"
                @click="moreOpen = !moreOpen"
            >
                <span
                    class="grid h-7 w-11 place-items-center rounded-full text-[15px] transition-colors"
                    :class="moreActive || moreOpen ? 'bg-hh-soft' : ''"
                    aria-hidden="true"
                    >☰</span
                >
                <span>More</span>
            </button>
        </nav>

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="moreOpen" class="fixed inset-0 z-30 bg-black/30 sm:hidden" @click="moreOpen = false"></div>
        </Transition>
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-6 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-6 opacity-0"
        >
            <div
                v-if="moreOpen"
                id="more-sheet"
                class="fixed inset-x-3 bottom-[calc(74px+env(safe-area-inset-bottom))] z-40 rounded-3xl border border-hh-line bg-hh-card p-2 shadow-hh sm:hidden"
            >
                <Link
                    v-for="item in moreItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex min-h-[48px] items-center gap-3 rounded-2xl px-3 text-[15px]"
                    :class="isActive(item) ? 'bg-hh-soft font-bold' : 'font-medium text-hh-ink2'"
                >
                    <span class="w-6 text-center" aria-hidden="true">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>
                <div class="my-1 border-t border-hh-line"></div>
                <button
                    type="button"
                    class="flex min-h-[48px] w-full items-center gap-3 rounded-2xl px-3 text-[15px] font-medium text-hh-ink2"
                    @click="toggleTheme"
                >
                    <span class="w-6 text-center" aria-hidden="true">{{ isDark ? '☀️' : '🌙' }}</span>
                    {{ isDark ? 'Light mode' : 'Dark mode' }}
                </button>
                <button
                    type="button"
                    class="flex min-h-[48px] w-full items-center gap-3 rounded-2xl px-3 text-[15px] font-medium text-hh-ink2"
                    @click="logout"
                >
                    <LogOut class="mx-1 h-4 w-4" />
                    Log out
                </button>
            </div>
        </Transition>

        <InstallPrompt />
        <Toast />
    </div>
</template>
