<script setup lang="ts">
import RyhLogo from '@/components/RyhLogo.vue';
import Toast from '@/components/Toast.vue';
import { logout } from '@/lib/logout';
import { Link } from '@inertiajs/vue3';

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
    <div class="flex min-h-svh justify-center bg-ryh-bg p-4 text-ryh-ink transition-colors sm:p-7">
        <Toast />

        <div class="flex w-full max-w-[1240px] animate-ryh-rise overflow-hidden rounded-[24px] bg-ryh-shell shadow-ryh">
            <aside
                class="relative hidden w-[340px] flex-none flex-col overflow-hidden p-8 text-ryh-onhero lg:flex"
                style="background: linear-gradient(150deg, var(--ryh-hero1), var(--ryh-hero2))"
            >
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-ryh-coral opacity-20 blur-3xl"></div>

                <Link :href="route('home')" class="relative flex">
                    <RyhLogo class="h-9" />
                </Link>

                <div class="relative mt-9 flex flex-col gap-0.5">
                    <div v-for="(item, index) in steps" :key="item.title" class="flex gap-3.5">
                        <div class="flex flex-col items-center gap-1.5">
                            <span
                                class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[10px] border text-[12.5px] font-extrabold transition-colors"
                                :class="
                                    props.step > index + 1
                                        ? 'border-transparent bg-ryh-mint text-[#0E1A2B]'
                                        : props.step === index + 1
                                          ? 'border-transparent bg-ryh-onhero text-[#0E1A2B]'
                                          : 'border-ryh-heroline bg-ryh-herofilm text-ryh-onhero2'
                                "
                            >
                                {{ props.step > index + 1 ? '✓' : index + 1 }}
                            </span>
                            <span
                                v-if="index < steps.length - 1"
                                class="w-0.5 flex-1 rounded-full"
                                :class="props.step > index + 1 ? 'bg-ryh-mint' : 'bg-ryh-heroline'"
                            ></span>
                        </div>
                        <div class="pb-5">
                            <div class="text-[14.5px] font-bold" :class="props.step >= index + 1 ? 'text-ryh-onhero' : 'text-ryh-onhero2'">
                                {{ item.title }}
                            </div>
                            <div class="mt-1 max-w-[220px] text-[12.5px] leading-relaxed text-ryh-onhero2">{{ item.body }}</div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-[68px] flex-none items-center gap-3 border-b border-ryh-line px-6 sm:px-8">
                    <span class="font-mono text-xs text-ryh-ink3">Step {{ props.step }} of 3</span>
                    <span class="text-sm font-bold">{{ props.stepTitle }}</span>
                    <div class="flex-1"></div>
                    <template v-if="props.step === 1">
                        <span class="hidden text-[13px] text-ryh-ink3 sm:inline">Already registered?</span>
                        <Link :href="route('login')" class="text-[13.5px] font-semibold text-ryh-coral hover:opacity-75">Log in</Link>
                    </template>
                    <template v-else>
                        <span class="hidden text-[13px] text-ryh-ink3 sm:inline">Not you?</span>
                        <button type="button" class="text-[13.5px] font-semibold text-ryh-coral hover:opacity-75" @click="logout">Log out</button>
                    </template>
                </header>

                <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
