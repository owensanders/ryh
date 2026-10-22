<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The cookies the app sets, read from the live session config so the Cookie Policy can't drift from it. */
class CookieSettingsData extends Data
{
    /** Laravel's "Remember me" cookie lasts 400 days (SessionGuard::$rememberDuration). */
    private const int REMEMBER_DAYS = 400;

    public function __construct(
        public string $sessionCookie,
        public int $sessionLifetimeMinutes,
        public bool $expireOnClose,
        public int $rememberDays,
    ) {}

    public static function current(): self
    {
        return new self(
            sessionCookie: config('session.cookie'),
            sessionLifetimeMinutes: (int) config('session.lifetime'),
            expireOnClose: (bool) config('session.expire_on_close'),
            rememberDays: self::REMEMBER_DAYS,
        );
    }
}
