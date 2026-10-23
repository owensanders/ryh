<?php

declare(strict_types=1);

namespace App\Casts;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A datetime column that holds a UTC moment. Laravel's `datetime` cast writes
 * a Carbon out in its own zone, unconverted, so 18:00 in Auckland would be
 * stored as 18:00; this converts it to UTC first. A string is taken as UTC.
 *
 * @implements CastsAttributes<Carbon, DateTimeInterface|string>
 */
class UtcDateTimeCast implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return is_string($value) ? Carbon::parse($value, 'UTC') : null;
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $moment = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse((string) $value, 'UTC');

        return $moment->utc()->format('Y-m-d H:i:s');
    }
}
