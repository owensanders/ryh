<script setup lang="ts">
import { logout } from '@/lib/logout';
import { Link } from '@inertiajs/vue3';
import { House } from 'lucide-vue-next';

const props = defineProps<{
    step: 1 | 2 | 3;
    stepTitle: string;
}>();

const steps = [
    { title: 'Your account', body: 'Name, email and a password. Yours alone.' },
    { title: 'Your household', body: 'Create a new one, or join with an invite code.' },
    { title: 'First morning', body: 'We drop you straight into the dashboard.' },
];
</script>

<template>
    <div class="flex min-h-svh justify-center bg-hh-bg p-4 text-hh-ink transition-colors sm:p-7">
        <div class="flex w-full max-w-[1240px] animate-hh-rise overflow-hidden rounded-[24px] bg-hh-shell shadow-hh">
            <aside
                class="relative hidden w-[340px] flex-none flex-col overflow-hidden p-8 text-hh-onhero lg:flex"
                style="background: linear-gradient(150deg, var(--hh-hero1), var(--hh-hero2))"
            >
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-hh-coral opacity-20 blur-3xl"></div>

                <Link :href="route('home')" class="relative flex items-center gap-2.5">
                    <div class="grid h-9 w-9 flex-none place-items-center rounded-xl bg-hh-coral text-white">
                        <House class="h-[19px] w-[19px]" :stroke-width="2.5" />
                    </div>
                    <span class="text-[17px] font-extrabold tracking-tight">HouseHub</span>
                </Link>

                <div class="relative mt-9 flex flex-col gap-0.5">
                    <div v-for="(item, index) in steps" :key="item.title" class="flex gap-3.5">
                        <div class="flex flex-col items-center gap-1.5">
                            <span
                                class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[10px] border text-[12.5px] font-extrabold transition-colors"
                                :class="
                                    props.step > index + 1
                                        ? 'border-transparent bg-hh-mint text-[#0E1A2B]'
                                        : props.step === index + 1
                                          ? 'border-transparent bg-hh-onhero text-[#0E1A2B]'
                                          : 'border-hh-heroline bg-hh-herofilm text-hh-onhero2'
                                "
                            >
                                {{ props.step > index + 1 ? '✓' : index + 1 }}
                            </span>
                            <span
                                v-if="index < steps.length - 1"
                                class="w-0.5 flex-1 rounded-full"
                                :class="props.step > index + 1 ? 'bg-hh-mint' : 'bg-hh-heroline'"
                            ></span>
                        </div>
                        <div class="pb-5">
                            <div class="text-[14.5px] font-bold" :class="props.step >= index + 1 ? 'text-hh-onhero' : 'text-hh-onhero2'">
                                {{ item.title }}
                            </div>
                            <div class="mt-1 max-w-[220px] text-[12.5px] leading-relaxed text-hh-onhero2">{{ item.body }}</div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-[68px] flex-none items-center gap-3 border-b border-hh-line px-6 sm:px-8">
                    <span class="font-mono text-xs text-hh-ink3">Step {{ props.step }} of 3</span>
                    <span class="text-sm font-bold">{{ props.stepTitle }}</span>
                    <div class="flex-1"></div>
                    <template v-if="props.step === 1">
                        <span class="hidden text-[13px] text-hh-ink3 sm:inline">Already registered?</span>
                        <Link :href="route('login')" class="text-[13.5px] font-semibold text-hh-coral hover:opacity-75">Log in</Link>
                    </template>
                    <template v-else>
                        <span class="hidden text-[13px] text-hh-ink3 sm:inline">Not you?</span>
                        <button type="button" class="text-[13.5px] font-semibold text-hh-coral hover:opacity-75" @click="logout">Log out</button>
                    </template>
                </header>

                <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
