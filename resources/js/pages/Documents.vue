<script setup lang="ts">
import DocumentFolders from '@/components/ryh/documents/DocumentFolders.vue';
import DocumentList from '@/components/ryh/documents/DocumentList.vue';
import DocumentUpload from '@/components/ryh/documents/DocumentUpload.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { DocumentData, DocumentFolderData } from '@/types/generated';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

defineProps<{
    folders: DocumentFolderData[];
    active: DocumentFolderData | null;
    documents: DocumentData[];
    storageLabel: string;
    storagePct: number;
}>();

// A document card being dragged onto a folder to move it.
const draggingId = ref<number | null>(null);
</script>

<template>
    <RyhLayout title="Documents" :subtitle="`${documents.length} files`">
        <div class="flex animate-ryh-rise flex-col items-start gap-4 lg:flex-row">
            <DocumentFolders
                v-model:dragging-id="draggingId"
                :folders="folders"
                :active="active"
                :storage-label="storageLabel"
                :storage-pct="storagePct"
            />

            <div v-if="active" class="flex w-full min-w-0 flex-1 flex-col gap-3.5">
                <DocumentUpload :active="active" />
                <DocumentList v-model:dragging-id="draggingId" :active="active" :documents="documents" />
            </div>

            <div v-else class="flex w-full min-w-0 flex-1 flex-col items-center justify-center gap-3 py-24 text-center">
                <div class="text-[15px] font-bold">No folders yet</div>
                <div class="text-[13px] text-ryh-ink3">Create your first folder to start uploading documents.</div>
            </div>
        </div>
    </RyhLayout>
</template>
