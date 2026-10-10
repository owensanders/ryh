<?php

declare(strict_types=1);

namespace App\Enums;

/** Why a household member row is still `pending`, so the House page can tell the two apart. */
enum PendingReasonEnum: string
{
    case Invited = 'invited';
    case Requested = 'requested';
}
