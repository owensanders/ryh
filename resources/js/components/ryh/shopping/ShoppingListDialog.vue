<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PALETTE } from '@/lib/ryh';
import type { ShoppingListData } from '@/types/generated';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Creating a list, or renaming and recolouring one. Opened with openNew() or openEdit(list). */
const listDialogOpen = ref(false);
const editingList = ref<ShoppingListData | null>(null);

const listForm = useForm({ name: '', colour: 'mint' });

function openNew(): void {
    editingList.value = null;
    listForm.clearErrors();
    listForm.defaults({ name: '', colour: 'mint' });
    listForm.reset();
    listDialogOpen.value = true;
}

function openEdit(list: ShoppingListData): void {
    editingList.value = list;
    listForm.clearErrors();
    listForm.defaults({ name: list.name, colour: list.colourKey });
    listForm.reset();
    listDialogOpen.value = true;
}

function submitList(): void {
    const options = { preserveScroll: true, onSuccess: () => (listDialogOpen.value = false) };

    if (editingList.value) {
        listForm.patch(route('shopping.lists.update', { list: editingList.value.id }), options);
    } else {
        listForm.post(route('shopping.lists.store'), options);
    }
}

defineExpose({ openNew, openEdit });
</script>

<template>
    <Dialog :open="listDialogOpen" @update:open="listDialogOpen = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ editingList ? 'Rename list' : 'New list' }}</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submitList">
                <div class="flex flex-col gap-1.5">
                    <label for="list-name" class="ryh-label">Name</label>
                    <input id="list-name" v-model="listForm.name" type="text" class="ryh-input" required placeholder="e.g. Tesco" />
                    <p v-if="listForm.errors.name" class="text-[12.5px] text-ryh-coral">{{ listForm.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="ryh-label">Colour</span>
                    <div class="flex gap-2">
                        <button
                            v-for="colour in PALETTE"
                            :key="colour"
                            type="button"
                            :title="colour"
                            :aria-label="colour"
                            :aria-pressed="listForm.colour === colour"
                            class="h-7 w-7 rounded-[9px] transition"
                            :class="listForm.colour === colour ? 'ring-2 ring-ryh-ink ring-offset-2 ring-offset-ryh-card' : ''"
                            :style="{ background: `var(--ryh-${colour})` }"
                            @click="listForm.colour = colour"
                        ></button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="listForm.processing">
                        {{ editingList ? 'Save changes' : 'Add list' }}
                    </button>
                    <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="listDialogOpen = false">Cancel</button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
