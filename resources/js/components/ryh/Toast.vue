<script setup lang="ts">
import { latestToast } from '@/composables/useNotifications';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';

const DISMISS_AFTER_MS = 2600;

const page = usePage<SharedData>();
const message = ref<string | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

onUnmounted(() => clearTimeout(timer));

function show(toast: string): void {
    message.value = toast;
    clearTimeout(timer);
    timer = setTimeout(() => (message.value = null), DISMISS_AFTER_MS);
}

watch(
    () => page.props.flash?.toast,
    (toast) => toast && show(toast),
    { immediate: true },
);

watch(latestToast, (toast) => toast && show(toast));
</script>

<template>
    <div
        v-if="message"
        class="fixed bottom-24 left-1/2 z-50 flex animate-ryh-toast items-center gap-3 rounded-2xl bg-ryh-ink px-[22px] py-3.5 text-[13.5px] font-semibold text-ryh-shell shadow-[0_20px_44px_-18px_rgba(0,0,0,0.6)] sm:bottom-11"
        role="status"
        aria-live="polite"
    >
        <span class="grid h-[21px] w-[21px] animate-ryh-pop place-items-center rounded-full bg-ryh-mint text-[11px] text-[#0E1A2B]">✓</span>
        {{ message }}
    </div>
</template>
