<?php

declare(strict_types=1);

namespace App\UseCases\Documents;

use App\Models\Document;
use App\Repositories\DocumentRepository;

class DeleteDocumentUseCase
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function execute(Document $document): void
    {
        $this->documents->deleteDocument($document);
    }
}
