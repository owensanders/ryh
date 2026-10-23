<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

defineProps<{
    open: boolean;
    message: string;
    confirmLabel?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">Are you sure?</DialogTitle>
            </DialogHeader>
            <p class="text-[13.5px] text-ryh-ink2">{{ message }}</p>
            <div class="flex items-center gap-2">
                <Button class="w-auto" @click="emit('confirm')">
                    {{ confirmLabel ?? 'Delete' }}
                </Button>
                <Button variant="secondary" class="w-auto" @click="emit('update:open', false)">Cancel</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
