<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventDeletedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalendarNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creatingAnEventNotifiesAttendeesButNotTheCreator(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $attendee = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->post('/calendar/events', [
            'title' => 'Football practice',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(18, 0)->format('Y-m-d\TH:i'),
            'attendees' => [$owner->id, $attendee->id],
        ]);

        Notification::assertSentTo($attendee, CalendarEventAssignedNotification::class);
        Notification::assertNotSentTo($owner, CalendarEventAssignedNotification::class);
    }

    #[Test]
    public function creatingAWholeHouseEventNotifiesNobody(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/calendar/events', [
            'title' => 'Family dinner',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(18, 0)->format('Y-m-d\TH:i'),
        ]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function editingAnEventNotifiesExistingAttendeesAndAssignsNewOnes(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $existing = User::factory()->create(['household_id' => $owner->household_id]);
        $newAttendee = User::factory()->create(['household_id' => $owner->household_id]);
        $event = CalendarEvent::factory()->create(['household_id' => $owner->household_id, 'created_by' => $owner->id]);
        $event->attendees()->sync([$existing->id]);

        $this->actingAs($owner)->patch("/calendar/events/{$event->id}", [
            'title' => 'Football practice — moved',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(18, 0)->format('Y-m-d\TH:i'),
            'attendees' => [$existing->id, $newAttendee->id],
        ]);

        Notification::assertSentTo($existing, CalendarEventUpdatedNotification::class);
        Notification::assertSentTo($newAttendee, CalendarEventAssignedNotification::class);
        Notification::assertNotSentTo($newAttendee, CalendarEventUpdatedNotification::class);
        Notification::assertNotSentTo($owner, CalendarEventUpdatedNotification::class);
    }

    #[Test]
    public function editingAnEventNotifiesTheCreatorWhenSomeoneElseEditsIt(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $actor = User::factory()->create(['household_id' => $creator->household_id]);
        $event = CalendarEvent::factory()->create(['household_id' => $creator->household_id, 'created_by' => $creator->id]);

        $this->actingAs($actor)->patch("/calendar/events/{$event->id}", [
            'title' => 'Football practice — moved',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(18, 0)->format('Y-m-d\TH:i'),
        ]);

        Notification::assertSentTo($creator, CalendarEventUpdatedNotification::class);
        Notification::assertNotSentTo($actor, CalendarEventUpdatedNotification::class);
    }

    #[Test]
    public function deletingAnEventNotifiesItsAttendeesAndCreator(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $attendee = User::factory()->create(['household_id' => $creator->household_id]);
        $actor = User::factory()->create(['household_id' => $creator->household_id]);
        $event = CalendarEvent::factory()->create(['household_id' => $creator->household_id, 'created_by' => $creator->id]);
        $event->attendees()->sync([$attendee->id]);

        $this->actingAs($actor)->delete("/calendar/events/{$event->id}");

        Notification::assertSentTo($attendee, CalendarEventDeletedNotification::class);
        Notification::assertSentTo($creator, CalendarEventDeletedNotification::class);
        Notification::assertNotSentTo($actor, CalendarEventDeletedNotification::class);
    }
}
