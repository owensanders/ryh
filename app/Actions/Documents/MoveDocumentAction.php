<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Data\DocumentData;
use App\Data\DocumentMovedData;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Repositories\DocumentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MoveDocumentAction
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function handle(Document $document, int $folderId): DocumentMovedData
    {
        $folder = $this->documents->findFolder($folderId);

        // A document never leaves its household, so another household's folder doesn't exist here.
        if ($folder === null || $folder->household_id !== $document->household_id) {
            throw (new ModelNotFoundException())->setModel(DocumentFolder::class, [$folderId]);
        }

        return new DocumentMovedData(
            DocumentData::fromModel($this->documents->moveDocument($document, $folder)),
            $folder->name,
        );
    }
}
