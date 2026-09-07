<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatus;
use App\Enums\HouseholdRole;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\ChoresReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifyChoresTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itNotifiesEveryMemberWithAnOutstandingChoreAndStartsTheCooldown(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id, 'status' => ChoreStatus::Today]);
        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id, 'status' => ChoreStatus::Done]);
        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => null, 'status' => ChoreStatus::Today]);

        $this->actingAs($owner)->post('/chores/notify')->assertRedirect();

        Notification::assertSentTo($member, ChoresReminderNotification::class);
        $this->assertNotNull($owner->household->refresh()->last_chore_notification_sent_at);
    }

    #[Test]
    public function itRejectsAnImmediateSecondRequestButAllowsOneAfterTheCooldown(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/chores/notify')->assertRedirect();
        $this->actingAs($owner)->post('/chores/notify')->assertStatus(429);

        Carbon::setTestNow(now()->addMinutes(config('notifications.chore_reminder_cooldown_minutes') + 1));

        $this->actingAs($owner)->post('/chores/notify')->assertRedirect();

        Carbon::setTestNow();
    }

    #[Test]
    public function teenAndChildCannotUseIt(): void
    {
        $owner = User::factory()->create();
        $teen = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Teen]);
        $child = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Child]);

        $this->actingAs($teen)->post('/chores/notify')->assertForbidden();
        $this->actingAs($child)->post('/chores/notify')->assertForbidden();
    }
}
