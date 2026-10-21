<?php

declare(strict_types=1);

namespace App\UseCases\Documents;

use App\Models\DocumentFolder;
use App\Repositories\DocumentRepository;

class DeleteDocumentFolderUseCase
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function execute(DocumentFolder $folder): void
    {
        $this->documents->deleteFolder($folder);
    }
}
