<script setup lang="ts">
import { HERO_GRADIENT } from '@/lib/ryh';
import type { PlanData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps<{ plan: PlanData }>();

const faces = [
    { initials: 'SP', colour: 'var(--ryh-mint)' },
    { initials: 'JP', colour: 'var(--ryh-lilac)' },
    { initials: 'MP', colour: 'var(--ryh-sun)' },
    { initials: 'NP', colour: 'var(--ryh-sky)' },
];

// The hero's product tour autoplays muted (browsers block autoplay with sound),
// unless the visitor has asked for reduced motion. It plays once and holds on
// its closing frame (the logo and "Start your free month") rather than looping
// back to the intro; a button then offers to watch it again with sound.
const tourVideo = ref<HTMLVideoElement | null>(null);
const tourMuted = ref(true);
const tourEnded = ref(false);

onMounted(() => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        tourVideo.value?.play().catch(() => {});
    }
});

function watchWithSound(): void {
    const video = tourVideo.value;
    if (!video) {
        return;
    }
    video.muted = false;
    video.currentTime = 0;
    video.play().catch(() => {});
    tourMuted.value = false;
    tourEnded.value = false;
}
</script>

<template>
    <section
        class="grid grid-cols-1 items-center gap-10 px-5 pb-16 pt-14 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-14 lg:px-14 lg:pt-[76px]"
    >
        <div class="animate-ryh-rise">
            <div
                class="inline-flex h-[30px] items-center gap-2 rounded-[9px] bg-ryh-soft px-[13px] text-xs font-bold uppercase tracking-[0.05em] text-ryh-ink2"
            >
                <span class="h-[7px] w-[7px] animate-ryh-glow rounded-full bg-ryh-mint"></span>
                First month free
            </div>

            <h1 class="mt-[22px] text-pretty text-[40px] font-black leading-[1.02] tracking-[-0.04em] sm:text-[52px] lg:text-[66px]">
                The three questions every morning starts with.
            </h1>

            <p class="mt-[22px] max-w-[520px] text-pretty text-[17px] leading-relaxed text-ryh-ink2 sm:text-lg">
                What's for dinner? What's on today? What do we need to buy? RYH answers all three the second you open it — and quietly keeps hold of
                everything else your home runs on.
            </p>

            <div class="mt-[30px]">
                <Link
                    :href="route('register')"
                    class="inline-flex h-[54px] items-center rounded-[15px] bg-ryh-coral px-[26px] text-[15.5px] font-bold text-white transition hover:-translate-y-0.5"
                >
                    Create your household
                </Link>
            </div>

            <div class="mt-[26px] flex flex-wrap items-center gap-3">
                <div class="flex">
                    <span
                        v-for="face in faces"
                        :key="face.initials"
                        class="-mr-[9px] grid h-8 w-8 place-items-center rounded-[10px] border-2 border-ryh-shell text-[11px] font-extrabold text-[#0E1A2B]"
                        :style="{ background: face.colour }"
                    >
                        {{ face.initials }}
                    </span>
                </div>
                <span class="ml-2 text-[13.5px] text-ryh-ink3">Up to {{ plan.maxMembers }} people per household</span>
            </div>
        </div>

        <!-- Product tour video -->
        <div class="relative animate-ryh-rise">
            <div class="absolute -top-8 right-10 h-[300px] w-[300px] rounded-full bg-ryh-coral opacity-[0.16] blur-[50px]"></div>

            <div class="relative overflow-hidden rounded-3xl shadow-ryh" :style="{ background: HERO_GRADIENT }">
                <video
                    ref="tourVideo"
                    class="block aspect-video w-full"
                    src="/videos/ryh-promo.mp4"
                    poster="/videos/ryh-promo-poster.jpg"
                    aria-label="RYH product tour"
                    muted
                    playsinline
                    preload="metadata"
                    :controls="!tourMuted && !tourEnded"
                    @ended="tourEnded = true"
                ></video>

                <button
                    v-if="tourMuted || tourEnded"
                    type="button"
                    class="group absolute inset-0 flex items-end p-4"
                    :aria-label="tourEnded ? 'Watch the product tour again with sound' : 'Watch the product tour with sound'"
                    @click="watchWithSound"
                >
                    <span
                        class="flex h-11 items-center gap-2 rounded-[13px] bg-ryh-coral px-4 text-sm font-bold text-white shadow-ryh transition group-hover:-translate-y-0.5"
                    >
                        <span aria-hidden="true">{{ tourEnded ? '↻' : '▶' }}</span> {{ tourEnded ? 'Replay' : 'Watch with sound' }}
                    </span>
                </button>
            </div>
        </div>
    </section>
</template>
