import { router } from '@inertiajs/vue3';

/**
 * Moves a document into another folder. Dragging a card onto a folder and
 * the card's "Move to" menu (for keyboard and touch) both come here.
 */
export function moveDocument(documentId: number, folderId: number): void {
    router.patch(route('documents.move', { document: documentId }), { folder_id: folderId }, { preserveScroll: true });
}
