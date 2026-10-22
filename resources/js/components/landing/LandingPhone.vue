<script setup lang="ts">
import InstallGuide from '@/components/landing/InstallGuide.vue';
import PhoneMockup from '@/components/landing/PhoneMockup.vue';

const appPerks = [
    { icon: '📲', title: 'On your home screen', body: 'Its own icon, opening full screen like any other app.' },
    { icon: '🔔', title: 'Notifications on your phone', body: 'Chores, meals and events, the moment they change. Pick which ones you get.' },
    { icon: '🚫', title: 'No app store', body: 'Nothing to download or update — it’s always the latest version.' },
    { icon: '📱', title: 'iPhone, iPad and Android', body: 'Plus any computer, all signed in to the same household.' },
];
</script>

<template>
    <section id="phone" class="scroll-mt-6 px-5 pt-[76px] sm:px-8 lg:px-14">
        <div class="grid grid-cols-1 items-start gap-12 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-14">
            <div class="order-2 lg:sticky lg:top-8 lg:order-1">
                <PhoneMockup />
            </div>

            <div class="order-1 lg:order-2">
                <div
                    class="inline-flex h-[30px] items-center gap-2 rounded-[9px] bg-ryh-soft px-[13px] text-xs font-bold uppercase tracking-[0.05em] text-ryh-ink2"
                >
                    📱 On your phone
                </div>
                <h2 class="mt-4 text-[30px] font-black leading-tight tracking-[-0.03em] sm:text-4xl">RYH lives on your home screen</h2>
                <p class="mt-3.5 max-w-[560px] text-pretty text-base leading-relaxed text-ryh-ink2">
                    Add it straight from your browser — no app store, no download. It opens like any other app, and tells you the moment a chore, meal
                    or event needs you.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-for="perk in appPerks" :key="perk.title" class="flex gap-3 rounded-[18px] border border-ryh-line bg-ryh-panel p-4">
                        <span class="text-[19px]" aria-hidden="true">{{ perk.icon }}</span>
                        <div>
                            <div class="text-[14.5px] font-extrabold tracking-tight">{{ perk.title }}</div>
                            <div class="mt-0.5 text-[13px] leading-normal text-ryh-ink2">{{ perk.body }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <InstallGuide />
                </div>
            </div>
        </div>
    </section>
</template>
