<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class DownloadDocumentAction
{
    /** How long an S3 download link works for — long enough to start the download, short enough not to be worth sharing. */
    private const int DOWNLOAD_LINK_MINUTES = 5;

    public function handle(Document $document): Response
    {
        $disk = (string) config('documents.disk');

        // On S3 the browser fetches the file straight from the bucket through a
        // short-lived link, rather than a PHP worker relaying every byte.
        if (config("filesystems.disks.{$disk}.driver") === 's3') {
            return redirect()->away(Storage::disk($disk)->temporaryUrl(
                $document->path,
                now()->addMinutes(self::DOWNLOAD_LINK_MINUTES),
                ['ResponseContentDisposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $document->name, str_replace(['%', '/', '\\'], '', Str::ascii($document->name)))],
            ));
        }

        return Storage::disk($disk)->download($document->path, $document->name);
    }
}
