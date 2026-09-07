<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { dial, formatPlanPrice, tickStyle, tint } from '@/lib/househub';
import type { SharedData } from '@/types';
import type { Plan } from '@/types/househub';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { House } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ plan: Plan }>();

const page = usePage<SharedData>();
const { appearance, updateAppearance } = useAppearance();

const isDark = computed(() => appearance.value === 'dark');
const user = computed(() => page.props.auth?.user);

const email = ref('');

const heroGradient = 'linear-gradient(118deg, var(--hh-hero1) 0%, var(--hh-hero2) 100%)';

const navLinks = [
    { label: 'Daily rhythm', href: '#daily' },
    { label: 'Features', href: '#features' },
    { label: 'Pricing', href: '#pricing' },
    { label: 'Promises', href: '#promises' },
];

const faces = [
    { initials: 'SP', colour: 'var(--hh-mint)' },
    { initials: 'JP', colour: 'var(--hh-lilac)' },
    { initials: 'MP', colour: 'var(--hh-sun)' },
    { initials: 'NP', colour: 'var(--hh-sky)' },
];

const demoList = [
    { name: 'Chicken thighs', qty: '1kg', done: false },
    { name: 'New potatoes', qty: '750g', done: false },
    { name: 'Lemons', qty: 'x3', done: true },
    { name: 'Oat milk', qty: 'x2', done: false },
];

const demoAgenda = [
    { time: '09:30', title: 'Dentist — Noah', colour: 'var(--hh-sky)' },
    { time: '16:00', title: 'Swim club pickup', colour: 'var(--hh-sun)' },
    { time: '19:00', title: 'Council tax £186', colour: 'var(--hh-coral)' },
];

const pillars = [
    {
        icon: '🍽',
        title: "What's for dinner?",
        body: "A week of meals planned in minutes, with whoever's cooking assigned. Drag a meal to another night and the shopping list follows.",
        proof: 'Answered before you reach the kitchen',
    },
    {
        icon: '📅',
        title: "What's on today?",
        body: 'Every appointment, club run and direct debit for the whole household on one timeline, colour-coded per person.',
        proof: 'One shared calendar, four phones',
    },
    {
        icon: '🛒',
        title: 'What do we need?',
        body: 'Lists that update live while someone else is mid-aisle, grouped the way the shop is laid out. Tesco, Aldi, DIY, Christmas.',
        proof: 'Shared lists, live as you shop',
    },
];

const secondary = [
    { icon: '💰', title: 'Budgeting', body: 'Household spend by category, month to month.' },
    { icon: '📂', title: 'Documents', body: 'A searchable vault for insurance, MOTs and tenancy paperwork.' },
    { icon: '✅', title: 'Chores', body: 'Streaks and a bit of friendly rivalry.' },
    { icon: '🔑', title: 'Guest access', body: 'Give cleaners, sitters or other helpers their own login.' },
    { icon: '✨', title: 'AI insights', body: 'Smart meal planning and household insights, automatically.' },
    { icon: '🏘', title: 'Multiple properties', body: 'Landlords and multi-home households, all under one login.' },
];

const promises = [
    {
        icon: '🌱',
        title: 'We are new, and we say so',
        body: 'HouseHub launched this year. There are no inflated user counts on this page because there is nothing yet to inflate — just software we use in our own home every morning.',
    },
    {
        icon: '🔒',
        title: 'Your household data is yours',
        body: 'No advertising, no selling shopping habits to supermarkets. Export everything, or delete the household outright, whenever you like.',
    },
    {
        icon: '📬',
        title: 'Founding households shape it',
        body: 'Early sign-ups get a direct line to the people building it, and keep their launch price for as long as they stay subscribed.',
    },
];

const footerLinks = ['Privacy', 'Terms', 'Support', 'Status'];

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}

function startRegistration(): void {
    router.get(route('register'), email.value ? { email: email.value } : {});
}
</script>

<template>
    <Head title="HouseHub — the home, organised" />

    <div class="min-h-screen bg-hh-bg text-hh-ink transition-colors">
        <div class="mx-auto max-w-[1440px] bg-hh-shell">
            <header class="sticky top-0 z-20 flex h-[76px] items-center gap-3 border-b border-hh-line bg-hh-shell px-5 sm:px-8 lg:px-14">
                <div class="flex items-center gap-[11px]">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-hh-coral text-white">
                        <House class="h-[19px] w-[19px]" :stroke-width="2.5" />
                    </div>
                    <span class="text-[17px] font-extrabold tracking-tight">HouseHub</span>
                </div>

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
                    class="h-10 w-10 rounded-xl bg-hh-soft text-[15px] transition-colors hover:bg-hh-line"
                    @click="toggleTheme"
                >
                    {{ isDark ? '☀️' : '🌙' }}
                </button>

                <Link
                    v-if="user"
                    :href="route('dashboard')"
                    class="flex h-10 items-center rounded-xl bg-hh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5"
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
                        class="flex h-10 items-center rounded-xl bg-hh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5"
                    >
                        Register free
                    </Link>
                </template>
            </header>

            <!-- Hero -->
            <section
                class="grid grid-cols-1 items-center gap-10 px-5 pb-16 pt-14 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-14 lg:px-14 lg:pt-[76px]"
            >
                <div class="animate-hh-rise">
                    <div
                        class="inline-flex h-[30px] items-center gap-2 rounded-[9px] bg-hh-soft px-[13px] text-xs font-bold uppercase tracking-[0.05em] text-hh-ink2"
                    >
                        <span class="h-[7px] w-[7px] animate-hh-glow rounded-full bg-hh-mint"></span>
                        First month free
                    </div>

                    <h1 class="mt-[22px] text-pretty text-[40px] font-black leading-[1.02] tracking-[-0.04em] sm:text-[52px] lg:text-[66px]">
                        The three questions every morning starts with.
                    </h1>

                    <p class="mt-[22px] max-w-[520px] text-pretty text-[17px] leading-relaxed text-hh-ink2 sm:text-lg">
                        What's for dinner? What's on today? What do we need to buy? HouseHub answers all three the second you open it — and quietly
                        keeps hold of everything else your home runs on.
                    </p>

                    <div class="mt-[30px]">
                        <Link
                            :href="route('register')"
                            class="inline-flex h-[54px] items-center rounded-[15px] bg-hh-coral px-[26px] text-[15.5px] font-bold text-white transition hover:-translate-y-0.5"
                        >
                            Create your household
                        </Link>
                    </div>

                    <div class="mt-[26px] flex flex-wrap items-center gap-3">
                        <div class="flex">
                            <span
                                v-for="face in faces"
                                :key="face.initials"
                                class="-mr-[9px] grid h-8 w-8 place-items-center rounded-[10px] border-2 border-hh-shell text-[11px] font-extrabold text-[#0E1A2B]"
                                :style="{ background: face.colour }"
                            >
                                {{ face.initials }}
                            </span>
                        </div>
                        <span class="ml-2 text-[13.5px] text-hh-ink3">Unlimited household members</span>
                    </div>
                </div>

                <!-- Dashboard preview -->
                <div class="relative animate-hh-rise">
                    <div class="absolute -top-8 right-10 h-[300px] w-[300px] rounded-full bg-hh-coral opacity-[0.16] blur-[50px]"></div>

                    <div class="relative rounded-3xl p-[22px] text-hh-onhero shadow-hh" :style="{ background: heroGradient }">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span class="h-[9px] w-[9px] rounded-full bg-hh-heroline"></span>
                            <span class="h-[9px] w-[9px] rounded-full bg-hh-heroline"></span>
                            <span class="h-[9px] w-[9px] rounded-full bg-hh-heroline"></span>
                            <span class="ml-2 text-xs text-hh-onhero2">Good morning, Sarah · Tuesday 5 August</span>
                            <span
                                class="ml-auto inline-flex h-6 items-center gap-1.5 rounded-lg border border-hh-heroline bg-hh-herofilm px-2.5 text-[11px] font-bold"
                            >
                                <span aria-hidden="true">🔥</span> 12 day streak
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1.25fr_1fr]">
                            <div class="rounded-[18px] bg-hh-card p-4 text-hh-ink">
                                <div class="text-xs font-bold uppercase tracking-[0.08em] text-hh-ink3">Tonight's dinner</div>
                                <div class="mt-3 flex gap-3.5">
                                    <div
                                        class="flex h-[104px] w-[104px] flex-none items-end rounded-[14px] bg-hh-coral p-3 text-lg font-extrabold leading-tight tracking-tight text-white"
                                    >
                                        Lemon chicken
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-col gap-[7px]">
                                        <div class="flex flex-wrap gap-1.5">
                                            <span class="rounded-lg bg-hh-soft px-[9px] py-1 text-[11.5px] font-semibold text-hh-ink2">45 min</span>
                                            <span class="rounded-lg bg-hh-soft px-[9px] py-1 text-[11.5px] font-semibold text-hh-ink2">Easy</span>
                                        </div>
                                        <div class="text-[13px] leading-normal text-hh-ink2">
                                            One tray. James is cooking. Everything's already in the fridge.
                                        </div>
                                        <div class="mt-auto flex items-center gap-[7px]">
                                            <span
                                                class="grid h-6 w-6 place-items-center rounded-lg bg-hh-lilac text-[10px] font-extrabold text-[#0E1A2B]"
                                            >
                                                JP
                                            </span>
                                            <span class="text-xs text-hh-ink3">James is cooking</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[18px] bg-hh-card p-4 text-hh-ink">
                                <div class="flex items-baseline gap-2">
                                    <div class="text-xs font-bold uppercase tracking-[0.08em] text-hh-ink3">Tesco list</div>
                                    <span class="ml-auto font-mono text-[11px] text-hh-ink3">7 left</span>
                                </div>
                                <div class="mt-2.5 flex flex-col gap-0.5">
                                    <div v-for="item in demoList" :key="item.name" class="flex min-h-[34px] items-center gap-2.5">
                                        <span
                                            class="grid h-[19px] w-[19px] flex-none place-items-center rounded-md border-[1.5px] text-[10px] text-[#0E1A2B]"
                                            :style="tickStyle(item.done)"
                                        >
                                            {{ item.done ? '✓' : '' }}
                                        </span>
                                        <span class="flex-1 text-[13.5px]" :class="item.done ? 'text-hh-ink3 line-through' : 'text-hh-ink'">
                                            {{ item.name }}
                                        </span>
                                        <span class="font-mono text-[11px] text-hh-ink3">{{ item.qty }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3.5 rounded-[18px] bg-hh-card p-4 text-hh-ink">
                                <div class="grid h-[60px] w-[60px] flex-none place-items-center rounded-full" :style="{ background: dial(50) }">
                                    <div class="flex h-[46px] w-[46px] items-center justify-center rounded-full bg-hh-card text-sm font-extrabold">
                                        50%
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[13.5px] font-extrabold">Today's chores</div>
                                    <div class="mt-1 text-[12.5px] text-hh-ink3">3 of 6 done · Noah's turn on bins</div>
                                </div>
                            </div>

                            <div class="rounded-[18px] bg-hh-card p-4 text-hh-ink">
                                <div class="text-xs font-bold uppercase tracking-[0.08em] text-hh-ink3">Coming up</div>
                                <div class="mt-2.5 flex flex-col gap-[5px]">
                                    <div
                                        v-for="event in demoAgenda"
                                        :key="event.title"
                                        class="flex items-center gap-2.5 rounded-[11px] bg-hh-sunk px-2.5 py-[7px]"
                                    >
                                        <span class="h-6 w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                                        <span class="w-11 flex-none font-mono text-[11px] text-hh-ink2">{{ event.time }}</span>
                                        <span class="flex-1 text-[12.5px] font-medium">{{ event.title }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- The daily three -->
            <section id="daily" class="px-5 pb-[72px] pt-5 sm:px-8 lg:px-14">
                <div class="mb-[22px] flex flex-wrap items-baseline gap-x-3.5 gap-y-2">
                    <h2 class="text-[28px] font-black tracking-[-0.03em] sm:text-[34px]">One glance, three answers</h2>
                    <span class="text-[15px] text-hh-ink3">The whole reason people open it before the kettle boils.</span>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(pillar, index) in pillars"
                        :key="pillar.title"
                        class="rounded-[22px] border border-hh-line bg-hh-card p-[26px] transition hover:-translate-y-[3px] hover:shadow-hh"
                    >
                        <div class="grid h-11 w-11 place-items-center rounded-[14px] text-[19px]" :style="{ background: tint(index) }">
                            <span aria-hidden="true">{{ pillar.icon }}</span>
                        </div>
                        <h3 class="mt-[18px] text-xl font-extrabold tracking-tight">{{ pillar.title }}</h3>
                        <p class="mt-2.5 text-pretty text-[14.5px] leading-relaxed text-hh-ink2">{{ pillar.body }}</p>
                        <div class="mt-[18px] border-t border-hh-line pt-4 text-[13px] font-semibold text-hh-ink3">{{ pillar.proof }}</div>
                    </div>
                </div>
            </section>

            <!-- Calendar joke -->
            <section class="px-5 pb-[72px] sm:px-8 lg:px-14">
                <div class="rounded-[22px] border border-hh-line bg-hh-card px-6 py-8 text-center sm:py-10">
                    <p class="text-[13px] font-bold uppercase tracking-[0.08em] text-hh-ink3">Old family motto</p>
                    <p class="mt-2.5 text-pretty text-2xl font-black tracking-[-0.02em] sm:text-3xl">
                        "If it's not on the calendar, it's not happening."
                    </p>
                    <p class="mt-3 text-[14.5px] text-hh-ink2">Neither is dinner, apparently. Ask us how we know.</p>
                </div>
            </section>

            <!-- Everything else -->
            <section
                id="features"
                class="relative mx-5 overflow-hidden rounded-[26px] p-8 text-hh-onhero sm:mx-8 sm:p-12 lg:mx-14"
                :style="{ background: heroGradient }"
            >
                <div class="absolute -bottom-[120px] left-[38%] h-[340px] w-[340px] rounded-full bg-hh-coral opacity-20 blur-[60px]"></div>

                <div class="relative grid grid-cols-1 items-center gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12">
                    <div>
                        <h2 class="text-[30px] font-black leading-tight tracking-[-0.03em] sm:text-4xl">Then it holds everything else</h2>
                        <p class="mt-3.5 text-pretty text-base leading-relaxed text-hh-onhero2">
                            Budgets, documents, guest access, AI insights. Nobody signs up for a filing cabinet — but once HouseHub is the app you
                            already open every morning, the boring things finally have a home.
                        </p>
                        <Link
                            :href="route('register')"
                            class="mt-6 inline-flex h-[50px] items-center rounded-[14px] bg-hh-onhero px-6 text-[15px] font-bold text-[#101C2E] transition hover:-translate-y-0.5"
                        >
                            Start with tonight's dinner
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                        <div
                            v-for="feature in secondary"
                            :key="feature.title"
                            class="rounded-[18px] border border-hh-heroline bg-hh-herofilm p-[18px] transition hover:-translate-y-[3px]"
                        >
                            <div class="text-[19px]" aria-hidden="true">{{ feature.icon }}</div>
                            <div class="mt-3 text-[15px] font-extrabold tracking-tight">{{ feature.title }}</div>
                            <div class="mt-1.5 text-[13px] leading-normal text-hh-onhero2">{{ feature.body }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing -->
            <section id="pricing" class="px-5 pb-6 pt-[76px] sm:px-8 lg:px-14">
                <div class="mb-6">
                    <h2 class="text-[28px] font-black tracking-[-0.03em] sm:text-[34px]">One price, everything included</h2>
                    <p class="mt-2.5 max-w-[560px] text-[15.5px] leading-relaxed text-hh-ink2">
                        {{ props.plan.sub }}
                    </p>
                </div>

                <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[400px_minmax(0,1fr)]">
                    <div class="relative flex flex-col overflow-hidden rounded-3xl p-8 text-hh-onhero" :style="{ background: heroGradient }">
                        <div class="absolute -right-[70px] -top-20 h-[280px] w-[280px] rounded-full bg-hh-coral opacity-20 blur-[46px]"></div>

                        <span
                            class="relative self-start rounded-[9px] bg-hh-coral px-2.5 py-[5px] text-[11.5px] font-extrabold uppercase tracking-[0.05em] text-white"
                        >
                            First month free
                        </span>

                        <div class="relative mt-[22px] flex items-baseline gap-2">
                            <span class="text-[46px] font-black leading-none tracking-[-0.045em] sm:text-[58px]">{{
                                formatPlanPrice(props.plan.priceMonthly)
                            }}</span>
                            <span class="text-sm font-semibold text-hh-onhero2">/month</span>
                        </div>

                        <div class="relative mt-1 text-[13.5px] font-semibold text-hh-onhero2">
                            or {{ formatPlanPrice(props.plan.priceAnnual) }}/year
                        </div>

                        <div class="relative mt-2.5 max-w-[290px] text-[14.5px] leading-relaxed text-hh-onhero2">
                            {{ props.plan.sub }}
                        </div>

                        <Link
                            :href="route('register')"
                            class="relative mt-6 flex h-[54px] items-center justify-center rounded-[15px] bg-hh-coral text-[15.5px] font-bold text-white transition hover:-translate-y-0.5"
                        >
                            {{ props.plan.cta }}
                        </Link>

                        <div class="relative mt-auto pt-6 text-[13px] text-hh-onhero2">{{ props.plan.note }}</div>
                    </div>

                    <div class="rounded-3xl border-[1.5px] border-hh-line bg-hh-card p-8">
                        <div class="text-[12.5px] font-extrabold uppercase tracking-[0.09em] text-hh-ink3">Everything included</div>
                        <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-3.5 sm:grid-cols-2">
                            <div v-for="feature in props.plan.features" :key="feature" class="flex items-start gap-2.5">
                                <span
                                    class="mt-0.5 grid h-[18px] w-[18px] flex-none place-items-center rounded-md bg-hh-mint text-[10px] font-extrabold text-[#0E1A2B]"
                                >
                                    ✓
                                </span>
                                <span class="text-sm leading-snug text-hh-ink">{{ feature }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Promises -->
            <section id="promises" class="grid grid-cols-1 gap-4 px-5 pb-[72px] pt-7 sm:px-8 md:grid-cols-2 lg:grid-cols-3 lg:px-14">
                <div v-for="(promise, index) in promises" :key="promise.title" class="rounded-[22px] border border-hh-line bg-hh-panel p-6">
                    <div class="grid h-[38px] w-[38px] place-items-center rounded-xl text-[17px]" :style="{ background: tint(index + 1) }">
                        <span aria-hidden="true">{{ promise.icon }}</span>
                    </div>
                    <div class="mt-4 text-base font-extrabold tracking-tight">{{ promise.title }}</div>
                    <p class="mt-2 text-pretty text-sm leading-relaxed text-hh-ink2">{{ promise.body }}</p>
                </div>
            </section>

            <!-- Final CTA -->
            <section
                class="mx-5 mb-[72px] flex flex-col items-start gap-8 rounded-[26px] border border-hh-line bg-hh-panel p-8 sm:mx-8 sm:p-14 lg:mx-14 lg:flex-row lg:items-center lg:gap-10"
            >
                <div class="flex-1">
                    <h2 class="text-[32px] font-black leading-[1.06] tracking-[-0.035em] sm:text-[42px]">Set it up before the kettle boils.</h2>
                    <p class="mt-3.5 max-w-[520px] text-[16.5px] leading-relaxed text-hh-ink2">
                        Create a household, invite the family, add tonight's dinner. Everything else can wait until you need it.
                    </p>
                </div>

                <form class="flex w-full flex-none flex-col gap-2.5 lg:w-[360px]" @submit.prevent="startRegistration">
                    <label for="cta-email" class="sr-only">Email address</label>
                    <input
                        id="cta-email"
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        placeholder="you@household.co.uk"
                        class="h-[54px] rounded-[15px] border border-hh-line bg-hh-card px-[18px] text-[15px] text-hh-ink placeholder:text-hh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hh-coral"
                    />
                    <button
                        type="submit"
                        class="h-[54px] rounded-[15px] bg-hh-coral text-[15.5px] font-bold text-white transition hover:-translate-y-0.5"
                    >
                        Create your household
                    </button>
                    <div class="text-center text-[12.5px] text-hh-ink3">First month free · no card · unlimited members</div>
                </form>
            </section>

            <footer class="mx-5 mb-10 flex flex-wrap items-center gap-3.5 border-t border-hh-line pt-[26px] sm:mx-8 lg:mx-14">
                <div class="grid h-7 w-7 place-items-center rounded-[9px] bg-hh-coral text-white">
                    <House class="h-[15px] w-[15px]" :stroke-width="2.5" />
                </div>
                <span class="text-sm font-bold">HouseHub</span>
                <span class="text-[13px] text-hh-ink3">The home, organised.</span>
                <div class="flex flex-wrap gap-5 sm:ml-auto">
                    <a v-for="link in footerLinks" :key="link" href="#" class="text-[13.5px] text-hh-ink2 hover:opacity-75">{{ link }}</a>
                </div>
            </footer>
        </div>
    </div>
</template>
