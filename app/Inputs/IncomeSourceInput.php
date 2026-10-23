<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Casts\PoundsToPenceCast;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** An income source as IncomeSourceRequest validated it. */
#[MapInputName(SnakeCaseMapper::class)]
final class IncomeSourceInput extends Dto
{
    public function __construct(
        public readonly string $label,
        /** The form takes pounds. */
        #[MapInputName('amount'), WithCast(PoundsToPenceCast::class)]
        public readonly int $amountPence,
        public readonly ?string $meta = null,
        public readonly bool $isRecurring = false,
    ) {}
}
