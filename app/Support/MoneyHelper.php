<?php

declare(strict_types=1);

namespace App\Support;

/** Pence in, "£1,284" out. Whole pounds only — the design never shows pence. */
final class MoneyHelper
{
    public static function format(int $pence): string
    {
        return '£'.number_format(intdiv($pence, 100));
    }
}
