<script setup lang="ts">
import { clientToast } from '@/composables/useToast';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';

const DISMISS_AFTER_MS = 2600;

const page = usePage<SharedData>();
const message = ref<string | null>(null);
const isError = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

onUnmounted(() => clearTimeout(timer));

function show(toast: string, error = false): void {
    message.value = toast;
    isError.value = error;
    clearTimeout(timer);
    timer = setTimeout(() => (message.value = null), DISMISS_AFTER_MS);
}

watch(
    () => page.props.flash?.toast,
    (toast) => toast && show(toast),
    { immediate: true },
);

watch(
    () => page.props.flash?.toastError,
    (toast) => toast && show(toast, true),
    { immediate: true },
);

watch(clientToast, (toast) => toast && show(toast.message, toast.error));
</script>

<template>
    <div
        v-if="message"
        class="fixed bottom-24 left-1/2 z-50 flex animate-ryh-toast items-center gap-3 rounded-2xl bg-ryh-ink px-[22px] py-3.5 text-[13.5px] font-semibold text-ryh-shell shadow-[0_20px_44px_-18px_rgba(0,0,0,0.6)] sm:bottom-11"
        :role="isError ? 'alert' : 'status'"
        :aria-live="isError ? 'assertive' : 'polite'"
    >
        <span
            class="grid h-[21px] w-[21px] animate-ryh-pop place-items-center rounded-full text-[11px] text-[#0E1A2B]"
            :class="isError ? 'bg-ryh-coral' : 'bg-ryh-mint'"
            aria-hidden="true"
            >{{ isError ? '!' : '✓' }}</span
        >
        {{ message }}
    </div>
</template>
