<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Button from '@/components/ryh/ui/Button.vue';
import Field from '@/components/ryh/ui/Field.vue';
import Input from '@/components/ryh/ui/Input.vue';
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
                <Field label="Name" for="item-name" :error="itemForm.errors.name">
                    <Input id="item-name" v-model="itemForm.name" type="text" required />
                </Field>

                <Field label="Quantity" for="item-quantity">
                    <Input id="item-quantity" v-model="itemForm.quantity" type="text" placeholder="x1" />
                </Field>

                <div class="flex items-center gap-2">
                    <Button class="w-auto" type="submit" :disabled="itemForm.processing">Save changes</Button>
                    <Button variant="secondary" class="w-auto" @click="itemDialogOpen = false">Cancel</Button>
                    <Button variant="danger" class="ml-auto w-auto" v-if="editingItem?.can.delete" @click="destroyItem"> Delete </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
