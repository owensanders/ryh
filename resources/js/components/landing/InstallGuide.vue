<script setup lang="ts">
import { isIos } from '@/lib/pwa';
import { onMounted, ref } from 'vue';

type Platform = 'iphone' | 'android';

const platforms: { id: Platform; label: string; browser: string; steps: { title: string; body: string }[] }[] = [
    {
        id: 'iphone',
        label: 'iPhone & iPad',
        browser: 'Safari',
        steps: [
            { title: 'Open HouseHub in Safari', body: 'Go to this site in Safari and sign in.' },
            { title: 'Tap Share', body: 'The square with an arrow pointing up, in Safari’s toolbar.' },
            { title: 'Choose Add to Home Screen', body: 'Scroll down the share sheet if you can’t see it, then tap Add.' },
            {
                title: 'Open it and turn on notifications',
                body: 'Open HouseHub from your home screen, then go to Settings → Notifications and tap Turn on. On iPhone, notifications only work from the home-screen app.',
            },
        ],
    },
    {
        id: 'android',
        label: 'Android',
        browser: 'Chrome',
        steps: [
            { title: 'Open HouseHub in Chrome', body: 'Go to this site in Chrome and sign in.' },
            {
                title: 'Tap Install',
                body: 'Use the “Get the HouseHub app” card that appears, or open Chrome’s ⋮ menu and choose Install app (or Add to Home screen).',
            },
            { title: 'Confirm', body: 'HouseHub is added to your home screen and app drawer.' },
            { title: 'Turn on notifications', body: 'Open HouseHub, go to Settings → Notifications and tap Turn on.' },
        ],
    },
];

// Start on the visitor's own kind of phone.
const active = ref<Platform>('android');
onMounted(() => {
    if (isIos()) {
        active.value = 'iphone';
    }
});
</script>

<template>
    <div class="rounded-[22px] border border-hh-line bg-hh-card p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-extrabold tracking-tight">Install it in under a minute</h3>
            <div class="inline-flex gap-1 rounded-[13px] border border-hh-line bg-hh-panel p-1" role="tablist" aria-label="Your phone">
                <button
                    v-for="platform in platforms"
                    :id="`install-tab-${platform.id}`"
                    :key="platform.id"
                    type="button"
                    role="tab"
                    :aria-selected="active === platform.id"
                    :aria-controls="`install-panel-${platform.id}`"
                    class="rounded-[10px] px-3.5 py-1.5 text-[13px] transition-colors"
                    :class="active === platform.id ? 'bg-hh-card font-bold text-hh-ink shadow-hh' : 'font-medium text-hh-ink2 hover:bg-hh-soft'"
                    @click="active = platform.id"
                >
                    {{ platform.label }}
                </button>
            </div>
        </div>

        <template v-for="platform in platforms" :key="platform.id">
            <div
                v-if="active === platform.id"
                :id="`install-panel-${platform.id}`"
                role="tabpanel"
                :aria-labelledby="`install-tab-${platform.id}`"
                class="mt-5"
            >
                <p class="text-[13px] text-hh-ink3">Using {{ platform.browser }}:</p>
                <ol class="mt-3 flex flex-col gap-3">
                    <li v-for="(step, index) in platform.steps" :key="step.title" class="flex gap-3">
                        <span class="grid h-7 w-7 flex-none place-items-center rounded-[9px] bg-hh-coral text-[13px] font-extrabold text-white">
                            {{ index + 1 }}
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <div class="text-[14.5px] font-bold">{{ step.title }}</div>
                            <div class="mt-0.5 text-[13.5px] leading-relaxed text-hh-ink2">{{ step.body }}</div>
                        </div>
                    </li>
                </ol>
            </div>
        </template>
    </div>
</template>
