<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\MoneyHelper;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/** An input's pounds, as typed into a form ("12.5"), to whole pence. */
class PoundsToPenceCast implements Cast
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  CreationContext<BaseData<object, mixed, array-key>>  $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): int
    {
        return MoneyHelper::toPence($value);
    }
}
