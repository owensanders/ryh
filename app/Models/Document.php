<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $document_folder_id
 * @property int|null $added_by
 * @property string $name
 * @property string $path
 * @property string $extension
 * @property int $size
 * @property list<string>|null $tags
 * @property Carbon|null $expires_at
 */
class Document extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'household_id', 'document_folder_id', 'added_by',
        'name', 'path', 'extension', 'size', 'tags', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'tags' => 'array',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<DocumentFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'document_folder_id');
    }

    /** @return BelongsTo<User, $this> */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
