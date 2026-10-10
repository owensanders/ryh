<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { Document, DocumentFolder } from '@/types/ryh';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Download, Plus, Search, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{
    folders: DocumentFolder[];
    active: DocumentFolder | null;
    documents: Document[];
    storageLabel: string;
    storagePct: number;
}>();

// New folder
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

// Search / sort over the active folder's documents (client-side, no round trip)
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

// Upload
const zoneHot = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function chooseFiles(): void {
    fileInput.value?.click();
}

// Inertia only allows one in-flight visit at a time — a new one aborts
// whatever's still running — so multiple files have to upload one after
// another, not all at once.
async function uploadFiles(files: FileList | null): Promise<void> {
    if (props.active === null || files === null) {
        return;
    }

    const folderId = props.active.id;

    for (const file of Array.from(files)) {
        await new Promise<void>((resolve) => {
            router.post(
                route('documents.store', { folder: folderId }),
                { file },
                { preserveScroll: true, forceFormData: true, onFinish: () => resolve() },
            );
        });
    }
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    uploadFiles(target.files);
    target.value = '';
}

function onDrop(event: DragEvent): void {
    event.preventDefault();
    zoneHot.value = false;
    uploadFiles(event.dataTransfer?.files ?? null);
}

// Drag a document card onto another folder to move it
const draggingId = ref<number | null>(null);

function startDrag(id: number): void {
    draggingId.value = id;
}

function dropOnFolder(folder: DocumentFolder): void {
    const documentId = draggingId.value;
    draggingId.value = null;

    if (documentId === null || folder.id === props.active?.id) {
        return;
    }

    router.patch(route('documents.move', { document: documentId }), { folder_id: folder.id }, { preserveScroll: true });
}

// Delete folder
const confirmFolderOpen = ref(false);

function destroyFolder(): void {
    if (props.active !== null) {
        confirmFolderOpen.value = true;
    }
}

function confirmDestroyFolder(): void {
    confirmFolderOpen.value = false;

    if (props.active !== null) {
        router.delete(route('documents.folders.destroy', { folder: props.active.id }));
    }
}

// Delete document
const confirmDocOpen = ref(false);
const deletingDocument = ref<Document | null>(null);

function destroyDocument(document: Document): void {
    deletingDocument.value = document;
    confirmDocOpen.value = true;
}

function confirmDestroyDocument(): void {
    confirmDocOpen.value = false;

    if (deletingDocument.value !== null) {
        router.delete(route('documents.destroy', { document: deletingDocument.value.id }), { preserveScroll: true });
        deletingDocument.value = null;
    }
}
</script>

<template>
    <RyhLayout title="Documents" :subtitle="`${documents.length} files`">
        <div class="flex animate-ryh-rise flex-col items-start gap-4 lg:flex-row">
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
                    <button
                        type="button"
                        aria-label="Add folder"
                        class="h-11 w-11 flex-none rounded-[13px] bg-ryh-soft text-lg transition hover:bg-ryh-line"
                        @click="addFolder"
                    >
                        <Plus class="mx-auto h-4 w-4" :stroke-width="2.5" />
                    </button>
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

            <div v-if="active" class="flex w-full min-w-0 flex-1 flex-col gap-3.5">
                <section
                    class="flex flex-wrap items-center gap-4 rounded-[20px] border-[1.5px] border-dashed p-4 transition-colors sm:flex-nowrap sm:p-5"
                    :class="zoneHot ? 'border-ryh-coral bg-ryh-soft' : 'border-ryh-line bg-ryh-panel'"
                    @dragover.prevent="zoneHot = true"
                    @dragleave="zoneHot = false"
                    @drop="onDrop"
                >
                    <div class="grid h-[46px] w-[46px] flex-none place-items-center rounded-2xl bg-ryh-soft">
                        <Upload class="h-5 w-5 text-ryh-ink2" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[15px] font-bold">
                            <template v-if="zoneHot">Drop to upload</template>
                            <template v-else>
                                <span class="[@media(hover:hover)]:hidden">Upload files</span>
                                <span class="hidden [@media(hover:hover)]:inline">Drag files here to upload</span>
                            </template>
                        </div>
                        <div class="mt-0.5 text-[13px] text-ryh-ink3">PDF, images, spreadsheets. Up to 50MB each · going into {{ active.name }}</div>
                    </div>
                    <button type="button" class="ryh-btn w-full flex-none bg-ryh-coral text-white sm:w-auto" @click="chooseFiles">
                        Choose files
                    </button>
                    <input ref="fileInput" type="file" multiple aria-label="Upload files" class="hidden" @change="onFileChange" />
                </section>

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
                            <button
                                type="button"
                                aria-label="Delete folder"
                                class="rounded-lg p-1.5 transition hover:bg-ryh-soft"
                                @click="destroyFolder"
                            >
                                <Trash2 class="h-4 w-4 text-ryh-ink3" />
                            </button>
                        </div>
                    </div>

                    <div v-if="shown.length" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="document in shown"
                            :key="document.id"
                            draggable="true"
                            class="rounded-[18px] border border-ryh-line bg-ryh-sunk p-4 transition hover:-translate-y-0.5 hover:shadow-ryh"
                            :style="{ opacity: draggingId === document.id ? 0.35 : 1 }"
                            @dragstart="startDrag(document.id)"
                            @dragend="draggingId = null"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="grid h-12 w-10 flex-none place-items-center rounded-lg bg-ryh-soft font-mono text-[10.5px] font-semibold uppercase"
                                >
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
                                <span
                                    v-for="tag in document.tags"
                                    :key="tag"
                                    class="rounded-lg bg-ryh-soft px-2.5 py-1 text-[11px] font-semibold text-ryh-ink2"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <div class="mt-3.5 flex items-center gap-2 border-t border-ryh-line pt-3">
                                <template v-if="document.addedBy">
                                    <span
                                        class="grid h-[22px] w-[22px] flex-none place-items-center rounded-lg text-[9px] font-extrabold text-[#0E1A2B]"
                                        :style="{ background: document.addedBy.colour }"
                                    >
                                        {{ document.addedBy.initials }}
                                    </span>
                                    <span class="text-[11.5px] text-ryh-ink3">Added by {{ document.addedBy.name }}</span>
                                </template>
                                <div class="ml-auto flex gap-1">
                                    <a
                                        :href="route('documents.download', { document: document.id })"
                                        title="Download"
                                        :aria-label="`Download ${document.name}`"
                                        class="grid h-[30px] w-[30px] place-items-center rounded-lg text-ryh-ink2 transition hover:bg-ryh-card"
                                    >
                                        <Download class="h-3.5 w-3.5" />
                                    </a>
                                    <button
                                        type="button"
                                        title="Delete"
                                        :aria-label="`Delete ${document.name}`"
                                        class="grid h-[30px] w-[30px] place-items-center rounded-lg text-ryh-ink3 transition hover:bg-ryh-card hover:text-ryh-coral"
                                        @click="destroyDocument(document)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-14 text-center">
                        <div class="text-[15px] font-bold">Nothing in here yet</div>
                        <div class="mt-1.5 text-[13px] text-ryh-ink3">Drop a file above, or drag one in from another folder.</div>
                    </div>
                </section>
            </div>

            <div v-else class="flex w-full min-w-0 flex-1 flex-col items-center justify-center gap-3 py-24 text-center">
                <div class="text-[15px] font-bold">No folders yet</div>
                <div class="text-[13px] text-ryh-ink3">Create your first folder to start uploading documents.</div>
            </div>
        </div>

        <ConfirmDialog
            :open="confirmFolderOpen"
            :message="`Delete “${active?.name}”? All its documents will be removed too.`"
            @update:open="confirmFolderOpen = $event"
            @confirm="confirmDestroyFolder"
        />

        <ConfirmDialog
            :open="confirmDocOpen"
            :message="`Delete “${deletingDocument?.name}”?`"
            @update:open="confirmDocOpen = $event"
            @confirm="confirmDestroyDocument"
        />
    </RyhLayout>
</template>
