<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/** Checkout couldn't be started: billing isn't configured, or Stripe didn't answer. */
final class BillingUnavailableException extends UserFacingException
{
    public static function notConfigured(): self
    {
        return new self('Billing is not set up yet.');
    }

    public static function providerUnreachable(Throwable $previous): self
    {
        return new self("We couldn't reach our payment provider. Please try again in a moment.", previous: $previous);
    }

    public function errorKey(): string
    {
        return 'plan';
    }
}
