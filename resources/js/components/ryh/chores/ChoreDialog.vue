<script setup lang="ts">
import Button from '@/components/ryh/ui/Button.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CHORE_BOARD_PROPS } from '@/lib/ryh';
import type { ChoreData, MemberData } from '@/types/generated';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Renaming or reassigning a chore. Opened with openEdit(chore). */
defineProps<{ members: MemberData[] }>();

const open = ref(false);
const editing = ref<ChoreData | null>(null);

const form = useForm({
    name: '',
    assigned_to: '',
});

function openEdit(chore: ChoreData): void {
    editing.value = chore;
    form.clearErrors();
    form.defaults({
        name: chore.name,
        assigned_to: chore.assignee ? String(chore.assignee.id) : '',
    });
    form.reset();
    open.value = true;
}

function submit(): void {
    if (editing.value === null) {
        return;
    }

    form.transform((data) => ({
        ...data,
        assigned_to: data.assigned_to === '' ? null : Number(data.assigned_to),
    }));

    form.patch(route('chores.update', { chore: editing.value.id }), {
        preserveScroll: true,
        only: CHORE_BOARD_PROPS,
        onSuccess: () => {
            open.value = false;
        },
    });
}

defineExpose({ openEdit });
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">Edit chore</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="ryh-label">Name</label>
                    <input id="name" v-model="form.name" type="text" class="ryh-input" required placeholder="What needs doing?" />
                    <p v-if="form.errors.name" class="text-[12.5px] text-ryh-coral">{{ form.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="assigned_to" class="ryh-label">Assignee</label>
                    <select id="assigned_to" v-model="form.assigned_to" class="ryh-input">
                        <option value="">Unassigned</option>
                        <option v-for="member in members" :key="member.id" :value="String(member.id)">
                            {{ member.name }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <Button class="w-auto" type="submit" :disabled="form.processing">Save changes</Button>
                    <Button variant="secondary" class="w-auto" @click="open = false">Cancel</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
