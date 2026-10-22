<script setup lang="ts">
import { tint } from '@/lib/ryh';

const promises = [
    {
        icon: '🌱',
        title: 'We are new, and we say so',
        body: 'RYH launched this year. There are no inflated user counts on this page because there is nothing yet to inflate — just software we use in our own home every morning.',
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
</script>

<template>
    <section id="promises" class="grid scroll-mt-[72px] grid-cols-1 gap-4 px-5 pb-[72px] pt-7 sm:px-8 md:grid-cols-2 lg:grid-cols-3 lg:px-14">
        <div v-for="(promise, index) in promises" :key="promise.title" class="rounded-[22px] border border-ryh-line bg-ryh-panel p-6">
            <div class="grid h-[38px] w-[38px] place-items-center rounded-xl text-[17px]" :style="{ background: tint(index + 1) }">
                <span aria-hidden="true">{{ promise.icon }}</span>
            </div>
            <div class="mt-4 text-base font-extrabold tracking-tight">{{ promise.title }}</div>
            <p class="mt-2 text-pretty text-sm leading-relaxed text-ryh-ink2">{{ promise.body }}</p>
        </div>
    </section>
</template>
