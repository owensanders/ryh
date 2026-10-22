<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Chores\NotifyChoresAction;
use App\Enums\ChoreStatusEnum;
use App\Enums\HouseholdRoleEnum;
use App\Exceptions\ChoreReminderCooldownException;
use App\Models\Chore;
use App\Models\Household;
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

        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id, 'status' => ChoreStatusEnum::Today]);
        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id, 'status' => ChoreStatusEnum::Done]);
        Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => null, 'status' => ChoreStatusEnum::Today]);

        $this->actingAs($owner)->post($this->hh('/chores/notify'))->assertRedirect();

        Notification::assertSentTo($member, ChoresReminderNotification::class);
        $this->assertNotNull($owner->lastHousehold->refresh()->last_chore_notification_sent_at);
    }

    #[Test]
    public function itRejectsAnImmediateSecondRequestButAllowsOneAfterTheCooldown(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $this->actingAs($owner)->post($this->hh('/chores/notify'))->assertRedirect();
        $this->actingAs($owner)->post($this->hh('/chores/notify'))->assertStatus(429);

        Carbon::setTestNow(now()->addMinutes(config('notifications.chore_reminder_cooldown_minutes') + 1));

        $this->actingAs($owner)->post($this->hh('/chores/notify'))->assertRedirect();

        Carbon::setTestNow();
    }

    #[Test]
    public function aSecondRequestFromTheAppStaysOnThePageWithAToast(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $this->actingAs($owner)->post($this->hh('/chores/notify'))->assertRedirect();

        $this->actingAs($owner)
            ->from($this->hh('/chores'))
            ->post($this->hh('/chores/notify'), [], ['X-Inertia' => 'true'])
            ->assertRedirect($this->hh('/chores'))
            ->assertSessionHas('toastError');
    }

    #[Test]
    public function itChecksTheCooldownAgainstTheHouseholdAsItIsNotAsItWasLoaded(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        Chore::factory()->create(['household_id' => $user->household_id, 'assigned_to' => $user->id]);
        $household = $user->lastHousehold()->firstOrFail();

        // Another request sent reminders after this one loaded the household.
        Household::where('id', $household->id)->update(['last_chore_notification_sent_at' => now()]);

        try {
            app(NotifyChoresAction::class)->handle($household);
            $this->fail('The second press should have hit the cooldown.');
        } catch (ChoreReminderCooldownException) {
        }

        Notification::assertNothingSent();
    }

    #[Test]
    public function listsThatShareAPositionKeepTheOrderTheyWereAddedIn(): void
    {
        $user = User::factory()->create();

        foreach (['shoppingLists', 'budgetCategories', 'incomeSources', 'documentFolders'] as $relation) {
            $orders = $user->lastHousehold()->firstOrFail()->{$relation}()->getQuery()->getQuery()->orders;

            $this->assertSame(['position', 'id'], array_column($orders ?? [], 'column'), $relation);
        }
    }

    #[Test]
    public function teenAndChildCannotUseIt(): void
    {
        $owner = User::factory()->create();
        $teen = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Teen]);
        $child = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Child]);

        $this->actingAs($teen)->post($this->hh('/chores/notify'))->assertForbidden();
        $this->actingAs($child)->post($this->hh('/chores/notify'))->assertForbidden();
    }
}
