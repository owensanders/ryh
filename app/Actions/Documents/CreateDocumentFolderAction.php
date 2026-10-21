<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Data\DocumentFolderData;
use App\Models\Household;
use App\Repositories\DocumentRepository;

class CreateDocumentFolderAction
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function handle(Household $household, string $name): DocumentFolderData
    {
        return DocumentFolderData::fromModel($this->documents->createFolder($household, $name), 0);
    }
}
