<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import GuardianConfirm from '@/components/househub/GuardianConfirm.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import { formatPlanPrice } from '@/lib/househub';
import type { House } from '@/types/househub';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ house: House }>();

const invite = useForm({ email: '', role: 'adult', guardian_confirmed: false });

// Teen and Child are for under-18s, who may only join with a parent or guardian's agreement.
const forMinors = (role: string): boolean => role === 'teen' || role === 'child';
const inviteNeedsGuardian = computed(() => forMinors(invite.role));
// Pending invites and join requests hold a place too, so memberCount includes them.
const isFull = computed(() => props.house.memberCount >= props.house.maxMembers);
const page = usePage();
const interval = ref<'monthly' | 'annual'>('monthly');

function subscribe(): void {
    router.post(route('house.subscribe'), { interval: interval.value }, { preserveScroll: true });
}

function manageBilling(): void {
    router.post(route('billing.portal'), {}, { preserveScroll: true });
}

function sendInvite(): void {
    if (invite.email.trim() === '' || (inviteNeedsGuardian.value && !invite.guardian_confirmed)) {
        return;
    }

    invite.post(route('house.invite'), {
        preserveScroll: true,
        onSuccess: () => invite.reset(),
    });
}

// Making someone a Teen or Child waits for the Owner to confirm they're a parent or guardian.
const pendingRole = ref<{ memberId: number; name: string; role: string; label: string; select: HTMLSelectElement; previous: string } | null>(null);
const pendingRoleConfirmed = ref(false);

function setRole(memberId: number, role: string, event?: Event): void {
    const member = props.house.members.find((candidate) => candidate.id === memberId);
    const select = event?.target as HTMLSelectElement | undefined;

    if (forMinors(role) && member && select) {
        pendingRoleConfirmed.value = false;
        pendingRole.value = {
            memberId,
            name: member.name,
            role,
            label: props.house.roleOptions.find((option) => option.value === role)?.label ?? role,
            select,
            previous: member.role,
        };
        return;
    }

    router.patch(route('house.members.role', { member: memberId }), { role }, { preserveScroll: true });
}

function confirmPendingRole(): void {
    if (!pendingRole.value || !pendingRoleConfirmed.value) {
        return;
    }

    const { memberId, role } = pendingRole.value;
    router.patch(route('house.members.role', { member: memberId }), { role, guardian_confirmed: true }, { preserveScroll: true });
    pendingRole.value = null;
}

function cancelPendingRole(open: boolean): void {
    if (open || !pendingRole.value) {
        return;
    }

    // Put the select back to the role they still have.
    pendingRole.value.select.value = pendingRole.value.previous;
    pendingRole.value = null;
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
                class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-5 text-hh-onhero sm:p-6 md:flex-row md:items-center"
                style="background: linear-gradient(118deg, var(--hh-hero1) 0%, var(--hh-hero2) 100%)"
            >
                <div class="absolute -top-24 right-52 h-72 w-72 rounded-full bg-hh-coral opacity-20 blur-3xl"></div>

                <div class="relative min-w-0 flex-1">
                    <div class="text-[11.5px] font-bold uppercase tracking-[0.09em] text-hh-onhero2">Household</div>
                    <div class="mt-2 break-words text-[32px] font-black leading-[1.05] tracking-[-0.03em] sm:text-4xl">
                        {{ props.house.houseName }}
                    </div>
                    <div class="mt-2 text-sm text-hh-onhero2">created {{ props.house.houseCreated }}</div>
                </div>

                <div class="relative grid grid-cols-3 gap-2 sm:gap-2.5 md:flex">
                    <div
                        v-for="stat in props.house.houseStats"
                        :key="stat.label"
                        class="min-w-0 rounded-2xl border border-hh-heroline bg-hh-herofilm p-3 sm:p-4 md:w-[132px]"
                    >
                        <div class="text-[10px] font-bold uppercase leading-tight tracking-[0.08em] text-hh-onhero2 sm:text-[11px]">
                            {{ stat.label }}
                        </div>
                        <div class="mt-1 text-2xl font-extrabold tracking-[-0.03em]">{{ stat.value }}</div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.5fr_1fr]">
                <!-- People in this home -->
                <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                    <div class="mb-3.5 flex items-center gap-2.5">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">People in this home</h3>
                        <span class="text-xs text-hh-ink3" data-testid="member-count"
                            >{{ props.house.memberCount }} of {{ props.house.maxMembers }} members</span
                        >
                    </div>

                    <div class="flex flex-col gap-2">
                        <div
                            v-for="member in props.house.members"
                            :key="member.id"
                            class="flex flex-wrap items-center gap-x-3.5 gap-y-2.5 rounded-2xl border border-hh-line p-3.5 sm:flex-nowrap"
                            :class="member.pending ? 'bg-hh-sunk' : 'bg-hh-card'"
                        >
                            <span
                                class="grid h-11 w-11 flex-none place-items-center rounded-2xl text-sm font-extrabold text-[#0E1A2B]"
                                :style="{ background: member.colour }"
                            >
                                {{ member.initials }}
                            </span>
                            <div class="min-w-0 flex-1 basis-[calc(100%-120px)] sm:basis-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="min-w-0 truncate text-[15px] font-bold">{{ member.name }}</span>
                                    <span v-if="member.you" class="rounded-lg bg-hh-soft px-2 py-[3px] text-[11px] font-bold text-hh-ink2">You</span>
                                    <span v-if="member.pending" class="rounded-lg bg-hh-sun px-2 py-[3px] text-[11px] font-bold text-[#0E1A2B]">
                                        {{ member.pendingReason === 'requested' ? 'Wants to join' : 'Invite pending' }}
                                    </span>
                                </div>
                                <div class="mt-1 truncate text-[12.5px] text-hh-ink3">{{ member.email }}</div>
                            </div>
                            <select
                                :value="member.role"
                                :aria-label="`Role for ${member.name}`"
                                class="order-last ml-[58px] h-[38px] min-w-0 flex-1 rounded-[11px] border border-hh-line bg-hh-sunk px-2.5 text-[13px] font-semibold sm:order-none sm:ml-0 sm:w-[132px] sm:flex-none"
                                @change="setRole(member.id, ($event.target as HTMLSelectElement).value, $event)"
                            >
                                <option v-for="option in props.house.roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                v-if="member.pendingReason === 'requested'"
                                type="button"
                                title="Approve"
                                :aria-label="`Approve ${member.name}`"
                                class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-mint"
                                @click="approveMember(member.id)"
                            >
                                ✓
                            </button>
                            <button
                                type="button"
                                title="Remove from household"
                                :aria-label="`Remove ${member.name} from household`"
                                class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-coral"
                                @click="removeMember(member.id)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-hh-line pt-4">
                        <div class="mb-2.5 text-[13px] font-bold">Invite someone</div>
                        <div v-if="isFull" class="rounded-2xl bg-hh-sunk px-3.5 py-3 text-[13px] leading-relaxed text-hh-ink2">
                            Your household is full — it holds up to {{ props.house.maxMembers }} people, including pending invites. Remove someone or
                            cancel an invite to make room.
                        </div>
                        <form v-else class="flex flex-col gap-2 sm:flex-row" @submit.prevent="sendInvite">
                            <input
                                v-model="invite.email"
                                type="email"
                                placeholder="their@email.co.uk"
                                aria-label="Email"
                                class="h-[46px] w-full min-w-0 rounded-[13px] border border-hh-line bg-hh-sunk px-3.5 text-sm placeholder:text-hh-ink3 sm:flex-1"
                            />
                            <select
                                v-model="invite.role"
                                aria-label="Role"
                                class="h-[46px] w-full rounded-[13px] border border-hh-line bg-hh-sunk px-2.5 text-[13px] font-semibold sm:w-[132px]"
                            >
                                <option v-for="option in props.house.roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                type="submit"
                                :disabled="invite.processing || (inviteNeedsGuardian && !invite.guardian_confirmed)"
                                class="h-[46px] rounded-[13px] bg-hh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:opacity-50"
                            >
                                Send invite
                            </button>
                        </form>
                        <InputError :message="invite.errors.email" />
                        <div v-if="inviteNeedsGuardian" class="mt-2.5">
                            <GuardianConfirm id="invite-guardian" v-model="invite.guardian_confirmed" who="the person I'm inviting" />
                            <InputError :message="invite.errors.guardian_confirmed" />
                        </div>
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
                                aria-label="Invite code"
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

                        <div v-if="props.house.joinCodeEnabled" class="mt-3 flex flex-wrap items-center gap-2.5">
                            <span
                                data-testid="join-code"
                                class="min-w-0 flex-1 break-all rounded-lg bg-hh-sunk px-3 py-1.5 font-mono text-[13px] font-bold tracking-wider text-hh-ink"
                            >
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
                    <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Plan</h3>

                        <div class="mt-3 rounded-2xl border border-hh-line bg-hh-sunk p-3.5">
                            <template v-if="props.house.planStatus.subscribed">
                                <div class="text-[13.5px] font-bold">
                                    <template v-if="props.house.planStatus.interval === 'annual'">
                                        Home · {{ formatPlanPrice(props.house.planStatus.priceAnnual) }}/year
                                    </template>
                                    <template v-else>Home · {{ formatPlanPrice(props.house.planStatus.priceMonthly) }}/month</template>
                                </div>
                                <div class="mt-0.5 text-[12px] text-hh-ink3">
                                    <template v-if="props.house.planStatus.endsLabel"
                                        >Cancelled · access until {{ props.house.planStatus.endsLabel }}</template
                                    >
                                    <template v-else>Change plan, update your card or cancel anytime.</template>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 flex h-[38px] w-full items-center justify-center rounded-[11px] border-[1.5px] border-hh-line px-4 text-[13px] font-bold text-hh-ink2 transition hover:-translate-y-0.5"
                                    @click="manageBilling"
                                >
                                    {{ props.house.planStatus.endsLabel ? 'Resume or manage billing' : 'Manage billing' }}
                                </button>
                            </template>
                            <template v-else>
                                <template v-if="props.house.planStatus.onTrial">
                                    <div class="text-[13.5px] font-bold">Free trial · ends {{ props.house.planStatus.trialEndsLabel }}</div>
                                </template>
                                <template v-else-if="props.house.planStatus.paymentIssue">
                                    <div class="text-[13.5px] font-bold">Payment failed</div>
                                    <div class="mt-0.5 text-[12px] text-hh-ink3">Update your card and we’ll retry the payment.</div>
                                </template>
                                <template v-else>
                                    <div class="text-[13.5px] font-bold">Trial ended</div>
                                    <div class="mt-0.5 text-[12px] text-hh-ink3">Subscribe to keep using HouseHub.</div>
                                </template>

                                <div v-if="!props.house.planStatus.paymentIssue" class="mt-3 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                                        :class="
                                            interval === 'monthly' ? 'bg-hh-coral/10 border-hh-coral text-hh-coral' : 'border-hh-line text-hh-ink2'
                                        "
                                        @click="interval = 'monthly'"
                                    >
                                        {{ formatPlanPrice(props.house.planStatus.priceMonthly) }}/month
                                    </button>
                                    <button
                                        type="button"
                                        class="flex h-[36px] items-center justify-center rounded-[10px] border-[1.5px] text-[12.5px] font-bold transition"
                                        :class="
                                            interval === 'annual' ? 'bg-hh-coral/10 border-hh-coral text-hh-coral' : 'border-hh-line text-hh-ink2'
                                        "
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
                                    <template v-if="props.house.planStatus.paymentIssue">Update your card</template>
                                    <template v-else>{{ props.house.planStatus.onTrial ? 'Start subscription now' : 'Subscribe now' }}</template>
                                </button>
                            </template>
                        </div>
                        <p v-if="page.props.errors.plan" class="mt-2 text-[12.5px] text-hh-coral">{{ page.props.errors.plan }}</p>
                    </section>

                    <!-- What each role can do -->
                    <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">What each role can do</h3>
                        <div class="mt-3 flex flex-col gap-2.5">
                            <div v-for="role in props.house.roles" :key="role.name" class="rounded-2xl bg-hh-sunk p-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-sm" :style="{ background: role.colour }"></span>
                                    <span class="text-[13.5px] font-bold">{{ role.name }}</span>
                                    <span class="ml-auto text-[11.5px] text-hh-ink3"
                                        >{{ role.count }} {{ role.count === 1 ? 'person' : 'people' }}</span
                                    >
                                </div>
                                <div class="mt-1.5 text-[12.5px] leading-relaxed text-hh-ink2">{{ role.body }}</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <Dialog :open="pendingRole !== null" @update:open="cancelPendingRole">
            <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight"
                        >Make {{ pendingRole?.name }} a {{ pendingRole?.label }}?</DialogTitle
                    >
                    <DialogDescription class="text-[13.5px] text-hh-ink2">
                        Teen and Child are for under-18s, who can only be part of a household with a parent or guardian's agreement.
                    </DialogDescription>
                </DialogHeader>
                <GuardianConfirm id="role-guardian" v-model="pendingRoleConfirmed" :who="pendingRole?.name" />
                <div class="flex items-center gap-2">
                    <button type="button" class="hh-btn w-auto bg-hh-coral text-white" :disabled="!pendingRoleConfirmed" @click="confirmPendingRole">
                        Change role
                    </button>
                    <button type="button" class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="cancelPendingRole(false)">Cancel</button>
                </div>
            </DialogContent>
        </Dialog>
    </HouseHubLayout>
</template>
