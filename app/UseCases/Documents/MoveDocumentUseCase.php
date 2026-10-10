<?php

declare(strict_types=1);

namespace App\UseCases\Documents;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Data\DocumentData;
use App\Data\DocumentMovedData;
use App\Models\Document;

class MoveDocumentUseCase
{
    public function __construct(private readonly DocumentRepositoryInterface $documents) {}

    public function execute(Document $document, int $folderId): DocumentMovedData
    {
        $folder = $this->documents->findFolder($folderId);

        // A document never leaves its household, so another household's folder doesn't exist here.
        abort_if($folder === null || $folder->household_id !== $document->household_id, 404);

        return new DocumentMovedData(
            DocumentData::fromModel($this->documents->moveDocument($document, $folder)),
            $folder->name,
        );
    }
}
