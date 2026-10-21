<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentFolder;

class MoveDocumentAction
{
    /** @return DocumentFolder The folder it's now in. */
    public function handle(Document $document, int $folderId): DocumentFolder
    {
        // A document never leaves its household, so another household's folder doesn't exist here.
        $folder = DocumentFolder::query()->where('household_id', $document->household_id)->findOrFail($folderId);

        $document->update(['document_folder_id' => $folder->id]);

        return $folder;
    }
}
