<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { House, Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// The landing page's chrome — header, mobile menu and footer — shared with the
// public pages its footer links to (privacy, terms, support, status).

const page = usePage<SharedData>();
const { appearance, updateAppearance } = useAppearance();

const isDark = computed(() => appearance.value === 'dark');
const user = computed(() => page.props.auth?.user);

// Below `lg` the section links (and, on phones, Log in and the theme toggle) live in a menu.
const menuOpen = ref(false);

// Rooted at `/` so they jump to the landing sections from any public page.
const navLinks = [
    { label: 'Daily rhythm', href: '/#daily' },
    { label: 'Features', href: '/#features' },
    { label: 'Pricing', href: '/#pricing' },
    { label: 'Promises', href: '/#promises' },
];

const footerLinks = [
    { label: 'Privacy', href: route('privacy') },
    { label: 'Terms', href: route('terms') },
    { label: 'Support', href: route('support') },
    { label: 'Status', href: route('status') },
];

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <div class="min-h-screen bg-hh-bg text-hh-ink transition-colors">
        <div class="mx-auto max-w-[1440px] bg-hh-shell">
            <header class="sticky top-0 z-20 flex h-[76px] items-center gap-3 border-b border-hh-line bg-hh-shell px-5 sm:px-8 lg:px-14">
                <Link :href="route('home')" class="flex items-center gap-[11px]" aria-label="HouseHub home">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-hh-coral text-white">
                        <House class="h-[19px] w-[19px]" :stroke-width="2.5" />
                    </div>
                    <span class="text-[17px] font-extrabold tracking-tight">HouseHub</span>
                </Link>

                <nav class="ml-8 hidden gap-1 lg:flex">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="flex h-[38px] items-center rounded-[11px] px-3.5 text-sm font-semibold text-hh-ink2 transition-colors hover:bg-hh-soft hover:text-hh-ink"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <div class="flex-1"></div>

                <button
                    type="button"
                    :title="isDark ? 'Light mode' : 'Dark mode'"
                    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    class="hidden h-10 w-10 rounded-xl bg-hh-soft text-[15px] transition-colors hover:bg-hh-line sm:block"
                    @click="toggleTheme"
                >
                    {{ isDark ? '☀️' : '🌙' }}
                </button>

                <Link
                    v-if="user"
                    :href="route('dashboard')"
                    class="flex h-10 items-center whitespace-nowrap rounded-xl bg-hh-coral px-4 text-sm font-bold text-white transition hover:-translate-y-0.5 sm:px-5"
                >
                    Go to dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="hidden h-10 items-center rounded-xl px-4 text-sm font-semibold text-hh-ink2 transition-colors hover:text-hh-ink sm:flex"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="flex h-10 items-center whitespace-nowrap rounded-xl bg-hh-coral px-4 text-sm font-bold text-white transition hover:-translate-y-0.5 sm:px-5"
                    >
                        Register<span class="hidden min-[400px]:inline">&nbsp;free</span>
                    </Link>
                </template>

                <button
                    type="button"
                    class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-hh-soft transition-colors hover:bg-hh-line lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="landing-menu"
                    :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" class="h-[18px] w-[18px]" />
                    <Menu v-else class="h-[18px] w-[18px]" />
                </button>

                <div
                    v-if="menuOpen"
                    id="landing-menu"
                    class="absolute inset-x-0 top-full border-b border-hh-line bg-hh-shell px-5 pb-5 pt-2 shadow-hh sm:px-8 lg:hidden"
                >
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="flex min-h-[48px] items-center rounded-xl px-3 text-[15px] font-semibold text-hh-ink2 hover:bg-hh-soft hover:text-hh-ink"
                        @click="menuOpen = false"
                    >
                        {{ link.label }}
                    </a>
                    <div class="mt-2 flex gap-2 border-t border-hh-line pt-4 sm:hidden">
                        <button
                            type="button"
                            class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-hh-soft text-sm font-semibold"
                            @click="toggleTheme"
                        >
                            {{ isDark ? '☀️ Light mode' : '🌙 Dark mode' }}
                        </button>
                        <Link
                            v-if="!user"
                            :href="route('login')"
                            class="flex h-11 flex-1 items-center justify-center rounded-xl border border-hh-line text-sm font-bold"
                        >
                            Log in
                        </Link>
                    </div>
                </div>
            </header>

            <slot />

            <footer class="mx-5 mb-10 flex flex-wrap items-center gap-3.5 border-t border-hh-line pt-[26px] sm:mx-8 lg:mx-14">
                <div class="grid h-7 w-7 place-items-center rounded-[9px] bg-hh-coral text-white">
                    <House class="h-[15px] w-[15px]" :stroke-width="2.5" />
                </div>
                <span class="text-sm font-bold">HouseHub</span>
                <span class="text-[13px] text-hh-ink3">The home, organised.</span>
                <div class="flex flex-wrap gap-5 sm:ml-auto">
                    <Link v-for="link in footerLinks" :key="link.label" :href="link.href" class="text-[13.5px] text-hh-ink2 hover:opacity-75">{{
                        link.label
                    }}</Link>
                </div>
            </footer>
        </div>
    </div>
</template>
