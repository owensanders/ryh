<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import IconButton from '@/components/ryh/ui/IconButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useOptimistic } from '@/composables/useOptimistic';
import { CHORE_BOARD_PROPS, tickStyle } from '@/lib/ryh';
import type { ChoreColumnData, ChoreData, ChoreStatusEnum } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref } from 'vue';

/** Today and Done side by side. Ticking and dragging between them show at once. */
const props = defineProps<{ columns: ChoreColumnData[] }>();

const emit = defineEmits<{ edit: [chore: ChoreData] }>();

// The server's columns replace these when it answers.
const { state: board, apply: updateBoard } = useOptimistic(() => props.columns);
const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

function moveOnBoard(columns: ChoreColumnData[], id: number, status: ChoreStatusEnum): void {
    const from = columns.find((column) => column.items.some((item) => item.id === id));
    const to = columns.find((column) => column.status === status);

    if (!from || !to || from === to) {
        return;
    }

    const [chore] = from.items.splice(
        from.items.findIndex((item) => item.id === id),
        1,
    );
    from.count--;
    chore.status = status;
    chore.done = status === 'done';
    to.items.push(chore);
    to.count++;
}

const draggingId = ref<number | null>(null);

function startDrag(event: DragEvent, id: number): void {
    draggingId.value = id;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function drop(status: ChoreStatusEnum): void {
    const chore = draggingId.value;
    draggingId.value = null;

    if (chore === null) {
        return;
    }

    updateBoard(
        (columns) => moveOnBoard(columns, chore, status),
        (callbacks) => router.patch(route('chores.move', { chore }), { status }, { preserveScroll: true, only: CHORE_BOARD_PROPS, ...callbacks }),
    );
}

function toggle(chore: ChoreData): void {
    if (!chore.can.toggle) {
        return;
    }

    updateBoard(
        (columns) => moveOnBoard(columns, chore.id, chore.done ? 'today' : 'done'),
        (callbacks) => router.patch(route('chores.toggle', { chore: chore.id }), {}, { preserveScroll: true, only: CHORE_BOARD_PROPS, ...callbacks }),
    );
}

function requestDelete(chore: ChoreData): void {
    confirmFirst('Delete this chore?', () => {
        router.delete(route('chores.destroy', { chore: chore.id }), { preserveScroll: true, only: CHORE_BOARD_PROPS });
    });
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
        <div
            v-for="column in board"
            :key="column.status"
            class="flex min-h-[420px] flex-col gap-2.5 rounded-[20px] border border-dashed p-3.5 transition"
            :style="{
                background: draggingId !== null ? 'var(--ryh-sunk)' : 'var(--ryh-panel)',
                borderColor: draggingId !== null ? 'var(--ryh-line)' : 'transparent',
            }"
            @dragover.prevent
            @drop.prevent="drop(column.status)"
        >
            <div class="flex items-center gap-2">
                <span class="text-[13.5px] font-extrabold">{{ column.title }}</span>
                <span class="rounded-md bg-ryh-soft px-1.5 py-0.5 font-mono text-[11px] text-ryh-ink2">{{ column.count }}</span>
            </div>

            <div
                v-for="chore in column.items"
                :key="chore.id"
                :draggable="chore.can.update"
                class="rounded-2xl border border-ryh-line bg-ryh-card px-3.5 py-3 transition hover:-translate-y-0.5 hover:shadow-ryh"
                :class="chore.can.update ? 'cursor-grab' : ''"
                :style="{ opacity: draggingId === chore.id ? 0.4 : 1 }"
                @dragstart="startDrag($event, chore.id)"
                @dragend="draggingId = null"
                @click="chore.can.update && emit('edit', chore)"
            >
                <div class="flex items-start gap-2.5">
                    <!-- Ticking lives on the box alone so it can't fire at the end of a drag. -->
                    <button
                        type="button"
                        :disabled="!chore.can.toggle"
                        :aria-label="chore.done ? `Mark ${chore.name} as not done` : `Mark ${chore.name} as done`"
                        class="mt-px grid h-[19px] w-[19px] flex-none place-items-center rounded-full border-[1.5px] text-[11px] text-[#0E1A2B] disabled:cursor-not-allowed"
                        :style="tickStyle(chore.done)"
                        @click.stop="toggle(chore)"
                    >
                        {{ chore.done ? '✓' : '' }}
                    </button>
                    <span class="flex-1 text-sm font-medium leading-snug" :class="chore.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                        {{ chore.name }}
                    </span>
                    <IconButton
                        label="Delete chore"
                        v-if="chore.can.delete"
                        class="grid h-6 w-6 flex-none place-items-center rounded-lg border-0 bg-transparent text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-coral"
                        @click.stop="requestDelete(chore)"
                    >
                        <X class="h-3 w-3" :stroke-width="2.5" />
                    </IconButton>
                </div>
                <div v-if="chore.assignee" class="mt-3 flex items-center gap-1.5">
                    <span
                        class="grid h-6 w-6 place-items-center rounded-lg text-[9.5px] font-extrabold text-[#0E1A2B]"
                        :style="{ background: chore.assignee.colour }"
                    >
                        {{ chore.assignee.initials }}
                    </span>
                </div>
            </div>

            <div v-if="column.items.length === 0" class="py-6 text-center text-[12.5px] text-ryh-ink3">
                {{ column.status === 'done' ? 'Nothing ticked off yet.' : 'Drag a chore here.' }}
            </div>
        </div>
    </div>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
