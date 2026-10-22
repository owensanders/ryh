<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';
import type { ShoppingItemData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Editing or deleting one item. Opened with openEdit(item). */
const itemDialogOpen = ref(false);
const editingItem = ref<ShoppingItemData | null>(null);

const itemForm = useForm({ name: '', quantity: '' });
const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

function openEdit(item: ShoppingItemData): void {
    editingItem.value = item;
    itemForm.clearErrors();
    itemForm.defaults({ name: item.name, quantity: item.quantity ?? '' });
    itemForm.reset();
    itemDialogOpen.value = true;
}

function submitItem(): void {
    if (editingItem.value === null) {
        return;
    }

    itemForm.patch(route('shopping.items.update', { item: editingItem.value.id }), {
        preserveScroll: true,
        onSuccess: () => (itemDialogOpen.value = false),
    });
}

function destroyItem(): void {
    if (editingItem.value === null) {
        return;
    }

    const item = editingItem.value;
    confirmFirst('Delete this item?', () => {
        router.delete(route('shopping.items.destroy', { item: item.id }), {
            preserveScroll: true,
            onSuccess: () => (itemDialogOpen.value = false),
        });
    });
}

defineExpose({ openEdit });
</script>

<template>
    <Dialog :open="itemDialogOpen" @update:open="itemDialogOpen = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">Edit item</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submitItem">
                <div class="flex flex-col gap-1.5">
                    <label for="item-name" class="ryh-label">Name</label>
                    <input id="item-name" v-model="itemForm.name" type="text" class="ryh-input" required />
                    <p v-if="itemForm.errors.name" class="text-[12.5px] text-ryh-coral">{{ itemForm.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="item-quantity" class="ryh-label">Quantity</label>
                    <input id="item-quantity" v-model="itemForm.quantity" type="text" class="ryh-input" placeholder="x1" />
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="itemForm.processing">Save changes</button>
                    <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="itemDialogOpen = false">Cancel</button>
                    <button
                        v-if="editingItem?.can.delete"
                        type="button"
                        class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-coral"
                        @click="destroyItem"
                    >
                        Delete
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
