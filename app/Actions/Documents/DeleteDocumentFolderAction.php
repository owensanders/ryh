<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\DocumentFolder;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentFolderAction
{
    public function handle(DocumentFolder $folder): void
    {
        $paths = $folder->documents()->pluck('path')->all();

        if ($paths !== []) {
            Storage::disk(config('documents.disk'))->delete($paths);
        }

        $folder->delete();
    }
}
