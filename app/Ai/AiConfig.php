<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Support\Facades\Cache;

class AiConfig
{
    private const PAUSED_KEY = 'ai:paused';

    private const ALERTED_KEY = 'ai:credits-alerted';

    /** True if the default AI provider has an API key set. */
    public static function isConfigured(): bool
    {
        return filled(config('ai.providers.'.config('ai.default').'.key'));
    }

    /** True while AI is paused app-wide, e.g. after the provider ran out of credit. */
    public static function isPaused(): bool
    {
        return Cache::has(self::PAUSED_KEY);
    }

    /**
     * Pause AI app-wide for the configured cool-off.
     *
     * Returns true only the first time it's called until resume(), so the
     * caller can alert once rather than on every failed request.
     */
    public static function pause(): bool
    {
        Cache::put(self::PAUSED_KEY, true, now()->addHours((int) config('meal_planner.pause_hours')));

        return Cache::add(self::ALERTED_KEY, true, now()->addDay());
    }

    public static function resume(): void
    {
        Cache::forget(self::PAUSED_KEY);
        Cache::forget(self::ALERTED_KEY);
    }
}
