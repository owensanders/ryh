<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import type { DocumentFolderData } from '@/types/generated';
import { router } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { ref } from 'vue';

/** Drop files here, or choose them, to upload into the open folder. */
const props = defineProps<{ active: DocumentFolderData }>();

const zoneHot = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
// Why files in the last batch didn't upload. Each upload is its own visit, so the page's errors only hold the last one's.
const failures = ref<string[]>([]);

function chooseFiles(): void {
    fileInput.value?.click();
}

// Inertia only allows one in-flight visit at a time — a new one aborts
// whatever's still running — so multiple files have to upload one after
// another, not all at once.
async function uploadFiles(files: FileList | null): Promise<void> {
    if (files === null) {
        return;
    }

    const folderId = props.active.id;
    failures.value = [];

    for (const file of Array.from(files)) {
        await new Promise<void>((resolve) => {
            router.post(
                route('documents.store', { folder: folderId }),
                { file },
                {
                    preserveScroll: true,
                    forceFormData: true,
                    onError: (errors) => failures.value.push(errors.file ?? `“${file.name}” didn't upload.`),
                    onFinish: () => resolve(),
                },
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
</script>

<template>
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
        <Button class="w-full flex-none sm:w-auto" @click="chooseFiles">Choose files</Button>
        <input ref="fileInput" type="file" multiple aria-label="Upload files" class="hidden" @change="onFileChange" />
    </section>

    <div v-if="failures.length" role="alert" class="flex flex-col gap-1 text-[13px] text-ryh-coral">
        <p v-for="(failure, index) in failures" :key="index">{{ failure }}</p>
    </div>
</template>
