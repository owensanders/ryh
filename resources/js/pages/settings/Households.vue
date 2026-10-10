<script setup lang="ts">
import LeaveHousehold from '@/components/LeaveHousehold.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { HouseholdOption } from '@/types/ryh';
import { Link, router } from '@inertiajs/vue3';
import { House } from 'lucide-vue-next';

defineOptions({ layout: RyhShell });

defineProps<{
    households: HouseholdOption[];
    currentHouseholdId: number | null;
}>();

function switchTo(householdId: number): void {
    router.patch(route('households.switch'), { household_id: householdId });
}
</script>

<template>
    <RyhLayout title="Settings" subtitle="Switch households or leave one you no longer need">
        <SettingsLayout>
            <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Your households</h3>
                <p class="mt-1 text-[13px] text-ryh-ink3">Switch which household is open, or leave one you belong to.</p>

                <div class="mt-5 flex flex-col gap-2.5">
                    <div
                        v-for="household in households"
                        :key="household.id"
                        class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-[18px] border-[1.5px] p-4 md:flex-nowrap"
                        :class="household.id === currentHouseholdId ? 'border-ryh-coral bg-ryh-sunk' : 'border-ryh-line'"
                    >
                        <span class="grid h-11 w-11 flex-none place-items-center rounded-[15px] text-[#0E1A2B]" style="background: var(--ryh-t1)">
                            <House class="h-5 w-5" :stroke-width="1.8" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                <span class="min-w-0 truncate text-[15px] font-bold">{{ household.name }}</span>
                                <span class="rounded-lg bg-ryh-sunk px-2 py-[3px] text-[11px] font-bold text-ryh-ink2">{{
                                    household.roleLabel
                                }}</span>
                            </span>
                            <span class="mt-0.5 block text-[12.5px] text-ryh-ink3">{{ household.memberCount }} members</span>
                        </span>
                        <span class="hidden items-center sm:flex">
                            <span
                                v-for="avatar in household.memberAvatars"
                                :key="avatar.initials"
                                class="-ml-2 grid h-7 w-7 place-items-center rounded-lg border-2 border-ryh-card text-[10px] font-extrabold text-[#0E1A2B]"
                                :style="{ background: avatar.colour }"
                            >
                                {{ avatar.initials }}
                            </span>
                        </span>

                        <div class="flex w-full items-center gap-2 md:w-auto">
                            <span
                                v-if="household.id === currentHouseholdId"
                                class="ryh-btn flex-1 bg-ryh-card px-4 text-ryh-ink2 md:w-auto md:flex-none"
                            >
                                Currently open
                            </span>
                            <button
                                v-else
                                type="button"
                                class="ryh-btn flex-1 bg-ryh-soft px-4 text-ryh-ink md:w-auto md:flex-none"
                                @click="switchTo(household.id)"
                            >
                                Switch
                            </button>

                            <LeaveHousehold :household-id="household.id" :household-name="household.name" />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-3.5 flex flex-wrap items-center gap-3 rounded-[18px] border border-dashed border-ryh-line bg-ryh-sunk p-4 sm:flex-nowrap"
                >
                    <span class="grid h-9 w-9 flex-none place-items-center rounded-xl bg-ryh-card text-base">🔑</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13.5px] font-bold">Somewhere new to add?</span>
                        <span class="mt-0.5 block text-[12px] text-ryh-ink3"
                            >Join another household with an invite code, or set one up from scratch.</span
                        >
                    </span>
                    <Link :href="route('household.setup')" class="ryh-btn w-full bg-ryh-card px-4 text-ryh-ink sm:w-auto">Add household</Link>
                </div>
            </section>
        </SettingsLayout>
    </RyhLayout>
</template>
