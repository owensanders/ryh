<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\DocumentFolder;
use App\Repositories\DocumentRepository;

class DeleteDocumentFolderAction
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function handle(DocumentFolder $folder): void
    {
        $this->documents->deleteFolder($folder);
    }
}
