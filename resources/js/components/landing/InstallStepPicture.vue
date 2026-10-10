<script lang="ts">
export type InstallScene = 'safari-site' | 'safari-share' | 'share-sheet' | 'chrome-site' | 'chrome-install' | 'install-confirm' | 'notifications';
</script>

<script setup lang="ts">
import TapHint from '@/components/landing/TapHint.vue';
import RyhLogo from '@/components/ryh/RyhLogo.vue';
import { BookOpen, ChevronLeft, ChevronRight, Copy, EllipsisVertical, Lock, Share, SquarePlus } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * A simplified drawing of the phone screen for one install step, with the thing
 * to tap ringed in coral. Drawn rather than screenshotted so it stays in the
 * app's palette and doesn't date with each iOS/Android release. Purely visual:
 * the step's text says the same thing.
 */
defineProps<{ scene: InstallScene }>();

// The address bar shows this site, as the visitor's browser will.
const host = computed(() => (typeof window === 'undefined' ? '' : window.location.hostname.replace(/^www\./, '')));
</script>

<template>
    <div class="relative h-[150px] overflow-hidden rounded-[18px] border border-ryh-line bg-ryh-shell text-ryh-ink" aria-hidden="true">
        <!-- Safari: the site open, address bar at the bottom -->
        <template v-if="scene === 'safari-site' || scene === 'safari-share'">
            <div class="px-4 pt-3">
                <RyhLogo class="h-5" />
                <div class="mt-3 h-2 w-3/4 rounded-full bg-ryh-soft"></div>
                <div class="mt-2 h-2 w-1/2 rounded-full bg-ryh-soft"></div>
            </div>
            <div class="absolute inset-x-0 bottom-0 border-t border-ryh-line bg-ryh-panel px-3 pb-2 pt-2">
                <div
                    class="flex h-7 items-center justify-center gap-1.5 rounded-[10px] bg-ryh-card text-[11px] font-semibold text-ryh-ink2"
                    :class="scene === 'safari-site' ? 'ring-2 ring-ryh-coral ring-offset-2 ring-offset-ryh-panel' : ''"
                >
                    <Lock class="h-3 w-3" /> {{ host }}
                </div>
                <div class="mt-2 flex items-center justify-between px-2 text-ryh-ink2">
                    <ChevronLeft class="h-4 w-4" />
                    <ChevronRight class="h-4 w-4 opacity-40" />
                    <span
                        class="relative grid h-7 w-7 place-items-center rounded-lg"
                        :class="scene === 'safari-share' ? 'bg-ryh-card text-ryh-coral ring-2 ring-ryh-coral' : ''"
                    >
                        <Share class="h-4 w-4" />
                        <TapHint v-if="scene === 'safari-share'" />
                    </span>
                    <BookOpen class="h-4 w-4" />
                    <Copy class="h-4 w-4" />
                </div>
            </div>
        </template>

        <!-- Safari's share sheet -->
        <template v-else-if="scene === 'share-sheet'">
            <div class="absolute inset-x-2 bottom-0 top-3 rounded-t-[16px] border border-b-0 border-ryh-line bg-ryh-panel px-3 pt-2.5">
                <div class="mx-auto h-1 w-8 rounded-full bg-ryh-line"></div>
                <div class="mt-2.5 flex flex-col gap-1.5 text-[12px] font-semibold">
                    <div class="flex h-7 items-center justify-between rounded-[9px] bg-ryh-card px-2.5 text-ryh-ink2">
                        Copy <Copy class="h-3.5 w-3.5" />
                    </div>
                    <div class="flex h-7 items-center justify-between rounded-[9px] bg-ryh-card px-2.5 text-ryh-ink2">
                        Add to Reading List <BookOpen class="h-3.5 w-3.5" />
                    </div>
                    <div class="relative flex h-7 items-center justify-between rounded-[9px] bg-ryh-card px-2.5 text-ryh-ink ring-2 ring-ryh-coral">
                        Add to Home Screen <SquarePlus class="h-3.5 w-3.5 text-ryh-coral" />
                        <TapHint />
                    </div>
                </div>
            </div>
        </template>

        <!-- Chrome: the site open, address bar at the top, and our install card -->
        <template v-else-if="scene === 'chrome-site' || scene === 'chrome-install'">
            <div class="flex items-center gap-2 border-b border-ryh-line bg-ryh-panel px-3 py-2">
                <div
                    class="flex h-7 flex-1 items-center gap-1.5 rounded-full bg-ryh-card px-3 text-[11px] font-semibold text-ryh-ink2"
                    :class="scene === 'chrome-site' ? 'ring-2 ring-ryh-coral ring-offset-2 ring-offset-ryh-panel' : ''"
                >
                    <Lock class="h-3 w-3" /> {{ host }}
                </div>
                <EllipsisVertical class="h-4 w-4 text-ryh-ink2" />
            </div>
            <div class="px-4 pt-3">
                <RyhLogo class="h-5" />
                <div class="mt-3 h-2 w-3/4 rounded-full bg-ryh-soft"></div>
            </div>
            <div
                v-if="scene === 'chrome-install'"
                class="absolute inset-x-2.5 bottom-2.5 flex items-center gap-2 rounded-[14px] border border-ryh-line bg-ryh-card p-2 shadow-ryh"
            >
                <img src="/icons/icon.svg" alt="" class="h-7 w-7 flex-none rounded-lg ring-1 ring-ryh-line" />
                <div class="min-w-0 flex-1 truncate text-[11.5px] font-extrabold tracking-tight">Get the RYH app</div>
                <span
                    class="relative rounded-[9px] bg-ryh-coral px-2.5 py-1 text-[11px] font-bold text-white ring-2 ring-ryh-coral ring-offset-2 ring-offset-ryh-card"
                >
                    Install
                    <TapHint />
                </span>
            </div>
        </template>

        <!-- Chrome's install confirmation -->
        <template v-else-if="scene === 'install-confirm'">
            <div class="bg-ryh-ink/10 absolute inset-0"></div>
            <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 rounded-[16px] border border-ryh-line bg-ryh-card p-3 shadow-ryh">
                <div class="text-[12.5px] font-extrabold">Install app</div>
                <div class="mt-2 flex items-center gap-2">
                    <img src="/icons/icon.svg" alt="" class="h-7 w-7 flex-none rounded-lg ring-1 ring-ryh-line" />
                    <div class="min-w-0 leading-tight">
                        <div class="text-[12px] font-bold">RYH</div>
                        <div class="truncate text-[10.5px] text-ryh-ink3">{{ host }}</div>
                    </div>
                </div>
                <div class="mt-2.5 flex justify-end gap-3 text-[11.5px] font-bold">
                    <span class="py-1 text-ryh-ink2">Cancel</span>
                    <span class="relative rounded-[8px] px-2 py-1 text-ryh-coral ring-2 ring-ryh-coral">
                        Install
                        <TapHint />
                    </span>
                </div>
            </div>
        </template>

        <!-- The home-screen app, then Settings → Notifications → Turn on -->
        <template v-else>
            <div class="flex h-full items-center justify-center gap-3 px-3">
                <div class="flex flex-col items-center gap-1">
                    <img src="/icons/icon.svg" alt="" class="h-11 w-11 rounded-[12px] shadow-sm ring-1 ring-ryh-line" />
                    <span class="text-[10.5px] font-bold">RYH</span>
                </div>
                <ChevronRight class="h-4 w-4 flex-none text-ryh-ink3" />
                <div class="w-[150px] rounded-[14px] border border-ryh-line bg-ryh-card p-2.5">
                    <div class="text-[10.5px] font-semibold text-ryh-ink3">Settings → Notifications</div>
                    <div class="mt-1 text-[12px] font-extrabold">This device</div>
                    <div
                        class="relative mt-2 rounded-[9px] bg-ryh-coral py-1.5 text-center text-[11.5px] font-bold text-white ring-2 ring-ryh-coral ring-offset-2 ring-offset-ryh-card"
                    >
                        Turn on
                        <TapHint />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
