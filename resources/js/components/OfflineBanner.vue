<script setup lang="ts">
import { offline } from '@/lib/offline';
import { WifiOff } from 'lucide-vue-next';
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="-translate-y-4 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="-translate-y-4 opacity-0"
    >
        <div
            v-if="offline"
            role="status"
            aria-live="polite"
            class="fixed inset-x-3 top-[calc(env(safe-area-inset-top)+12px)] z-[60] mx-auto flex max-w-md items-center gap-3 rounded-2xl bg-ryh-ink px-4 py-3 text-ryh-shell shadow-[0_20px_44px_-18px_rgba(0,0,0,0.6)]"
        >
            <WifiOff class="h-4 w-4 flex-none" />
            <div class="min-w-0 text-[13px] leading-snug">
                <div class="font-bold">You're offline</div>
                <div class="opacity-75">RYH needs a connection. It'll carry on as soon as you're back online.</div>
            </div>
        </div>
    </Transition>
</template>
