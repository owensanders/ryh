<?php

declare(strict_types=1);

namespace App\Enums;

/** Where an AI meal plan is: still being written in the background, ready, or failed. */
enum AiMealPlanStatusEnum: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
}
