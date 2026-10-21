<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Data\DocumentData;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;

class UploadDocumentAction
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function handle(DocumentFolder $folder, UploadedFile $file, ?User $uploader): DocumentData
    {
        return DocumentData::fromModel($this->documents->storeUpload($folder, $file, $uploader));
    }
}
