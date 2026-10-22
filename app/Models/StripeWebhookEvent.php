<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A Stripe webhook event that's been handled, by its Stripe ID (evt_…).
 *
 * @property string $id
 * @property string $type
 * @property Carbon $created_at
 */
class StripeWebhookEvent extends Model
{
    use MassPrunable;

    /** Stripe stops retrying an event after three days; a month leaves room. */
    public const int KEEP_DAYS = 30;

    public $incrementing = false;

    protected $keyType = 'string';

    public const ?string UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['id', 'type'];

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}
