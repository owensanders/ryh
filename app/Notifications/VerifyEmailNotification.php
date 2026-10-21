<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Traits\RetriesEmailTrait;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Laravel's verification email, sent from the queue so a slow or down mail provider can't fail sign-up. */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;
    use RetriesEmailTrait;
}
