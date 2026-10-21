<?php

declare(strict_types=1);

namespace App\Support;

/** Pence in, "£1,284" out. Whole pounds only — the design never shows pence. */
final class MoneyHelper
{
    /** Pounds as typed into a form ("12.5") to whole pence. */
    public static function toPence(mixed $pounds): int
    {
        return (int) round(((float) $pounds) * 100);
    }

    public static function format(int $pence): string
    {
        return '£'.number_format(intdiv($pence, 100));
    }
}
