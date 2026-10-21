<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentAction
{
    public function handle(Document $document): void
    {
        Storage::disk(config('documents.disk'))->delete($document->path);

        $document->delete();
    }
}
