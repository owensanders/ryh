<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Exceptions\DocumentQuotaExceededException;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadDocumentAction
{
    /**
     * The file is stored first, outside the transaction, so a slow disk never
     * holds the household's lock. The quota is then checked under that lock, so
     * two uploads at once can't both fit into the last of the space; if either
     * that or the row fails, the stored file goes again.
     *
     * @throws DocumentQuotaExceededException
     */
    public function handle(DocumentFolder $folder, UploadedFile $file, ?User $uploader): Document
    {
        $disk = Storage::disk(config('documents.disk'));
        $path = $disk->putFile("documents/{$folder->household_id}", $file);

        if ($path === false) {
            throw new RuntimeException('The document disk refused the upload.');
        }

        try {
            return DB::transaction(function () use ($folder, $file, $uploader, $path): Document {
                $household = $folder->household()->firstOrFail()->lockRow();

                if ($household->documentBytes() + $file->getSize() > (int) config('documents.quota_bytes')) {
                    throw new DocumentQuotaExceededException($file->getClientOriginalName());
                }

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
            });
        } catch (Throwable $e) {
            $disk->delete($path);

            throw $e;
        }
    }
}
