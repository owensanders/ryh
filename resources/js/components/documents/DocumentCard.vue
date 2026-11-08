<script setup lang="ts">
import IconButton from '@/components/ui/IconButton.vue';
import Select from '@/components/ui/Select.vue';
import { moveDocument } from '@/composables/useMoveDocument';
import type { DocumentData, DocumentFolderData } from '@/types/generated';
import { Download, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * One document: move it (drag it onto a folder, or pick one from its "Move
 * to" menu, which works from a keyboard or a touch screen), download it, or
 * delete it.
 */
const props = defineProps<{ document: DocumentData; folders: DocumentFolderData[]; activeId: number }>();

const otherFolders = computed(() => props.folders.filter((folder) => folder.id !== props.activeId));

function moveTo(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const folderId = Number(select.value);
    select.value = '';

    if (folderId) {
        moveDocument(props.document.id, folderId);
    }
}

const emit = defineEmits<{ delete: [document: DocumentData] }>();

/** The document being dragged, if any. */
const draggingId = defineModel<number | null>('draggingId', { required: true });
</script>

<template>
    <div
        draggable="true"
        class="rounded-[18px] border border-ryh-line bg-ryh-sunk p-4 transition hover:-translate-y-0.5 hover:shadow-ryh"
        :style="{ opacity: draggingId === document.id ? 0.35 : 1 }"
        @dragstart="draggingId = document.id"
        @dragend="draggingId = null"
    >
        <div class="flex items-start gap-3">
            <span class="grid h-12 w-10 flex-none place-items-center rounded-lg bg-ryh-soft font-mono text-[10.5px] font-semibold uppercase">
                {{ document.extension }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="break-words text-sm font-bold leading-tight">{{ document.name }}</div>
                <div class="mt-1 text-[11.5px] text-ryh-ink3">{{ document.meta }}</div>
            </div>
        </div>

        <div v-if="document.expiryLabel || document.tags.length" class="mt-3 flex flex-wrap items-center gap-1.5">
            <span
                v-if="document.expiryLabel"
                class="rounded-lg px-2.5 py-1 text-[11px] font-bold text-[#0E1A2B]"
                :class="document.isUrgent ? 'bg-ryh-sun' : 'bg-ryh-soft'"
            >
                {{ document.expiryLabel }}
            </span>
            <span v-for="tag in document.tags" :key="tag" class="rounded-lg bg-ryh-soft px-2.5 py-1 text-[11px] font-semibold text-ryh-ink2">
                {{ tag }}
            </span>
        </div>

        <!-- Wraps on a narrow card: the actions drop to their own line, right-aligned, rather than spilling out. -->
        <div class="mt-3.5 flex flex-wrap items-center gap-2 border-t border-ryh-line pt-3">
            <div v-if="document.addedBy" class="flex min-w-0 items-center gap-2">
                <span
                    class="grid h-[22px] w-[22px] flex-none place-items-center rounded-lg text-[9px] font-extrabold text-[#0E1A2B]"
                    :style="{ background: document.addedBy.colour }"
                >
                    {{ document.addedBy.initials }}
                </span>
                <span class="min-w-0 truncate text-[11.5px] text-ryh-ink3">Added by {{ document.addedBy.name }}</span>
            </div>
            <div class="ml-auto flex flex-none items-center gap-1">
                <Select
                    v-if="otherFolders.length"
                    model-value=""
                    bare
                    :aria-label="`Move ${document.name} to another folder`"
                    class="h-[30px] max-w-[110px] flex-none rounded-lg px-1.5 text-[11.5px] font-semibold text-ryh-ink2 transition hover:bg-ryh-card focus-visible:outline focus-visible:outline-2 focus-visible:outline-ryh-coral"
                    @change="moveTo"
                >
                    <option value="">Move to…</option>
                    <option v-for="folder in otherFolders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
                </Select>
                <a
                    :href="route('documents.download', { document: document.id })"
                    title="Download"
                    :aria-label="`Download ${document.name}`"
                    class="grid h-[30px] w-[30px] place-items-center rounded-lg text-ryh-ink2 transition hover:bg-ryh-card"
                >
                    <Download class="h-3.5 w-3.5" />
                </a>
                <IconButton
                    :label="`Delete ${document.name}`"
                    class="grid h-[30px] w-[30px] place-items-center rounded-lg text-ryh-ink3 transition hover:bg-ryh-card hover:text-ryh-coral"
                    @click="emit('delete', document)"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                </IconButton>
            </div>
        </div>
    </div>
</template>
