<script setup lang="ts">
import InstallStepPicture, { type InstallScene } from '@/components/landing/InstallStepPicture.vue';
import { isAndroid } from '@/lib/pwa';
import { onMounted, ref } from 'vue';

type Platform = 'iphone' | 'android';

const platforms: { id: Platform; label: string; browser: string; steps: { title: string; body: string; scene: InstallScene }[] }[] = [
    {
        id: 'iphone',
        label: 'iPhone & iPad',
        browser: 'Safari',
        steps: [
            { title: 'Open RYH in Safari', body: 'Go to this site in Safari and sign in.', scene: 'safari-site' },
            { title: 'Tap Share', body: 'The square with an arrow pointing up, in Safari’s toolbar.', scene: 'safari-share' },
            { title: 'Choose Add to Home Screen', body: 'Scroll down the share sheet if you can’t see it, then tap Add.', scene: 'share-sheet' },
            {
                title: 'Open it and turn on notifications',
                body: 'Open RYH from your home screen, then go to Settings → Notifications and tap Turn on. On iPhone, notifications only work from the home-screen app.',
                scene: 'notifications',
            },
        ],
    },
    {
        id: 'android',
        label: 'Android',
        browser: 'Chrome',
        steps: [
            { title: 'Open RYH in Chrome', body: 'Go to this site in Chrome and sign in.', scene: 'chrome-site' },
            {
                title: 'Tap Install',
                body: 'Use the “Get the RYH app” card that appears, or open Chrome’s ⋮ menu and choose Install app (or Add to Home screen).',
                scene: 'chrome-install',
            },
            { title: 'Confirm', body: 'Tap Install. RYH is added to your home screen and app drawer.', scene: 'install-confirm' },
            { title: 'Turn on notifications', body: 'Open RYH, go to Settings → Notifications and tap Turn on.', scene: 'notifications' },
        ],
    },
];

// Start on iPhone & iPad (the steps most people need walking through), or on
// Android for someone already browsing on an Android phone.
const active = ref<Platform>('iphone');
onMounted(() => {
    if (isAndroid()) {
        active.value = 'android';
    }
});
</script>

<template>
    <div class="rounded-[22px] border border-ryh-line bg-ryh-card p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-extrabold tracking-tight">Install it in under a minute</h3>
            <div class="inline-flex gap-1 rounded-[13px] border border-ryh-line bg-ryh-panel p-1" role="tablist" aria-label="Your phone">
                <button
                    v-for="platform in platforms"
                    :id="`install-tab-${platform.id}`"
                    :key="platform.id"
                    type="button"
                    role="tab"
                    :aria-selected="active === platform.id"
                    :aria-controls="`install-panel-${platform.id}`"
                    class="rounded-[10px] px-3.5 py-1.5 text-[13px] transition-colors"
                    :class="active === platform.id ? 'bg-ryh-card font-bold text-ryh-ink shadow-ryh' : 'font-medium text-ryh-ink2 hover:bg-ryh-soft'"
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
                <p class="text-[13px] text-ryh-ink3">Using {{ platform.browser }}:</p>
                <ol class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <li v-for="(step, index) in platform.steps" :key="step.title" class="flex flex-col gap-3">
                        <InstallStepPicture :scene="step.scene" />
                        <div class="flex gap-3">
                            <span class="grid h-7 w-7 flex-none place-items-center rounded-[9px] bg-ryh-coral text-[13px] font-extrabold text-white">
                                {{ index + 1 }}
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <div class="text-[14.5px] font-bold">{{ step.title }}</div>
                                <div class="mt-0.5 text-[13.5px] leading-relaxed text-ryh-ink2">{{ step.body }}</div>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </template>
    </div>
</template>
