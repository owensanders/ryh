<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\DocumentFolder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentFolderAction
{
    /** Its documents' rows cascade with it; their files go once that's committed. */
    public function handle(DocumentFolder $folder): void
    {
        $paths = $folder->documents()->pluck('path')->all();

        $folder->delete();

        if ($paths !== []) {
            DB::afterCommit(fn () => Storage::disk(config('documents.disk'))->delete($paths));
        }
    }
}
