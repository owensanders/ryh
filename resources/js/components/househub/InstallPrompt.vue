<script setup lang="ts">
import { installPrompt, isIos, isStandalone, promptInstall } from '@/lib/pwa';
import { House, Share, SquarePlus, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

// "Not now" hides the card for a while rather than for good.
const DISMISS_KEY = 'pwa-install-dismissed-at';
const DISMISS_FOR_MS = 30 * 24 * 60 * 60 * 1000;

const dismissed = ref(true);
const ios = ref(false);

onMounted(() => {
    if (isStandalone()) {
        return;
    }

    ios.value = isIos();
    const dismissedAt = Number(localStorage.getItem(DISMISS_KEY) ?? 0);
    dismissed.value = Date.now() - dismissedAt < DISMISS_FOR_MS;
});

// Android/desktop Chrome offer a real install dialog; iOS needs the manual guide.
const visible = computed(() => !dismissed.value && (installPrompt.value !== null || ios.value));

function dismiss(): void {
    dismissed.value = true;
    localStorage.setItem(DISMISS_KEY, String(Date.now()));
}

async function install(): Promise<void> {
    if (await promptInstall()) {
        dismissed.value = true;
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-6 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-6 opacity-0"
    >
        <section
            v-if="visible"
            aria-label="Install HouseHub"
            class="fixed inset-x-3 bottom-[calc(74px+env(safe-area-inset-bottom))] z-30 rounded-3xl border border-hh-line bg-hh-card p-4 shadow-hh sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-[360px]"
        >
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-hh-coral text-white">
                    <House class="h-5 w-5" :stroke-width="2.5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[15px] font-extrabold tracking-tight">Get the HouseHub app</div>
                    <p class="mt-0.5 text-[13px] text-hh-ink2">Add it to your home screen for one-tap access to the household.</p>
                </div>
                <button
                    type="button"
                    aria-label="Not now"
                    class="-mr-1 -mt-1 grid h-8 w-8 flex-none place-items-center rounded-lg text-hh-ink3 hover:bg-hh-soft"
                    @click="dismiss"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <ol v-if="ios && !installPrompt" class="mt-3 flex flex-col gap-2 text-[13px] text-hh-ink2">
                <li class="flex items-center gap-2.5">
                    <span class="grid h-7 w-7 flex-none place-items-center rounded-lg bg-hh-soft text-hh-ink"><Share class="h-4 w-4" /></span>
                    <span>Tap <strong class="text-hh-ink">Share</strong> in Safari's toolbar</span>
                </li>
                <li class="flex items-center gap-2.5">
                    <span class="grid h-7 w-7 flex-none place-items-center rounded-lg bg-hh-soft text-hh-ink"><SquarePlus class="h-4 w-4" /></span>
                    <span>Choose <strong class="text-hh-ink">Add to Home Screen</strong></span>
                </li>
            </ol>

            <div v-else class="mt-3 flex gap-2">
                <button type="button" class="hh-btn flex-1 bg-hh-soft text-hh-ink" @click="dismiss">Not now</button>
                <button type="button" class="hh-btn flex-1 bg-hh-coral text-white" @click="install">Install</button>
            </div>
        </section>
    </Transition>
</template>
