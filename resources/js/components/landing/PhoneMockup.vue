<script setup lang="ts">
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });

// A lock screen with RYH push notifications on it, worded exactly as the
// app sends them (app/Notifications/*). Drawn in CSS so it stays sharp, themes
// with the page and needs no screenshot to keep current.
const notifications = [
    { household: 'The Parkers', body: 'James Parker assigned you “Take the bins out”', when: 'now' },
    { household: 'The Parkers', body: 'Mia Parker completed “Feed the cat”', when: '4m ago' },
    { household: 'The Parkers', body: 'James Parker updated “Swim club pickup”', when: '12m ago' },
];
</script>

<template>
    <div class="relative mx-auto w-[280px] sm:w-[300px]" aria-hidden="true">
        <div class="absolute -inset-10 rounded-full bg-ryh-coral opacity-[0.18] blur-[60px]"></div>

        <div class="relative rounded-[46px] bg-[#0b0e15] p-[11px] shadow-[0_40px_80px_-30px_rgba(14,26,43,0.7)] ring-1 ring-white/10">
            <div
                class="relative flex aspect-[9/19.5] flex-col overflow-hidden rounded-[36px] px-3.5 pb-5 pt-3"
                style="background: linear-gradient(160deg, #1b2f4a 0%, #101c2e 55%, #2a1840 100%)"
            >
                <!-- Status bar and dynamic island -->
                <div class="flex items-center justify-between px-3 text-[11px] font-semibold text-white/90">
                    <span>07:42</span>
                    <span class="h-[22px] w-[78px] rounded-full bg-black"></span>
                    <span class="tracking-tight">5G ▮</span>
                </div>

                <div class="mt-7 text-center text-white">
                    <div class="text-[13px] font-semibold text-white/70">{{ today }}</div>
                    <div class="text-[64px] font-extrabold leading-none tracking-[-0.04em]">07:42</div>
                </div>

                <div class="mt-auto flex flex-col gap-2">
                    <div
                        v-for="(notification, index) in notifications"
                        :key="index"
                        class="flex animate-ryh-rise gap-2.5 rounded-[18px] bg-white/[0.82] p-2.5 text-[#0e1a2b] backdrop-blur"
                        :style="{ animationDelay: `${index * 120}ms`, animationFillMode: 'backwards' }"
                    >
                        <img src="/icons/icon.svg" alt="" class="h-[30px] w-[30px] flex-none rounded-[9px]" />
                        <div class="min-w-0 flex-1 leading-snug">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-[12px] font-bold">{{ notification.household }}</span>
                                <span class="flex-none text-[10.5px] text-[#0e1a2b]/50">{{ notification.when }}</span>
                            </div>
                            <div class="text-[11.5px] text-[#0e1a2b]/75">{{ notification.body }}</div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto mt-4 h-[4px] w-[96px] rounded-full bg-white/60"></div>
            </div>
        </div>
    </div>
</template>
