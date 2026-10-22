<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DocumentCard from '@/components/ryh/documents/DocumentCard.vue';
import { useConfirm } from '@/composables/useConfirm';
import type { DocumentData, DocumentFolderData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/** The open folder's documents, searched and sorted in the browser, with deleting the folder or a document. */
const props = defineProps<{ active: DocumentFolderData; documents: DocumentData[] }>();

/** The document being dragged, if any. */
const draggingId = defineModel<number | null>('draggingId', { required: true });

const query = ref('');
const sort = ref<'Recent' | 'Name'>('Recent');

const shown = computed(() => {
    const q = query.value.trim().toLowerCase();
    let list = props.documents.filter((d) => !q || d.name.toLowerCase().includes(q) || d.tags.join(' ').toLowerCase().includes(q));

    if (sort.value === 'Name') {
        list = [...list].sort((a, b) => a.name.localeCompare(b.name));
    }

    return list;
});

const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

function destroyFolder(): void {
    const folder = props.active;

    confirmFirst(`Delete “${folder.name}”? All its documents will be removed too.`, () => {
        router.delete(route('documents.folders.destroy', { folder: folder.id }));
    });
}

function destroyDocument(document: DocumentData): void {
    confirmFirst(`Delete “${document.name}”?`, () => {
        router.delete(route('documents.destroy', { document: document.id }), { preserveScroll: true });
    });
}
</script>

<template>
    <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <div class="flex flex-wrap items-center gap-3 border-b border-ryh-line pb-3.5">
            <div>
                <h3 class="text-lg font-extrabold tracking-[-0.02em]">{{ active.name }}</h3>
                <div class="mt-1 text-[13px] text-ryh-ink3">{{ documents.length }} files</div>
            </div>
            <div class="ml-auto flex w-full items-center gap-2 sm:w-auto">
                <div class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl bg-ryh-sunk px-3.5 sm:w-[220px] sm:flex-none">
                    <Search class="h-3.5 w-3.5 flex-none text-ryh-ink3" />
                    <input
                        v-model="query"
                        placeholder="Search filenames and tags"
                        aria-label="Search filenames and tags"
                        class="h-full min-w-0 flex-1 bg-transparent text-[13px] outline-none"
                    />
                </div>
                <div class="flex gap-1 rounded-xl bg-ryh-soft p-1">
                    <button
                        v-for="option in ['Recent', 'Name'] as const"
                        :key="option"
                        type="button"
                        class="h-8 rounded-lg px-3 text-[12.5px] font-bold transition"
                        :class="sort === option ? 'bg-ryh-card text-ryh-ink' : 'text-ryh-ink3'"
                        @click="sort = option"
                    >
                        {{ option }}
                    </button>
                </div>
                <button type="button" aria-label="Delete folder" class="rounded-lg p-1.5 transition hover:bg-ryh-soft" @click="destroyFolder">
                    <Trash2 class="h-4 w-4 text-ryh-ink3" />
                </button>
            </div>
        </div>

        <div v-if="shown.length" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <DocumentCard
                v-for="document in shown"
                :key="document.id"
                v-model:dragging-id="draggingId"
                :document="document"
                @delete="destroyDocument"
            />
        </div>
        <div v-else class="py-14 text-center">
            <div class="text-[15px] font-bold">Nothing in here yet</div>
            <div class="mt-1.5 text-[13px] text-ryh-ink3">Drop a file above, or drag one in from another folder.</div>
        </div>
    </section>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
