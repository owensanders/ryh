<script setup lang="ts">
import { HERO_GRADIENT, tint } from '@/lib/ryh';

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
    {
        icon: '🔔',
        title: 'Live notifications',
        body: 'Know the moment a chore, meal or event is assigned to you, changed or cancelled — in the app and on your phone.',
    },
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
</script>

<template>
    <!-- The sections the header links to each set scroll-mt so their content
         lands 24px below the 76px sticky header: 100px less their top padding. -->

    <!-- The daily three -->
    <section id="daily" class="scroll-mt-20 px-5 pb-[72px] pt-5 sm:px-8 lg:px-14">
        <div class="mb-[22px] flex flex-wrap items-baseline gap-x-3.5 gap-y-2">
            <h2 class="text-[28px] font-black tracking-[-0.03em] sm:text-[34px]">One glance, three answers</h2>
            <span class="text-[15px] text-ryh-ink3">The whole reason people open it before the kettle boils.</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="(pillar, index) in pillars"
                :key="pillar.title"
                class="rounded-[22px] border border-ryh-line bg-ryh-card p-[26px] transition hover:-translate-y-[3px] hover:shadow-ryh"
            >
                <div class="grid h-11 w-11 place-items-center rounded-[14px] text-[19px]" :style="{ background: tint(index) }">
                    <span aria-hidden="true">{{ pillar.icon }}</span>
                </div>
                <h3 class="mt-[18px] text-xl font-extrabold tracking-tight">{{ pillar.title }}</h3>
                <p class="mt-2.5 text-pretty text-[14.5px] leading-relaxed text-ryh-ink2">{{ pillar.body }}</p>
                <div class="mt-[18px] border-t border-ryh-line pt-4 text-[13px] font-semibold text-ryh-ink3">{{ pillar.proof }}</div>
            </div>
        </div>
    </section>

    <!-- Calendar joke -->
    <section class="px-5 pb-[72px] sm:px-8 lg:px-14">
        <div class="rounded-[22px] border border-ryh-line bg-ryh-card px-6 py-8 text-center sm:py-10">
            <p class="text-[13px] font-bold uppercase tracking-[0.08em] text-ryh-ink3">Old family motto</p>
            <p class="mt-2.5 text-pretty text-2xl font-black tracking-[-0.02em] sm:text-3xl">"If it's not on the calendar, it's not happening."</p>
            <p class="mt-3 text-[14.5px] text-ryh-ink2">Neither is dinner, apparently. Ask us how we know.</p>
        </div>
    </section>

    <!-- Everything else -->
    <section
        id="features"
        class="relative mx-5 scroll-mt-[100px] overflow-hidden rounded-[26px] p-8 text-ryh-onhero sm:mx-8 sm:p-12 lg:mx-14"
        :style="{ background: HERO_GRADIENT }"
    >
        <div class="absolute -bottom-[120px] left-[38%] h-[340px] w-[340px] rounded-full bg-ryh-coral opacity-20 blur-[60px]"></div>

        <div class="relative grid grid-cols-1 items-center gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12">
            <div>
                <h2 class="text-[30px] font-black leading-tight tracking-[-0.03em] sm:text-4xl">Then it holds everything else</h2>
                <p class="mt-3.5 text-pretty text-base leading-relaxed text-ryh-onhero2">
                    Recipes, chores, budgets, documents. Nobody signs up for a filing cabinet — but once RYH is the app you already open every
                    morning, the boring things finally have a home.
                </p>
                <Link
                    :href="route('register')"
                    class="mt-6 inline-flex h-[50px] items-center rounded-[14px] bg-ryh-onhero px-6 text-[15px] font-bold text-[#101C2E] transition hover:-translate-y-0.5"
                >
                    Start with tonight's dinner
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in secondary"
                    :key="feature.title"
                    class="rounded-[18px] border border-ryh-heroline bg-ryh-herofilm p-[18px] transition hover:-translate-y-[3px]"
                >
                    <div class="text-[19px]" aria-hidden="true">{{ feature.icon }}</div>
                    <div class="mt-3 text-[15px] font-extrabold tracking-tight">{{ feature.title }}</div>
                    <div class="mt-1.5 text-[13px] leading-normal text-ryh-onhero2">{{ feature.body }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
