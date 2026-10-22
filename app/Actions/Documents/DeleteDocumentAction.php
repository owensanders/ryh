<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentAction
{
    /** The row goes first; the file only once that's committed, so a failed delete never leaves a row with no file. */
    public function handle(Document $document): void
    {
        $path = $document->path;

        $document->delete();

        DB::afterCommit(fn () => Storage::disk(config('documents.disk'))->delete($path));
    }
}
