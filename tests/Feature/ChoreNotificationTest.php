<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Enums\HouseholdRoleEnum;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\ChoreActionedNotification;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreDeletedNotification;
use App\Notifications\ChoreEditedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChoreNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creatingAChoreAssignedToSomeoneElseNotifiesThem(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $partner->id]);

        Notification::assertSentTo($partner, ChoreAssignedNotification::class);
        Notification::assertNotSentTo($owner, ChoreAssignedNotification::class);
    }

    #[Test]
    public function creatingAChoreAssignedToYourselfDoesNotNotifyYou(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $this->actingAs($owner)->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $owner->id]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function reassigningAChoreNotifiesTheNewAssigneeOnly(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $first = User::factory()->create(['household_id' => $owner->household_id]);
        $second = User::factory()->create(['household_id' => $owner->household_id]);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $first->id]);

        $this->actingAs($owner)->patch($this->hh("/chores/{$chore->id}"), ['name' => $chore->name, 'assigned_to' => $second->id]);

        Notification::assertSentTo($second, ChoreAssignedNotification::class);
        Notification::assertNotSentTo($first, ChoreAssignedNotification::class);
    }

    #[Test]
    public function togglingSomeoneElsesChoreNotifiesTheAssigneeAndCreatorButNotTheActor(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $assignee = User::factory()->create(['household_id' => $creator->household_id]);
        $actor = User::factory()->create(['household_id' => $creator->household_id, 'role' => HouseholdRoleEnum::Adult]);
        $chore = Chore::factory()->create([
            'household_id' => $creator->household_id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
            'status' => ChoreStatusEnum::Today,
        ]);

        $this->actingAs($actor)->patch($this->hh("/chores/{$chore->id}/toggle"));

        Notification::assertSentTo($assignee, ChoreActionedNotification::class);
        Notification::assertSentTo($creator, ChoreActionedNotification::class);
        Notification::assertNotSentTo($actor, ChoreActionedNotification::class);
    }

    #[Test]
    public function togglingYourOwnChoreDoesNotNotifyYou(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $chore = Chore::factory()->create([
            'household_id' => $owner->household_id,
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'status' => ChoreStatusEnum::Today,
        ]);

        $this->actingAs($owner)->patch($this->hh("/chores/{$chore->id}/toggle"));

        Notification::assertNothingSent();
    }

    #[Test]
    public function editingSomeoneElsesChoreWithoutReassigningItNotifiesTheAssigneeAndCreator(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $assignee = User::factory()->create(['household_id' => $creator->household_id]);
        $actor = User::factory()->create(['household_id' => $creator->household_id, 'role' => HouseholdRoleEnum::Adult]);
        $chore = Chore::factory()->create([
            'household_id' => $creator->household_id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($actor)->patch($this->hh("/chores/{$chore->id}"), ['name' => 'Clean the gutters', 'assigned_to' => $assignee->id]);

        Notification::assertSentTo($assignee, ChoreEditedNotification::class);
        Notification::assertSentTo($creator, ChoreEditedNotification::class);
        Notification::assertNotSentTo($actor, ChoreEditedNotification::class);
        Notification::assertNotSentTo($assignee, ChoreAssignedNotification::class);
    }

    #[Test]
    public function reassigningAChoreDoesNotAlsoSendAnEditedNotification(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $first = User::factory()->create(['household_id' => $owner->household_id]);
        $second = User::factory()->create(['household_id' => $owner->household_id]);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $first->id]);

        $this->actingAs($owner)->patch($this->hh("/chores/{$chore->id}"), ['name' => $chore->name, 'assigned_to' => $second->id]);

        Notification::assertNotSentTo($second, ChoreEditedNotification::class);
        Notification::assertNotSentTo($first, ChoreEditedNotification::class);
    }

    #[Test]
    public function deletingSomeoneElsesChoreNotifiesTheAssigneeAndCreator(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $assignee = User::factory()->create(['household_id' => $creator->household_id]);
        $actor = User::factory()->create(['household_id' => $creator->household_id, 'role' => HouseholdRoleEnum::Adult]);
        $chore = Chore::factory()->create([
            'household_id' => $creator->household_id,
            'assigned_to' => $assignee->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($actor)->delete($this->hh("/chores/{$chore->id}"));

        Notification::assertSentTo($assignee, ChoreDeletedNotification::class);
        Notification::assertSentTo($creator, ChoreDeletedNotification::class);
        Notification::assertNotSentTo($actor, ChoreDeletedNotification::class);
    }

    #[Test]
    public function deletingYourOwnChoreDoesNotNotifyYou(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $chore = Chore::factory()->create([
            'household_id' => $owner->household_id,
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->delete($this->hh("/chores/{$chore->id}"));

        Notification::assertNothingSent();
    }
}
