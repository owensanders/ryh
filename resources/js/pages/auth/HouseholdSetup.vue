<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import GuardianConfirm from '@/components/ryh/GuardianConfirm.vue';
import AuthWizardLayout from '@/layouts/auth/AuthWizardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Mode = 'create' | 'join';
type InviteRole = 'adult' | 'teen' | 'child';

const props = defineProps<{
    trialAvailable: boolean;
    /** Name of the household an invite link couldn't add them to because it had filled up. */
    fullHousehold: string | null;
    /** Name of the household whose Teen/Child invite link needs re-sending with a guardian's confirmation. */
    reinviteHousehold: string | null;
    maxMembers: number;
}>();

const mode = ref<Mode>('create');

const sizes = ['1 person', '2 people', '3 people', '4 people', '5 people', '6 or more'];
const roles: InviteRole[] = ['adult', 'teen', 'child'];

const createForm = useForm<{
    name: string;
    size: string;
    invites: { email: string; role: InviteRole }[];
    guardian_confirmed: boolean;
}>({
    name: '',
    size: sizes[3],
    invites: [],
    guardian_confirmed: false,
});

const invitesMinors = computed(() => createForm.invites.some((invite) => invite.role !== 'adult'));

// Laravel reports invite errors per row (`invites.1.email`), which Inertia's
// form types don't know about — show whichever comes first.
const inviteError = computed(
    () => Object.entries(createForm.errors as Record<string, string | undefined>).find(([key]) => key.startsWith('invites'))?.[1],
);

const inviteEmail = ref('');
const inviteRole = ref<InviteRole>('adult');

// The owner takes one of the household's places.
const canAddInvite = computed(() => createForm.invites.length < props.maxMembers - 1);

function addInvite(): void {
    const email = inviteEmail.value.trim();
    if (!email.includes('@') || !canAddInvite.value) return;
    createForm.invites.push({ email, role: inviteRole.value });
    inviteEmail.value = '';
}

function removeInvite(index: number): void {
    createForm.invites.splice(index, 1);
}

function submitCreate(): void {
    if (createForm.name.trim() === '') return;
    createForm.post(route('household.setup.store'));
}

const joinForm = useForm({ code: '' });
const codeClean = computed(() => joinForm.code.replace(/[^A-Za-z0-9]/g, ''));
const codeLooksValid = computed(() => codeClean.value.length >= 8);

function submitJoin(): void {
    if (!codeLooksValid.value) return;
    joinForm.post(route('household.join'));
}
</script>

<template>
    <AuthWizardLayout :step="2" step-title="Your household">
        <Head title="Household" />

        <div class="max-w-[760px]">
            <h1 class="text-[32px] font-black tracking-[-0.035em] sm:text-[38px]">Create or join a household</h1>
            <p class="mt-3 text-[15.5px] leading-relaxed text-ryh-ink2">
                A household is the shared space your meals, lists, chores and documents live in. Most people only ever need one.
            </p>

            <div
                v-if="props.fullHousehold"
                role="alert"
                class="mt-5 rounded-2xl border border-ryh-coral bg-ryh-card px-4 py-3.5 text-[13.5px] leading-relaxed"
            >
                <span class="font-bold">{{ props.fullHousehold }} is full.</span>
                A household holds up to {{ props.maxMembers }} people, so you couldn't be added. Ask the owner to make room and send a new invite, or
                set up your own household below.
            </div>

            <div
                v-if="props.reinviteHousehold"
                role="alert"
                class="mt-5 rounded-2xl border border-ryh-coral bg-ryh-card px-4 py-3.5 text-[13.5px] leading-relaxed"
            >
                <span class="font-bold">Your invite to {{ props.reinviteHousehold }} needs re-sending.</span>
                Your parent or guardian now confirms they're happy for you to join when they invite you. Ask them to send you a new invite from their
                House page.
            </div>

            <div class="mt-6 grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <button
                    type="button"
                    class="flex flex-col items-start gap-2.5 rounded-[20px] border-[1.5px] p-5 text-left transition-transform hover:-translate-y-0.5"
                    :class="mode === 'create' ? 'border-ryh-coral bg-ryh-card' : 'border-ryh-line bg-transparent'"
                    @click="mode = 'create'"
                >
                    <span class="grid h-[42px] w-[42px] place-items-center rounded-[14px] text-lg" style="background: var(--ryh-t1)">🏡</span>
                    <span class="text-[17px] font-extrabold tracking-[-0.02em]">Create a household</span>
                    <span class="text-[13.5px] leading-relaxed text-ryh-ink2">
                        You are the first one in. You become the owner and invite everyone else.
                    </span>
                </button>
                <button
                    type="button"
                    class="flex flex-col items-start gap-2.5 rounded-[20px] border-[1.5px] p-5 text-left transition-transform hover:-translate-y-0.5"
                    :class="mode === 'join' ? 'border-ryh-coral bg-ryh-card' : 'border-ryh-line bg-transparent'"
                    @click="mode = 'join'"
                >
                    <span class="grid h-[42px] w-[42px] place-items-center rounded-[14px] text-lg" style="background: var(--ryh-t3)">🔑</span>
                    <span class="text-[17px] font-extrabold tracking-[-0.02em]">Join with a code</span>
                    <span class="text-[13.5px] leading-relaxed text-ryh-ink2"> Someone already set your home up. Paste the code they sent you. </span>
                </button>
            </div>

            <form
                v-if="mode === 'create'"
                class="mt-5 flex flex-col gap-3.5 rounded-[20px] border border-ryh-line bg-ryh-card p-6"
                @submit.prevent="submitCreate"
            >
                <div class="flex flex-col gap-1.5">
                    <label for="householdName" class="ryh-label">Household name</label>
                    <input id="householdName" v-model="createForm.name" type="text" class="ryh-input" placeholder="The Doe household" />
                    <InputError :message="createForm.errors.name" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="householdSize" class="ryh-label">Household size</label>
                    <select id="householdSize" v-model="createForm.size" class="ryh-input font-semibold">
                        <option v-for="option in sizes" :key="option" :value="option">{{ option }}</option>
                    </select>
                </div>

                <div class="rounded-[16px] bg-ryh-soft px-4 py-3 text-[13px] leading-relaxed text-ryh-ink2">
                    <template v-if="props.trialAvailable">
                        Your household gets a free month, then £3.99/month (or £39.99/year) — no card needed today.
                    </template>
                    <template v-else>You've already had your free month, so this household starts at £3.99/month (or £39.99/year).</template>
                </div>

                <div class="border-t border-ryh-line pt-4">
                    <div class="text-[13px] font-bold">
                        Invite the rest of the house <span class="font-medium text-ryh-ink3">— you can do this later</span>
                    </div>

                    <div v-if="createForm.invites.length" class="mt-2.5 flex flex-col gap-2">
                        <div
                            v-for="(invite, index) in createForm.invites"
                            :key="invite.email"
                            class="flex items-center gap-2.5 rounded-2xl bg-ryh-sunk px-3 py-2.5"
                        >
                            <span class="flex-1 text-[13.5px] font-semibold">{{ invite.email }}</span>
                            <span class="text-xs font-bold text-ryh-ink3">{{ invite.role }}</span>
                            <button
                                type="button"
                                class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[9px] text-xs text-ryh-ink3 transition-colors hover:bg-ryh-card hover:text-ryh-coral"
                                @click="removeInvite(index)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div v-if="!canAddInvite" class="mt-2.5 text-[12.5px] text-ryh-ink3">
                        That's everyone — a household holds {{ props.maxMembers }} people, including you.
                    </div>
                    <div v-else class="mt-2.5 flex gap-2">
                        <input
                            v-model="inviteEmail"
                            type="email"
                            placeholder="their@email.co.uk"
                            class="h-[46px] min-w-0 flex-1 rounded-[13px] border border-ryh-line bg-ryh-sunk px-3.5 text-sm placeholder:text-ryh-ink3"
                            @keydown.enter.prevent="addInvite"
                        />
                        <select
                            v-model="inviteRole"
                            class="h-[46px] w-[130px] rounded-[13px] border border-ryh-line bg-ryh-sunk px-2.5 text-[13px] font-semibold"
                        >
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                        <button
                            type="button"
                            class="h-[46px] rounded-[13px] border border-ryh-line px-4 text-[13.5px] font-bold transition-colors hover:bg-ryh-soft"
                            @click="addInvite"
                        >
                            Add
                        </button>
                    </div>
                    <InputError :message="inviteError" />

                    <div v-if="invitesMinors" class="mt-3">
                        <GuardianConfirm id="setup-guardian" v-model="createForm.guardian_confirmed" />
                        <InputError :message="createForm.errors.guardian_confirmed" />
                    </div>
                </div>

                <div class="mt-2 flex items-center gap-3.5">
                    <button
                        type="submit"
                        class="ryh-btn bg-ryh-coral px-6 text-white"
                        :disabled="createForm.name.trim() === '' || (invitesMinors && !createForm.guardian_confirmed) || createForm.processing"
                    >
                        <LoaderCircle v-if="createForm.processing" class="h-4 w-4 animate-spin" />
                        Create household
                    </button>
                </div>
            </form>

            <form v-else class="mt-5 flex flex-col gap-3.5 rounded-[20px] border border-ryh-line bg-ryh-card p-6" @submit.prevent="submitJoin">
                <div class="flex flex-col gap-1.5">
                    <label for="joinCode" class="ryh-label">Invite code</label>
                    <input
                        id="joinCode"
                        v-model="joinForm.code"
                        type="text"
                        class="ryh-input font-mono text-lg uppercase tracking-[0.12em]"
                        placeholder="DOE-4Q72KD"
                    />
                    <span class="text-[12.5px] text-ryh-ink3">Ask whoever set the household up to send you the code.</span>
                    <InputError :message="joinForm.errors.code" />
                </div>

                <div class="mt-2 flex items-center gap-3.5">
                    <button type="submit" class="ryh-btn bg-ryh-coral px-6 text-white" :disabled="!codeLooksValid || joinForm.processing">
                        <LoaderCircle v-if="joinForm.processing" class="h-4 w-4 animate-spin" />
                        Request to join
                    </button>
                </div>

                <div class="text-[13px] leading-relaxed text-ryh-ink3">Owners must approve new members before you get access.</div>
            </form>
        </div>
    </AuthWizardLayout>
</template>
