<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The document vault, opened on one folder. */
class DocumentsScreenData extends Data
{
    /**
     * @param  array<int, DocumentFolderData>  $folders
     * @param  array<int, DocumentData>  $documents
     */
    public function __construct(
        public array $folders,
        public ?DocumentFolderData $active,
        public array $documents,
        public string $storageLabel,
        public int $storagePct,
    ) {}
}
