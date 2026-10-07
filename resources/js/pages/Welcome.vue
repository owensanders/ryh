<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatPlanPrice, tint } from '@/lib/househub';
import type { Plan } from '@/types/househub';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{ plan: Plan }>();

const email = ref('');

const heroGradient = 'linear-gradient(118deg, var(--hh-hero1) 0%, var(--hh-hero2) 100%)';

const faces = [
    { initials: 'SP', colour: 'var(--hh-mint)' },
    { initials: 'JP', colour: 'var(--hh-lilac)' },
    { initials: 'MP', colour: 'var(--hh-sun)' },
    { initials: 'NP', colour: 'var(--hh-sky)' },
];

// The hero's product tour autoplays muted (browsers block autoplay with sound),
// unless the visitor has asked for reduced motion.
const tourVideo = ref<HTMLVideoElement | null>(null);
const tourMuted = ref(true);

onMounted(() => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        tourVideo.value?.play().catch(() => {});
    }
});

function watchWithSound(): void {
    const video = tourVideo.value;
    if (!video) {
        return;
    }
    video.muted = false;
    video.currentTime = 0;
    video.play().catch(() => {});
    tourMuted.value = false;
}

const pillars = [
    {
        icon: '🍽',
        title: "What's for dinner?",
        body: "A week of dinners planned in minutes, with whoever's cooking assigned. Drag a meal to another night, or send a recipe's ingredients straight to a shopping list.",
        proof: 'Answered before you reach the kitchen',
    },
    {
        icon: '📅',
        title: "What's on today?",
        body: "Every appointment, club run and bill date for the whole household on one shared calendar, with who's going on each.",
        proof: 'One shared calendar for the whole house',
    },
    {
        icon: '🛒',
        title: 'What do we need?',
        body: 'As many named lists as you need — Tesco, Aldi, DIY, Christmas. Everyone sees the same list and ticks things off as they shop.',
        proof: 'Shared lists for every shop',
    },
];

const secondary = [
    { icon: '📖', title: 'Recipe library', body: 'Family favourites with ingredients, tags and cook times, ready to plan.' },
    { icon: '✨', title: 'AI meal plans', body: "Say who's eating and what to avoid, and get a week of dinners." },
    { icon: '✅', title: 'Chores', body: "Assign jobs, see who's pulling their weight, and nudge everyone in one tap." },
    { icon: '🔔', title: 'Live notifications', body: 'Know the moment a chore, meal or event is assigned to you, changed or cancelled.' },
    { icon: '💰', title: 'Budgeting', body: 'Income and planned spending by category, month to month.' },
    { icon: '📂', title: 'Documents', body: 'Insurance, MOTs and tenancy paperwork in folders, tagged and searchable.' },
    { icon: '👪', title: 'Family roles', body: 'Owner, Adult, Teen and Child access, so kids tick off and grown-ups manage.' },
    { icon: '✉️', title: 'Easy invites', body: 'Invite by email, or share a join code and approve who gets in.' },
    {
        icon: '🏘',
        title: 'Multiple homes',
        body: 'Two households, or helping run a parent’s? Switch between them under one login — each home has its own subscription.',
    },
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
        body: "No advertising, no selling shopping habits to supermarkets. Close your account whenever you like, and if you are the household's only owner, everything in it goes too.",
    },
    {
        icon: '📬',
        title: 'Founding households shape it',
        body: 'Early sign-ups get a direct line to the people building it, and keep their launch price for as long as they stay subscribed.',
    },
];

function startRegistration(): void {
    router.get(route('register'), email.value ? { email: email.value } : {});
}
</script>

<template>
    <Head title="HouseHub — the home, organised" />

    <PublicLayout>
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
                    What's for dinner? What's on today? What do we need to buy? HouseHub answers all three the second you open it — and quietly keeps
                    hold of everything else your home runs on.
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
                    <span class="ml-2 text-[13.5px] text-hh-ink3">Up to {{ props.plan.maxMembers }} people per household</span>
                </div>
            </div>

            <!-- Product tour video -->
            <div class="relative animate-hh-rise">
                <div class="absolute -top-8 right-10 h-[300px] w-[300px] rounded-full bg-hh-coral opacity-[0.16] blur-[50px]"></div>

                <div class="relative overflow-hidden rounded-3xl shadow-hh" :style="{ background: heroGradient }">
                    <video
                        ref="tourVideo"
                        class="block aspect-video w-full"
                        src="/videos/househub-promo.mp4"
                        poster="/videos/househub-promo-poster.jpg"
                        aria-label="HouseHub product tour"
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        :controls="!tourMuted"
                    ></video>

                    <button
                        v-if="tourMuted"
                        type="button"
                        class="group absolute inset-0 flex items-end p-4"
                        aria-label="Watch the product tour with sound"
                        @click="watchWithSound"
                    >
                        <span
                            class="flex h-11 items-center gap-2 rounded-[13px] bg-hh-coral px-4 text-sm font-bold text-white shadow-hh transition group-hover:-translate-y-0.5"
                        >
                            <span aria-hidden="true">▶</span> Watch with sound
                        </span>
                    </button>
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
                        Recipes, chores, budgets, documents. Nobody signs up for a filing cabinet — but once HouseHub is the app you already open
                        every morning, the boring things finally have a home.
                    </p>
                    <Link
                        :href="route('register')"
                        class="mt-6 inline-flex h-[50px] items-center rounded-[14px] bg-hh-onhero px-6 text-[15px] font-bold text-[#101C2E] transition hover:-translate-y-0.5"
                    >
                        Start with tonight's dinner
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
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

                    <div class="relative mt-1 text-[13.5px] font-semibold text-hh-onhero2">or {{ formatPlanPrice(props.plan.priceAnnual) }}/year</div>

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
                <div class="text-center text-[12.5px] text-hh-ink3">First month free · no card · up to {{ props.plan.maxMembers }} members</div>
            </form>
        </section>
    </PublicLayout>
</template>
