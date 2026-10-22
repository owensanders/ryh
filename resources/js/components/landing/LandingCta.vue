<script setup lang="ts">
import type { PlanData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ plan: PlanData }>();

const email = ref('');

function startRegistration(): void {
    router.get(route('register'), email.value ? { email: email.value } : {});
}
</script>

<template>
    <section
        class="mx-5 mb-[72px] flex flex-col items-start gap-8 rounded-[26px] border border-ryh-line bg-ryh-panel p-8 sm:mx-8 sm:p-14 lg:mx-14 lg:flex-row lg:items-center lg:gap-10"
    >
        <div class="flex-1">
            <h2 class="text-[32px] font-black leading-[1.06] tracking-[-0.035em] sm:text-[42px]">Set it up before the kettle boils.</h2>
            <p class="mt-3.5 max-w-[520px] text-[16.5px] leading-relaxed text-ryh-ink2">
                Create a household, invite the family, add tonight's dinner. Everything else can wait until you need it.
            </p>
        </div>

        <form class="flex w-full flex-none flex-col gap-2.5 lg:w-[360px]" @submit.prevent="startRegistration">
            <label for="cta-email" class="sr-only">Email address</label>
            <input
                id="cta-email"
                v-model="email"
                type="email"
                autocomplete="email"
                placeholder="you@household.co.uk"
                class="h-[54px] rounded-[15px] border border-ryh-line bg-ryh-card px-[18px] text-[15px] text-ryh-ink placeholder:text-ryh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral"
            />
            <button type="submit" class="h-[54px] rounded-[15px] bg-ryh-coral text-[15.5px] font-bold text-white transition hover:-translate-y-0.5">
                Create your household
            </button>
            <div class="text-center text-[12.5px] text-ryh-ink3">First month free · no card · up to {{ plan.maxMembers }} members</div>
        </form>
    </section>
</template>
