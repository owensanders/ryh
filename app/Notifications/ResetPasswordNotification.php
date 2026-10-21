<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Traits\RetriesEmailTrait;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Laravel's password reset email, sent from the queue so a slow or down mail provider can't fail the request. */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;
    use RetriesEmailTrait;
}
