<script setup lang="ts">
import Tbc from '@/components/legal/Tbc.vue';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatPlanPrice } from '@/lib/househub';
import type { Legal, Plan } from '@/types/househub';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ legal: Legal; plan: Plan }>();

const { user, isOwner } = useHouseholdRole();

const mailto = computed(() =>
    props.legal.supportEmail ? `mailto:${props.legal.supportEmail}?subject=${encodeURIComponent(`${props.legal.tradingName} support`)}` : null,
);

// Billing lives on the House page, which only Owners can open.
const shortcuts = computed(() => [
    {
        icon: '🔑',
        title: 'Forgotten your password?',
        body: "Reset it from the sign-in page — we'll email you a link.",
        href: route('password.request'),
    },
    {
        icon: '💳',
        title: 'Billing and cancelling',
        body: "Your household's Owner can change plan, update the card or cancel from Manage billing on the House page.",
        href: isOwner.value ? route('house.index') : route(user.value ? 'dashboard' : 'login'),
    },
    {
        icon: '🗑',
        title: 'Delete your account',
        body: 'Settings → Profile. If you are the only Owner, the household is deleted too.',
        href: route(user.value ? 'profile.edit' : 'login'),
    },
    { icon: '🟢', title: 'Is something down?', body: 'Check the live status of HouseHub before getting in touch.', href: route('status') },
]);

const faqs = computed(() => [
    {
        q: 'How does the free trial work?',
        a: `The first household you create gets 30 days free, with no card needed. When it ends, an Owner can subscribe for ${formatPlanPrice(props.plan.priceMonthly)} a month or ${formatPlanPrice(props.plan.priceAnnual)} a year — nothing is charged automatically. The free month is once per person.`,
    },
    {
        q: 'Does one subscription cover my whole family?',
        a: `Yes — a subscription is per household, not per person, so everyone in the house (up to ${props.plan.maxMembers} people) is covered by one payment. If you run more than one household, such as your own and a parent's, each needs its own subscription.`,
    },
    {
        q: 'How do I cancel?',
        a: "An Owner can cancel any time from Manage billing on the House page. You keep access until the end of the period you've paid for. Cancel within 14 days of your first payment and we'll refund it in full — just email us.",
    },
    {
        q: 'How do I add my family?',
        a: `Owners can invite people by email from the House page, or switch on a join code to share. Up to ${props.plan.maxMembers} members per household, including pending invites.`,
    },
    {
        q: "What's the difference between the roles?",
        a: 'Owners manage the household and billing. Adults run meals, lists, chores and the calendar. Teens can add calendar events and tick things off. Children see and tick off what is assigned to them.',
    },
    {
        q: 'Can I get a copy of my data, or have it deleted?',
        a: 'Yes. Email our privacy contact and we will respond within one month. You can delete your account yourself at any time from Settings → Profile.',
    },
]);
</script>

<template>
    <Head title="Support" />

    <PublicLayout>
        <div class="mx-auto max-w-[860px] px-5 pb-20 pt-12 sm:px-8 lg:pt-16">
            <h1 class="text-[34px] font-black leading-[1.05] tracking-[-0.035em] sm:text-[44px]">How can we help?</h1>
            <p class="mt-3 max-w-[600px] text-[16.5px] leading-relaxed text-hh-ink2">
                {{ legal.tradingName }} is built by a small team, and every message is read by a person who works on it.
            </p>

            <section class="mt-8 rounded-[22px] border border-hh-line bg-hh-panel p-6 sm:p-8">
                <h2 class="text-xl font-extrabold tracking-tight">Email us</h2>
                <p class="mt-2 text-[15px] leading-relaxed text-hh-ink2">
                    We reply {{ legal.supportResponse }}. To help us sort things quickly, tell us your household's name, what you were trying to do,
                    what happened instead, and which device and browser you were using.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <a
                        v-if="mailto"
                        :href="mailto"
                        class="flex h-11 items-center rounded-[13px] bg-hh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5"
                    >
                        {{ legal.supportEmail }}
                    </a>
                    <Tbc v-else :value="null" label="Support email" />
                </div>
                <p class="mt-4 text-[13.5px] text-hh-ink3">
                    Privacy or data requests: <Tbc :value="legal.privacyEmail" label="Privacy email" />. See our
                    <Link :href="route('privacy')" class="font-semibold text-hh-ink underline underline-offset-2">Privacy Policy</Link>.
                </p>
            </section>

            <section class="mt-6 grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <Link
                    v-for="shortcut in shortcuts"
                    :key="shortcut.title"
                    :href="shortcut.href"
                    class="rounded-[18px] border border-hh-line bg-hh-card p-5 transition hover:-translate-y-0.5 hover:shadow-hh"
                >
                    <div class="text-lg" aria-hidden="true">{{ shortcut.icon }}</div>
                    <div class="mt-2 font-extrabold tracking-tight">{{ shortcut.title }}</div>
                    <p class="mt-1 text-sm leading-relaxed text-hh-ink2">{{ shortcut.body }}</p>
                </Link>
            </section>

            <section class="mt-12">
                <h2 class="text-2xl font-black tracking-[-0.02em]">Common questions</h2>
                <div class="mt-4 divide-y divide-hh-line border-y border-hh-line">
                    <details v-for="faq in faqs" :key="faq.q" class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold">
                            {{ faq.q }}
                            <span class="text-hh-ink3 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-2 text-[15px] leading-relaxed text-hh-ink2">{{ faq.a }}</p>
                    </details>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
