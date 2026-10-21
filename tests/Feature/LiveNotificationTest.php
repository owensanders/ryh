<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Events\UserNotificationBroadcastEvent;
use App\Models\Chore;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LiveNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** Points broadcasting at a Reverb that isn't there. */
    private function useUnreachableReverb(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'key',
            'broadcasting.connections.reverb.secret' => 'secret',
            'broadcasting.connections.reverb.app_id' => '1',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 1,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
    }

    #[Test]
    public function theLiveUpdateGoesToTheRecipientsOwnChannelWithTheBellPayload(): void
    {
        Event::fake([UserNotificationBroadcastEvent::class]);

        $owner = User::factory()->create(['name' => 'Sarah Parker']);
        $partner = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $partner->id]);

        $stored = $partner->notifications()->sole();

        Event::assertDispatchedTimes(UserNotificationBroadcastEvent::class, 1);
        Event::assertDispatched(UserNotificationBroadcastEvent::class, function (UserNotificationBroadcastEvent $event) use ($partner, $stored): bool {
            return $event->broadcastOn()[0]->name === "private-App.Models.User.{$partner->id}"
                && $event->broadcastWith()['id'] === $stored->id
                && $event->broadcastWith()['type'] === 'chore_assigned'
                && $event->broadcastWith()['body'] === 'Sarah Parker assigned you "Clean the gutters"';
        });
    }

    #[Test]
    public function aDownRealtimeServerDoesNotFailTheChangeThatTriggeredTheNotification(): void
    {
        $this->useUnreachableReverb();
        Queue::fake();

        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $chore = Chore::factory()->create([
            'household_id' => $owner->household_id,
            'assigned_to' => $partner->id,
            'status' => ChoreStatusEnum::Today,
        ]);

        $this->actingAs($owner)->patch($this->hh("/chores/{$chore->id}/toggle"))->assertRedirect();

        $this->assertSame(ChoreStatusEnum::Done, $chore->fresh()?->status);
        $this->assertCount(1, $partner->notifications);
        Queue::assertPushed(BroadcastEvent::class, fn (BroadcastEvent $job) => $job->event instanceof UserNotificationBroadcastEvent);
    }

    #[Test]
    public function theLiveUpdateForADeletedChoreStillSendsOnceTheChoreIsGone(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $partner->id]);

        $this->actingAs($owner)->delete($this->hh("/chores/{$chore->id}"));

        // The queued job must not need the chore back from the database.
        Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job): bool {
            $restored = unserialize(serialize($job));

            return $restored->event instanceof UserNotificationBroadcastEvent
                && str_contains((string) $restored->event->broadcastWith()['body'], 'deleted');
        });
    }

    #[Test]
    public function peopleCanOnlyListenOnTheirOwnChannel(): void
    {
        $this->useUnreachableReverb();
        // Channels register on the broadcaster built at boot; give the Reverb one them too.
        require base_path('routes/channels.php');

        $sarah = User::factory()->create();
        $james = User::factory()->create(['household_id' => $sarah->household_id]);

        $this->actingAs($sarah)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$sarah->id}"])
            ->assertOk();

        $this->actingAs($sarah)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$james->id}"])
            ->assertForbidden();
    }
}
