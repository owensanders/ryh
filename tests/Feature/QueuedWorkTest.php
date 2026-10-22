<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Chores\NotifyChoresAction;
use App\Ai\Agents\MealPlanAgent;
use App\Enums\AiMealPlanStatusEnum;
use App\Enums\HouseholdRoleEnum;
use App\Jobs\GenerateAiMealPlanJob;
use App\Models\AiMealPlan;
use App\Models\Chore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/** What has to happen on the queue stays there, and only once its transaction has committed. */
class QueuedWorkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anAiMealPlanIsQueuedNotWrittenDuringTheRequest(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        Queue::fake();
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable'])
            ->assertSessionHasNoErrors();

        $plan = AiMealPlan::query()->sole();
        Queue::assertPushed(GenerateAiMealPlanJob::class, fn (GenerateAiMealPlanJob $job) => $job->planId === $plan->id);
        $this->assertSame(AiMealPlanStatusEnum::Pending, $plan->status);
        $this->assertNull($user->lastHousehold?->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function theAiJobIsNeverRetriedAndFinishesBeforeItsPlanWouldCountAsStale(): void
    {
        $job = new GenerateAiMealPlanJob(1);

        // A retry could pay the provider twice.
        $this->assertSame(1, $job->tries);
        // Otherwise a plan still being written could be shown as failed.
        $this->assertLessThan(AiMealPlan::STALE_AFTER_SECONDS, $job->timeout);
    }

    #[Test]
    public function anActionThatRollsBackSendsNoLiveUpdatesOrPushes(): void
    {
        // The real queue, not Queue::fake(), which records jobs straight away and
        // so can't tell whether they waited for the commit. JobQueued fires as a
        // job is actually handed to the queue, outside the transaction, so a job
        // queued too early is still seen even though its row is rolled back.
        config(['queue.default' => 'database']);
        $queued = [];
        Event::listen(JobQueued::class, function (JobQueued $event) use (&$queued): void {
            $queued[] = $event->job;
        });
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $owner->updatePushSubscription('https://push.example.com/abc', 'public-key', 'auth-token', 'aes128gcm');
        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $owner->id]);

        try {
            DB::transaction(function () use ($owner): void {
                app(NotifyChoresAction::class)->handle($owner->lastHousehold()->firstOrFail());

                throw new RuntimeException('Something later in the same transaction failed.');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame([], $queued);
        $this->assertDatabaseCount('notifications', 0);

        // The same reminder, committed, does go out: its live update and its push.
        app(NotifyChoresAction::class)->handle($owner->lastHousehold()->firstOrFail());

        $this->assertDatabaseCount('notifications', 1);
        $this->assertCount(2, $queued);
    }
}
