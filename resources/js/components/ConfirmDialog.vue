<script setup lang="ts">
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
        <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">Are you sure?</DialogTitle>
            </DialogHeader>
            <p class="text-[13.5px] text-hh-ink2">{{ message }}</p>
            <div class="flex items-center gap-2">
                <button type="button" class="hh-btn w-auto bg-hh-coral text-white" @click="emit('confirm')">
                    {{ confirmLabel ?? 'Delete' }}
                </button>
                <button type="button" class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="emit('update:open', false)">Cancel</button>
            </div>
        </DialogContent>
    </Dialog>
</template>
