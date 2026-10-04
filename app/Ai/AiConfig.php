<?php

declare(strict_types=1);

namespace App\Ai;

class AiConfig
{
    /** True if the default AI provider has an API key set. */
    public static function isConfigured(): bool
    {
        return filled(config('ai.providers.'.config('ai.default').'.key'));
    }
}
