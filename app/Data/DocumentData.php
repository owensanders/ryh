<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Document;
use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    /** @param array<int, string> $tags */
    public function __construct(
        public int $id,
        public string $name,
        public string $extension,
        public string $meta,
        public array $tags,
        public ?string $expiryLabel,
        public bool $isUrgent,
        public ?MemberData $addedBy,
    ) {}

    public static function fromModel(Document $document): self
    {
        $expiresAt = $document->expires_at;

        return new self(
            id: $document->id,
            name: $document->name,
            extension: $document->extension,
            meta: number_format($document->size / 1024 / 1024, 1).' MB · '.$document->created_at?->format('d M Y'),
            tags: $document->tags ?? [],
            expiryLabel: $expiresAt?->format('d M Y'),
            isUrgent: $expiresAt !== null && $expiresAt->lessThanOrEqualTo(now()->addDays(60)),
            addedBy: $document->addedBy !== null ? MemberData::fromModel($document->addedBy) : null,
        );
    }
}
