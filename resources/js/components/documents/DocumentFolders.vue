<script setup lang="ts">
import IconButton from '@/components/ui/IconButton.vue';
import { moveDocument } from '@/composables/useMoveDocument';
import type { DocumentFolderData } from '@/types/generated';
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

/** The folder list (drop a document on one to move it there), a new-folder field, and storage used. */
const props = defineProps<{ folders: DocumentFolderData[]; active: DocumentFolderData | null; storageLabel: string; storagePct: number }>();

/** The document being dragged, if any. */
const draggingId = defineModel<number | null>('draggingId', { required: true });

const folderForm = useForm({ name: '' });

function addFolder(): void {
    if (folderForm.name.trim() === '') {
        return;
    }

    folderForm.post(route('documents.folders.store'), {
        preserveScroll: true,
        onSuccess: () => folderForm.reset(),
    });
}

function dropOnFolder(folder: DocumentFolderData): void {
    const documentId = draggingId.value;
    draggingId.value = null;

    if (documentId === null || folder.id === props.active?.id) {
        return;
    }

    moveDocument(documentId, folder.id);
}
</script>

<template>
    <div class="flex w-full flex-none flex-col gap-1.5 lg:w-[244px]">
        <Link
            v-for="folder in folders"
            :key="folder.id"
            :href="route('documents.index', { folder: folder.id })"
            class="flex min-h-[48px] items-center gap-2.5 rounded-[14px] border px-3.5 text-left transition hover:bg-ryh-card"
            :class="active?.id === folder.id ? 'border-ryh-line bg-ryh-card' : 'border-transparent bg-transparent'"
            @dragover.prevent
            @drop="dropOnFolder(folder)"
        >
            <span class="grid h-[26px] w-[26px] flex-none place-items-center rounded-lg text-[13px]" :style="{ background: folder.colour }">
                {{ folder.icon }}
            </span>
            <span class="flex-1 text-sm font-semibold">{{ folder.name }}</span>
            <span class="font-mono text-[11.5px] text-ryh-ink3">{{ folder.count }}</span>
        </Link>

        <div class="mt-1 flex gap-2">
            <input
                v-model="folderForm.name"
                placeholder="New folder"
                aria-label="New folder"
                class="h-11 flex-1 rounded-[13px] border border-dashed border-ryh-line bg-transparent px-3 text-[13.5px]"
                @keydown.enter="addFolder"
            />
            <IconButton
                label="Add folder"
                class="h-11 w-11 flex-none rounded-[13px] bg-ryh-soft text-lg transition hover:bg-ryh-line"
                @click="addFolder"
            >
                <Plus class="mx-auto h-4 w-4" :stroke-width="2.5" />
            </IconButton>
        </div>

        <div class="mt-3.5 rounded-2xl border border-ryh-line bg-ryh-panel p-4">
            <div class="flex items-baseline gap-2">
                <span class="text-xs font-bold">Storage</span>
                <span class="ml-auto font-mono text-[11.5px] text-ryh-ink3">{{ storageLabel }}</span>
            </div>
            <div class="mt-2.5 h-[7px] overflow-hidden rounded-full bg-ryh-sunk">
                <span class="block h-full rounded-full bg-ryh-coral" :style="{ width: storagePct + '%' }"></span>
            </div>
            <div class="mt-2.5 text-[11.5px] leading-relaxed text-ryh-ink3">Encrypted at rest. Everything downloadable, any time.</div>
        </div>
    </div>
</template>
