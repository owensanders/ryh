<script setup lang="ts">
import Button from '@/components/ryh/ui/Button.vue';
import { CHORE_BOARD_PROPS } from '@/lib/ryh';
import type { MemberData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

/** Adding a chore, optionally assigned to someone. */
defineProps<{ members: MemberData[] }>();

const draft = ref('');
const draftAssignedTo = ref('');

function addChore(): void {
    if (!draft.value.trim()) {
        return;
    }

    router.post(
        route('chores.store'),
        {
            name: draft.value.trim(),
            assigned_to: draftAssignedTo.value === '' ? null : Number(draftAssignedTo.value),
        },
        {
            preserveScroll: true,
            only: CHORE_BOARD_PROPS,
            onSuccess: () => {
                draft.value = '';
            },
        },
    );
}
</script>

<template>
    <section class="flex flex-wrap items-center gap-2.5 rounded-[20px] border border-ryh-line bg-ryh-panel p-[18px]">
        <input
            v-model="draft"
            type="text"
            placeholder="Add a chore, e.g. Clean the bathroom"
            aria-label="New chore"
            class="ryh-input h-12 min-w-[220px] flex-1"
            @keydown.enter="addChore"
        />
        <select v-model="draftAssignedTo" aria-label="Assign new chore to" class="ryh-input h-12 w-[132px]">
            <option value="">Unassigned</option>
            <option v-for="member in members" :key="member.id" :value="String(member.id)">{{ member.name }}</option>
        </select>
        <Button class="w-auto" @click="addChore">
            <Plus class="h-4 w-4" :stroke-width="2.5" />
            Add chore
        </Button>
    </section>
</template>
