<script setup lang="ts">
import GuardianConfirm from '@/components/GuardianConfirm.vue';
import HouseInvite from '@/components/house/HouseInvite.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { forMinors } from '@/lib/house';
import type { HouseData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/** People in this home: their roles, approving join requests, removing people, and inviting more. */
const props = defineProps<{ house: HouseData }>();

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
</script>

<template>
    <Card as="section">
        <div class="mb-3.5 flex items-center gap-2.5">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">People in this home</h3>
            <span class="text-xs text-ryh-ink3" data-testid="member-count">{{ house.memberCount }} of {{ house.maxMembers }} members</span>
        </div>

        <div class="flex flex-col gap-2">
            <div
                v-for="member in house.members"
                :key="member.id"
                class="flex flex-wrap items-center gap-x-3.5 gap-y-2.5 rounded-2xl border border-ryh-line p-3.5 sm:flex-nowrap"
                :class="member.pending ? 'bg-ryh-sunk' : 'bg-ryh-card'"
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
                        <span v-if="member.you" class="rounded-lg bg-ryh-soft px-2 py-[3px] text-[11px] font-bold text-ryh-ink2">You</span>
                        <span v-if="member.pending" class="rounded-lg bg-ryh-sun px-2 py-[3px] text-[11px] font-bold text-[#0E1A2B]">
                            {{ member.pendingReason === 'requested' ? 'Wants to join' : 'Invite pending' }}
                        </span>
                    </div>
                    <div class="mt-1 truncate text-[12.5px] text-ryh-ink3">{{ member.email }}</div>
                </div>
                <select
                    :value="member.role"
                    :aria-label="`Role for ${member.name}`"
                    class="order-last ml-[58px] h-[38px] min-w-0 flex-1 rounded-[11px] border border-ryh-line bg-ryh-sunk px-2.5 text-[13px] font-semibold sm:order-none sm:ml-0 sm:w-[132px] sm:flex-none"
                    @change="setRole(member.id, ($event.target as HTMLSelectElement).value, $event)"
                >
                    <option v-for="option in house.roleOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <button
                    v-if="member.pendingReason === 'requested'"
                    type="button"
                    title="Approve"
                    :aria-label="`Approve ${member.name}`"
                    class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-mint"
                    @click="approveMember(member.id)"
                >
                    ✓
                </button>
                <button
                    type="button"
                    title="Remove from household"
                    :aria-label="`Remove ${member.name} from household`"
                    class="h-[38px] w-[38px] flex-none rounded-[11px] text-sm text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-coral"
                    @click="removeMember(member.id)"
                >
                    ✕
                </button>
            </div>
        </div>

        <HouseInvite :house="house" />
    </Card>

    <Dialog :open="pendingRole !== null" @update:open="cancelPendingRole">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">Make {{ pendingRole?.name }} a {{ pendingRole?.label }}?</DialogTitle>
                <DialogDescription class="text-[13.5px] text-ryh-ink2">
                    Teen and Child are for under-18s, who can only be part of a household with a parent or guardian's agreement.
                </DialogDescription>
            </DialogHeader>
            <GuardianConfirm id="role-guardian" v-model="pendingRoleConfirmed" :who="pendingRole?.name" />
            <div class="flex items-center gap-2">
                <Button class="w-auto" :disabled="!pendingRoleConfirmed" @click="confirmPendingRole"> Change role </Button>
                <Button variant="secondary" class="w-auto" @click="cancelPendingRole(false)">Cancel</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
