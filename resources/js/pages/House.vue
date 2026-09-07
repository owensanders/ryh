<script setup lang="ts">
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import { formatPlanPrice } from '@/lib/househub';
import type { House } from '@/types/househub';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ house: House }>();

const invite = useForm({ email: '', role: 'adult' });
const interval = ref<'monthly' | 'annual'>('monthly');

function subscribe(): void {
    router.post(route('house.subscribe'), { interval: interval.value }, { preserveScroll: true });
}

function sendInvite(): void {
    if (invite.email.trim() === '') {
        return;
    }

    invite.post(route('house.invite'), {
        preserveScroll: true,
        onSuccess: () => invite.reset(),
    });
}

function setRole(memberId: number, role: string): void {
    router.patch(route('house.members.role', { member: memberId }), { role }, { preserveScroll: true });
}

function removeMember(memberId: number): void {
    router.delete(route('house.members.destroy', { member: memberId }), { preserveScroll: true });
}

function approveMember(memberId: number): void {
    router.patch(route('house.members.approve', { member: memberId }), {}, { preserveScroll: true });
}

function toggleJoinCode(): void {
    router.patch(route('house.joinCode.toggle'), { enabled: !props.house.joinCodeEnabled }, { preserveScroll: true });
}

function regenerateJoinCode(): void {
    router.post(route('house.joinCode.regenerate'), {}, { preserveScroll: true });
}
</script>

<template>
    <HouseHubLayout title="House" subtitle="House information">
        <div class="flex animate-hh-rise flex-col gap-4">
            <!-- Hero -->
            <section
                class="relative flex flex-col items-start gap-5 overflow-hidden rounded-[22px] p-6 text-hh-onhero sm:flex-row sm:items-center"
                style="background: linear-gradient(118deg, var(--hh-hero1) 0%, var(--hh-hero2) 100%)"
            >
                <div class="absolute -top-24 right-52 h-72 w-72 rounded-full bg-hh-coral opacity-20 blur-3xl"></div>

                <div class="relative min-w-0 flex-1">
                    <div class="text-[11.5px] font-bold uppercase tracking-[0.09em] text-hh-onhero2">Household</div>
                    <div class="mt-2 text-4xl font-black leading-[1.05] tracking-[-0.03em]">{{ props.house.houseName }}</div>
                    <div class="mt-2 text-sm text-hh-onhero2">created {{ props.house.houseCreated }}</div>
                </div>

                <div class="relative flex gap-2.5">
                    <div
                        v-for="stat in props.house.houseStats"
                        :key="stat.label"
                        class="w-[132px] rounded-2xl border border-hh-heroline bg-hh-herofilm p-4"
                    >
                        <div class="text-[11px] font-bold uppercase tracking-[0.09em] text-hh-onhero2">{{ stat.label }}</div>
                        <div class="mt-1 text-2xl font-extrabold tracking-[-0.03em]">{{ stat.value }}</div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.5fr_1fr]">
                <!-- People in this home -->
                <section class="rounded-[22px] border border-hh-line bg-hh-card p-[22px]">
                    <div class="mb-3.5 flex items-center gap-2.5">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">People in this home</h3>
                        <span class="text-xs text-hh-ink3">{{ props.house.memberCount }} members</span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div
                            v-for="member in props.house.members"
                            :key="member.id"
                            class="flex items-center gap-3.5 rounded-2xl border border-hh-line p-3.5"
                            :class="member.pending ? 'bg-hh-sunk' : 'bg-hh-card'"
                        >
                            <span
                                class="grid h-11 w-11 flex-none place-items-center rounded-2xl text-sm font-extrabold text-[#0E1A2B]"
                                :style="{ background: member.colour }"
                            >
                                {{ member.initials }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[15px] font-bold">{{ member.name }}</span>
                                    <span v-if="member.you" class="rounded-lg bg-hh-soft px-2 py-[3px] text-[11px] font-bold text-hh-ink2">You</span>
                                    <span v-if="member.pending" class="rounded-lg bg-hh-sun px-2 py-[3px] text-[11px] font-bold text-[#0E1A2B]">
                                        {{ member.pendingReason === 'requested' ? 'Wants to join' : 'Invite pending' }}
                                    </span>
                                </div>
                                <div class="mt-1 text-[12.5px] text-hh-ink3">{{ member.email }}</div>
                            </div>
                            <select
                                :value="member.role"
                                class="h-[38px] w-[132px] rounded-[11px] border border-hh-line bg-hh-sunk px-2.5 text-[13px] font-semibold"
                                @change="setRole(member.id, ($event.target as HTMLSelectElement).value)"
                            >
                                <option v-for="option in props.house.roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                v-if="member.pendingReason === 'requested'"
                                type="button"
                                title="Approve"
                                class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-mint"
                                @click="approveMember(member.id)"
                            >
                                ✓
                            </button>
                            <button
                                type="button"
                                title="Remove from household"
                                class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-coral"
                                @click="removeMember(member.id)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-hh-line pt-4">
                        <div class="mb-2.5 text-[13px] font-bold">Invite someone</div>
                        <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="sendInvite">
                            <input
                                v-model="invite.email"
                                type="email"
                                placeholder="their@email.co.uk"
                                aria-label="Email"
                                class="h-[46px] min-w-0 flex-1 rounded-[13px] border border-hh-line bg-hh-sunk px-3.5 text-sm placeholder:text-hh-ink3"
                            />
                            <select
                                v-model="invite.role"
                                class="h-[46px] w-[132px] rounded-[13px] border border-hh-line bg-hh-sunk px-2.5 text-[13px] font-semibold"
                            >
                                <option v-for="option in props.house.roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                type="submit"
                                :disabled="invite.processing"
                                class="h-[46px] rounded-[13px] bg-hh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:opacity-50"
                            >
                                Send invite
                            </button>
                        </form>
                        <div class="mt-2.5 text-[12.5px] text-hh-ink3">Invites expire after 7 days.</div>

                        <div class="my-4 flex items-center gap-3 text-[11.5px] font-semibold text-hh-ink3">
                            <span class="h-px flex-1 bg-hh-line"></span>
                            Or
                            <span class="h-px flex-1 bg-hh-line"></span>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl bg-hh-sunk p-3.5">
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="text-[13px] font-semibold">Invite code</div>
                                <div class="mt-0.5 text-[11.5px] text-hh-ink3">Share this so someone can join without an email invite.</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="props.house.joinCodeEnabled"
                                class="h-[26px] w-11 flex-none rounded-full p-[3px] transition-colors"
                                :style="{ background: props.house.joinCodeEnabled ? 'var(--hh-mint)' : 'var(--hh-line)' }"
                                @click="toggleJoinCode"
                            >
                                <span
                                    class="block h-5 w-5 rounded-full bg-white transition-transform"
                                    :style="{ transform: props.house.joinCodeEnabled ? 'translateX(18px)' : 'translateX(0)' }"
                                ></span>
                            </button>
                        </div>

                        <div v-if="props.house.joinCodeEnabled" class="mt-3 flex items-center gap-2.5">
                            <span class="flex-1 rounded-lg bg-hh-sunk px-3 py-1.5 font-mono text-[13px] font-bold tracking-wider text-hh-ink">
                                {{ props.house.joinCode }}
                            </span>
                            <button
                                type="button"
                                class="flex-none rounded-lg border border-hh-line px-3 py-1.5 text-[12.5px] font-semibold text-hh-ink2 transition-colors hover:bg-hh-soft"
                                @click="regenerateJoinCode"
                            >
                                Generate new code
                            </button>
                        </div>
                        <div v-else class="mt-3 text-[12.5px] text-hh-ink3">Off — turn this on to get a shareable code.</div>
                    </div>
                </section>

                <div class="flex flex-col gap-4">
                    <!-- Plan -->
                    <section class="rounded-[22px] border border-hh-line bg-hh-card p-[22px]">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Plan</h3>

                        <div class="mt-3 rounded-2xl border border-hh-line bg-hh-sunk p-3.5">
                            <template v-if="props.house.planStatus.subscribed">
                                <div class="text-[13.5px] font-bold">Home · {{ formatPlanPrice(props.house.planStatus.priceMonthly) }}/month</div>
                                <div class="mt-0.5 text-[12px] text-hh-ink3">Cancel or update your billing details with support.</div>
                            </template>
                            <template v-else>
                                <template v-if="props.house.planStatus.onTrial">
                                    <div class="text-[13.5px] font-bold">Free trial · ends {{ props.house.planStatus.trialEndsLabel }}</div>
                                </template>
                                <template v-else>
                                    <div class="text-[13.5px] font-bold">Trial ended</div>
                                    <div class="mt-0.5 text-[12px] text-hh-ink3">Subscribe to keep using HouseHub.</div>
                                </template>

                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                                        :class="interval === 'monthly' ? 'border-hh-coral bg-hh-coral/10 text-hh-coral' : 'border-hh-line text-hh-ink2'"
                                        @click="interval = 'monthly'"
                                    >
                                        {{ formatPlanPrice(props.house.planStatus.priceMonthly) }}/month
                                    </button>
                                    <button
                                        type="button"
                                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                                        :class="interval === 'annual' ? 'border-hh-coral bg-hh-coral/10 text-hh-coral' : 'border-hh-line text-hh-ink2'"
                                        @click="interval = 'annual'"
                                    >
                                        {{ formatPlanPrice(props.house.planStatus.priceAnnual) }}/year
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="mt-2.5 flex h-[38px] w-full items-center justify-center rounded-[11px] bg-hh-coral px-4 text-[13px] font-bold text-white transition hover:-translate-y-0.5"
                                    @click="subscribe"
                                >
                                    {{ props.house.planStatus.onTrial ? 'Start subscription now' : 'Subscribe now' }}
                                </button>
                            </template>
                        </div>
                    </section>

                    <!-- What each role can do -->
                    <section class="rounded-[22px] border border-hh-line bg-hh-card p-[22px]">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">What each role can do</h3>
                        <div class="mt-3 flex flex-col gap-2.5">
                            <div v-for="role in props.house.roles" :key="role.name" class="rounded-2xl bg-hh-sunk p-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-sm" :style="{ background: role.colour }"></span>
                                    <span class="text-[13.5px] font-bold">{{ role.name }}</span>
                                    <span class="ml-auto text-[11.5px] text-hh-ink3">{{ role.count }} {{ role.count === 1 ? 'person' : 'people' }}</span>
                                </div>
                                <div class="mt-1.5 text-[12.5px] leading-relaxed text-hh-ink2">{{ role.body }}</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </HouseHubLayout>
</template>
