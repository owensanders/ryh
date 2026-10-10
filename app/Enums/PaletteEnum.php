<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Accent tokens from the RYH design. The value is the CSS custom property
 * name so the client can render `var(--mint)` and stay theme-aware.
 */
enum PaletteEnum: string
{
    case Coral = 'coral';
    case Mint = 'mint';
    case Lilac = 'lilac';
    case Sun = 'sun';
    case Sky = 'sky';

    public function cssVar(): string
    {
        return "var(--ryh-{$this->value})";
    }
}
