<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes stored files no document row points to: an upload that died
 * between storing its file and saving its row, or a delete whose file
 * removal failed after commit. Only files older than `$olderThan` go, so an
 * upload still between those two steps is never caught mid-way.
 */
class PruneOrphanedDocumentFilesAction
{
    private const int CHUNK = 500;

    /** @return int How many files it deleted. */
    public function handle(CarbonInterface $olderThan): int
    {
        $disk = Storage::disk(config('documents.disk'));
        $deleted = 0;

        foreach (array_chunk($disk->allFiles('documents'), self::CHUNK) as $paths) {
            $known = Document::query()->whereIn('path', $paths)->pluck('path')->flip();

            foreach ($paths as $path) {
                if (! $known->has($path) && $disk->lastModified($path) < $olderThan->getTimestamp()) {
                    $disk->delete($path);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
