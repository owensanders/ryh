<script setup lang="ts">
import ChoreAddForm from '@/components/chores/ChoreAddForm.vue';
import ChoreBoard from '@/components/chores/ChoreBoard.vue';
import ChoreDialog from '@/components/chores/ChoreDialog.vue';
import ChoreNotify from '@/components/chores/ChoreNotify.vue';
import ChoreScores from '@/components/chores/ChoreScores.vue';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { ChoreColumnData, MemberData, MemberScoreData } from '@/types/generated';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

defineProps<{
    columns: ChoreColumnData[];
    scores: MemberScoreData[];
    members: MemberData[];
    notifyChoresAvailableAt: string | null;
}>();

const can = useHouseholdAbilities();

const choreDialog = ref<InstanceType<typeof ChoreDialog> | null>(null);
</script>

<template>
    <RyhLayout title="Chores" subtitle="This week">
        <div class="flex animate-ryh-rise flex-col gap-[18px]">
            <ChoreScores :scores="scores" />
            <ChoreNotify v-if="can?.notifyChores" :available-at="notifyChoresAvailableAt" />
            <ChoreAddForm v-if="can?.createChores" :members="members" />
            <ChoreBoard :columns="columns" @edit="choreDialog?.openEdit($event)" />
        </div>

        <ChoreDialog ref="choreDialog" :members="members" />
    </RyhLayout>
</template>
