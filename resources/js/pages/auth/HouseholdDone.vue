<script setup lang="ts">
import AuthWizardLayout from '@/layouts/auth/AuthWizardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'create' | 'join';
    householdName: string;
    inviteCount: number;
    pending: boolean;
}>();

const doneTitle = computed(() => (props.mode === 'create' ? 'Your household is live' : 'Request sent'));

const doneBody = computed(() => {
    if (props.mode === 'join') {
        return 'The owner of that household has been asked to approve you. You will get an email the moment they do — usually within a few minutes.';
    }

    const name = props.householdName || 'Your household';
    const invites = props.inviteCount > 0 ? ` and ${props.inviteCount} invite${props.inviteCount > 1 ? 's are' : ' is'} on the way` : '';

    return `${name} is set up${invites}. Add tonight's dinner and the rest can wait.`;
});

const nextSteps = [
    { icon: '🍽', tint: 'var(--ryh-t1)', title: "Plan tonight's dinner", body: 'Pick one meal. The shopping list builds itself.', time: '2 min' },
    { icon: '📅', tint: 'var(--ryh-t3)', title: "Add this week's events", body: 'Clubs, appointments, direct debits.', time: '5 min' },
    { icon: '✅', tint: 'var(--ryh-t2)', title: 'Set up the chore rota', body: 'Assign the recurring ones once, then forget it.', time: '3 min' },
];
</script>

<template>
    <AuthWizardLayout :step="3" step-title="First morning">
        <Head title="You're all set" />

        <div class="max-w-[640px]">
            <div class="grid h-14 w-14 animate-ryh-pop place-items-center rounded-[18px] bg-ryh-mint text-2xl text-[#0E1A2B]">✓</div>
            <h1 class="mt-5 text-[32px] font-black tracking-[-0.035em] sm:text-[38px]">{{ doneTitle }}</h1>
            <p class="mt-3 text-[15.5px] leading-relaxed text-ryh-ink2">{{ doneBody }}</p>

            <div class="mt-6 flex flex-col gap-2">
                <div
                    v-for="step in nextSteps"
                    :key="step.title"
                    class="flex items-center gap-3.5 rounded-[18px] border border-ryh-line bg-ryh-card px-4 py-4"
                >
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-[13px] text-[17px]" :style="{ background: step.tint }">
                        {{ step.icon }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-[14.5px] font-bold">{{ step.title }}</div>
                        <div class="mt-0.5 text-[12.5px] text-ryh-ink3">{{ step.body }}</div>
                    </div>
                    <span class="font-mono text-[11.5px] text-ryh-ink3">{{ step.time }}</span>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <Link v-if="!pending" :href="route('dashboard')" class="ryh-btn bg-ryh-coral px-6 text-white">Open my dashboard</Link>
                <button
                    v-else
                    type="button"
                    disabled
                    title="Waiting for the household owner to approve you"
                    class="ryh-btn bg-ryh-coral px-6 text-white"
                >
                    Open my dashboard
                </button>
            </div>
        </div>
    </AuthWizardLayout>
</template>
