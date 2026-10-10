<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** A document after a move, and the folder it went to. */
class DocumentMovedData extends Data
{
    public function __construct(
        public DocumentData $document,
        public string $folderName,
    ) {}
}
