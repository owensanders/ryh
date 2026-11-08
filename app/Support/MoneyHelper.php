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

    /** The sign goes before the pound sign: -215000 is "-£2,150", not "£-2,150". */
    public static function format(int $pence): string
    {
        $pounds = intdiv($pence, 100);

        return ($pounds < 0 ? '-' : '').'£'.number_format(abs($pounds));
    }
}
