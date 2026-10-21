<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Repositories\DocumentRepository;

class DeleteDocumentAction
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function handle(Document $document): void
    {
        $this->documents->deleteDocument($document);
    }
}
