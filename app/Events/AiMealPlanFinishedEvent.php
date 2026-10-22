<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AiMealPlan;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/** Tells whoever asked for an AI meal plan that it's ready, or failed, so their page can fetch it. */
class AiMealPlanFinishedEvent implements ShouldBroadcast, ShouldDispatchAfterCommit, ShouldRescue
{
    public function __construct(
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $status,
    ) {}

    /** Nobody to tell once the person who asked has deleted their account. */
    public static function dispatchFor(AiMealPlan $plan): void
    {
        if ($plan->requested_by !== null) {
            event(new self($plan->requested_by, $plan->id, $plan->status->value));
        }
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'AiMealPlanFinished';
    }

    /** @return array{id: int, status: string} */
    public function broadcastWith(): array
    {
        return ['id' => $this->planId, 'status' => $this->status];
    }
}
