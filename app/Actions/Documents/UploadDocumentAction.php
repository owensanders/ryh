<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadDocumentAction
{
    public function handle(DocumentFolder $folder, UploadedFile $file, ?User $uploader): Document
    {
        $path = Storage::disk(config('documents.disk'))->putFile("documents/{$folder->household_id}", $file);

        return $folder->documents()->create([
            'household_id' => $folder->household_id,
            'added_by' => $uploader?->id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            // Truncated defensively: mimes: validates content, not filename shape,
            // so an unusually long suffix (e.g. "report.pdfbackupcopy") could
            // otherwise overflow the column.
            'extension' => mb_substr($file->getClientOriginalExtension(), 0, 20),
            'size' => $file->getSize(),
        ]);
    }
}
