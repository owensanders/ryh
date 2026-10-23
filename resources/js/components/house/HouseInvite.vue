<script setup lang="ts">
import GuardianConfirm from '@/components/GuardianConfirm.vue';
import InputError from '@/components/InputError.vue';
import IconButton from '@/components/ui/IconButton.vue';
import { forMinors } from '@/lib/house';
import type { HouseData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Inviting someone by email, or turning on a code they can join with. */
const props = defineProps<{ house: HouseData }>();

const invite = useForm({ email: '', role: 'adult', guardian_confirmed: false });
const inviteNeedsGuardian = computed(() => forMinors(invite.role));
// Pending invites and join requests hold a place too, so memberCount includes them.
const isFull = computed(() => props.house.memberCount >= props.house.maxMembers);

function sendInvite(): void {
    if (invite.email.trim() === '' || (inviteNeedsGuardian.value && !invite.guardian_confirmed)) {
        return;
    }

    invite.post(route('house.invite'), {
        preserveScroll: true,
        onSuccess: () => invite.reset(),
    });
}

function toggleJoinCode(): void {
    router.patch(route('house.joinCode.toggle'), { enabled: !props.house.joinCodeEnabled }, { preserveScroll: true });
}

function regenerateJoinCode(): void {
    router.post(route('house.joinCode.regenerate'), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="mt-4 border-t border-ryh-line pt-4">
        <div class="mb-2.5 text-[13px] font-bold">Invite someone</div>
        <div v-if="isFull" class="rounded-2xl bg-ryh-sunk px-3.5 py-3 text-[13px] leading-relaxed text-ryh-ink2">
            Your household is full — it holds up to {{ house.maxMembers }} people, including pending invites. Remove someone or cancel an invite to
            make room.
        </div>
        <form v-else class="flex flex-col gap-2 sm:flex-row" @submit.prevent="sendInvite">
            <input
                v-model="invite.email"
                type="email"
                placeholder="their@email.co.uk"
                aria-label="Email"
                class="h-[46px] w-full min-w-0 rounded-[13px] border border-ryh-line bg-ryh-sunk px-3.5 text-sm placeholder:text-ryh-ink3 sm:flex-1"
            />
            <select
                v-model="invite.role"
                aria-label="Role"
                class="h-[46px] w-full rounded-[13px] border border-ryh-line bg-ryh-sunk px-2.5 text-[13px] font-semibold sm:w-[132px]"
            >
                <option v-for="option in house.roleOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <button
                type="submit"
                :disabled="invite.processing || (inviteNeedsGuardian && !invite.guardian_confirmed)"
                class="h-[46px] rounded-[13px] bg-ryh-coral px-5 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:opacity-50"
            >
                Send invite
            </button>
        </form>
        <InputError :message="invite.errors.email" />
        <div v-if="inviteNeedsGuardian" class="mt-2.5">
            <GuardianConfirm id="invite-guardian" v-model="invite.guardian_confirmed" who="the person I'm inviting" />
            <InputError :message="invite.errors.guardian_confirmed" />
        </div>
        <div class="mt-2.5 text-[12.5px] text-ryh-ink3">Invites expire after 7 days.</div>

        <div class="my-4 flex items-center gap-3 text-[11.5px] font-semibold text-ryh-ink3">
            <span class="h-px flex-1 bg-ryh-line"></span>
            Or
            <span class="h-px flex-1 bg-ryh-line"></span>
        </div>

        <div class="flex items-center gap-3 rounded-2xl bg-ryh-sunk p-3.5">
            <div class="min-w-0 flex-1 pr-3">
                <div class="text-[13px] font-semibold">Invite code</div>
                <div class="mt-0.5 text-[11.5px] text-ryh-ink3">Share this so someone can join without an email invite.</div>
            </div>
            <IconButton
                label="Invite code"
                role="switch"
                :aria-checked="house.joinCodeEnabled"
                class="h-[26px] w-11 flex-none rounded-full p-[3px] transition-colors"
                :style="{ background: house.joinCodeEnabled ? 'var(--ryh-mint)' : 'var(--ryh-line)' }"
                @click="toggleJoinCode"
            >
                <span
                    class="block h-5 w-5 rounded-full bg-white transition-transform"
                    :style="{ transform: house.joinCodeEnabled ? 'translateX(18px)' : 'translateX(0)' }"
                ></span>
            </IconButton>
        </div>

        <div v-if="house.joinCodeEnabled" class="mt-3 flex flex-wrap items-center gap-2.5">
            <span
                data-testid="join-code"
                class="min-w-0 flex-1 break-all rounded-lg bg-ryh-sunk px-3 py-1.5 font-mono text-[13px] font-bold tracking-wider text-ryh-ink"
            >
                {{ house.joinCode }}
            </span>
            <button
                type="button"
                class="flex-none rounded-lg border border-ryh-line px-3 py-1.5 text-[12.5px] font-semibold text-ryh-ink2 transition-colors hover:bg-ryh-soft"
                @click="regenerateJoinCode"
            >
                Generate new code
            </button>
        </div>
        <div v-else class="mt-3 text-[12.5px] text-ryh-ink3">Off — turn this on to get a shareable code.</div>
    </div>
</template>
