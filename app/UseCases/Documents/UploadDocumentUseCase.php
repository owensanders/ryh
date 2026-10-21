<?php

declare(strict_types=1);

namespace App\UseCases\Documents;

use App\Data\DocumentData;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;

class UploadDocumentUseCase
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function execute(DocumentFolder $folder, UploadedFile $file, ?User $uploader): DocumentData
    {
        return DocumentData::fromModel($this->documents->storeUpload($folder, $file, $uploader));
    }
}
