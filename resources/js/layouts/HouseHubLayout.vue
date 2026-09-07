<script setup lang="ts">
import NotificationBell from '@/components/househub/NotificationBell.vue';
import Toast from '@/components/househub/Toast.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { House, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    subtitle?: string;
}>();

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
        { label: 'Dashboard', icon: '🏠', route: 'dashboard' },
        { label: 'Calendar', icon: '📅', route: 'calendar.index' },
        { label: 'Meal Planner', icon: '🍽', route: 'meals.index' },
        { label: 'Shopping Lists', icon: '🛒', route: 'shopping.index' },
        { label: 'Chores', icon: '✅', route: 'chores.index' },
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

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <Head :title="props.title" />

    <div class="min-h-screen bg-hh-bg p-3 text-hh-ink transition-colors sm:p-7">
        <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1440px] overflow-hidden rounded-3xl bg-hh-shell shadow-hh sm:min-h-[calc(100vh-3.5rem)]">
            <!-- Collapses to an icon rail below `lg`; the design only specified the wide state. -->
            <aside class="flex w-[68px] flex-none flex-col gap-[3px] border-r border-hh-line bg-hh-panel px-[14px] py-[22px] lg:w-[252px]">
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
                    class="flex min-h-[44px] w-full items-center gap-3 rounded-[13px] px-3 text-sm transition-colors hover:bg-hh-soft"
                    :class="isActive(item) ? 'bg-hh-card font-bold text-hh-ink' : 'font-medium text-hh-ink2'"
                >
                    <span class="w-5 flex-none text-center text-sm">{{ item.icon }}</span>
                    <span class="hidden flex-1 lg:block">{{ item.label }}</span>
                </Link>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col bg-hh-shell">
                <header class="flex h-[72px] flex-none items-center gap-4 border-b border-hh-line px-4 sm:px-[30px]">
                    <div class="min-w-0">
                        <div class="truncate text-[19px] font-extrabold tracking-tight">{{ props.title }}</div>
                    </div>
                    <div v-if="props.subtitle" class="hidden truncate text-[13px] text-hh-ink3 sm:block">
                        {{ props.subtitle }}
                    </div>
                    <div class="flex-1"></div>

                    <button
                        type="button"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                        class="h-[38px] w-[38px] rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line"
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

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        type="button"
                        title="Logout"
                        class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link>
                </header>

                <div class="flex-1 overflow-y-auto px-4 pb-10 pt-6 sm:px-[30px]">
                    <slot />
                </div>
            </main>
        </div>

        <Toast />
    </div>
</template>
