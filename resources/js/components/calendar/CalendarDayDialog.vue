<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useCalendarEditor } from '@/composables/useCalendarEditor';
import { eventsFor, isEditable } from '@/lib/calendar';

/** A whole day, for month cells with more events than fit. */
defineProps<{ filter: number | null }>();

const { expandedDay, openEdit } = useCalendarEditor();
</script>

<template>
    <Dialog :open="expandedDay !== null" @update:open="expandedDay = null">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ expandedDay?.dayLabel }} {{ expandedDay?.dateLabel }}</DialogTitle>
            </DialogHeader>

            <div class="flex flex-col gap-1.5">
                <button
                    v-for="event in expandedDay ? eventsFor(expandedDay, filter) : []"
                    :key="event.id"
                    type="button"
                    :disabled="!isEditable(event)"
                    class="flex items-center gap-3 rounded-[13px] bg-ryh-sunk px-3 py-2.5 text-left transition hover:translate-x-[3px] disabled:opacity-50 disabled:hover:translate-x-0"
                    @click="openEdit(event)"
                >
                    <span class="h-[30px] w-[3px] flex-none rounded-sm" :style="{ background: event.colour }"></span>
                    <span class="w-[92px] flex-none font-mono text-[11.5px] text-ryh-ink2">{{ event.time }}</span>
                    <span class="flex-1 text-[13.5px] font-medium">{{ event.title }}</span>
                    <span class="text-[11.5px] text-ryh-ink3">{{ event.who }}</span>
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>
