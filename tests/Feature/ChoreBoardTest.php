<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChoreBoardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRendersAColumnPerStatusAndAScorePerMember(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $user->household_id]);

        Chore::factory()->count(2)->create([
            'household_id' => $user->household_id,
            'assigned_to' => $partner->id,
            'status' => ChoreStatusEnum::Done,
        ]);
        Chore::factory()->create([
            'household_id' => $user->household_id,
            'assigned_to' => $partner->id,
            'status' => ChoreStatusEnum::Today,
        ]);

        $this->actingAs($user)
            ->get($this->hh('/chores'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Chores')
                ->count('columns', 2)
                ->where('columns.0.status', 'today')
                ->where('columns.0.count', 1)
                ->where('columns.1.status', 'done')
                ->where('columns.1.count', 2)
                ->count('scores', 2)
                ->where('scores.1.done', 2)
                ->where('scores.1.total', 3)
                ->where('scores.1.percentage', 67)
            );
    }

    #[Test]
    public function aPendingMemberDoesNotAppearInTheScoresAndCannotBeAssignedAChore(): void
    {
        $user = User::factory()->create();
        $pending = User::factory()->create(['household_id' => $user->household_id, 'name' => 'Margaret Parker', 'pending' => true]);

        $this->actingAs($user)
            ->get($this->hh('/chores'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->count('scores', 1));

        $this->actingAs($user)
            ->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $pending->id])
            ->assertSessionHasErrors('assigned_to');
    }

    #[Test]
    public function itRejectsAssigningAChoreToAMemberOfAnotherHousehold(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $stranger->id])
            ->assertSessionHasErrors('assigned_to');
    }

    #[Test]
    public function tickingAChoreMarksItDoneAndUntickingSendsItBackToToday(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create([
            'household_id' => $user->household_id,
            'status' => ChoreStatusEnum::Today,
        ]);

        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/toggle"))->assertRedirect();
        $this->assertSame(ChoreStatusEnum::Done, $chore->refresh()->status);

        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/toggle"))->assertRedirect();
        $this->assertSame(ChoreStatusEnum::Today, $chore->refresh()->status);
    }

    #[Test]
    public function itMovesAChoreToAnotherColumn(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->patch($this->hh("/chores/{$chore->id}/move"), ['status' => 'done'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Moved to Completed');

        $this->assertSame(ChoreStatusEnum::Done, $chore->refresh()->status);
    }

    #[Test]
    public function itRejectsAnUnknownColumn(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->patch($this->hh("/chores/{$chore->id}/move"), ['status' => 'someday'])
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function itDoesNotTouchAnotherHouseholdsChores(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/toggle"))->assertNotFound();
        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}/move"), ['status' => 'done'])->assertNotFound();

        $this->assertSame(ChoreStatusEnum::Today, $chore->refresh()->status);
    }

    #[Test]
    public function itCreatesAChore(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/chores'), ['name' => 'Clean the gutters'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Clean the gutters added');

        $this->assertDatabaseHas('chores', [
            'household_id' => $user->household_id,
            'name' => 'Clean the gutters',
            'status' => 'today',
        ]);
    }

    #[Test]
    public function itUpdatesAChore(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $user->household_id, 'name' => 'Water the plants']);

        $this->actingAs($user)
            ->patch($this->hh("/chores/{$chore->id}"), ['name' => 'Water the houseplants'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Water the houseplants updated');

        $this->assertSame('Water the houseplants', $chore->refresh()->name);
    }

    #[Test]
    public function editingADoneChoreDoesNotUncompleteIt(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $user->household_id, 'status' => ChoreStatusEnum::Done]);

        $this->actingAs($user)->patch($this->hh("/chores/{$chore->id}"), ['name' => $chore->name]);

        $this->assertSame(ChoreStatusEnum::Done, $chore->refresh()->status);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsChore(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $stranger->household_id, 'name' => 'Water the plants']);

        $this->actingAs($user)
            ->patch($this->hh("/chores/{$chore->id}"), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Water the plants', $chore->refresh()->name);
    }

    #[Test]
    public function itDeletesAChore(): void
    {
        $user = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $user->household_id, 'name' => 'Take out the bins']);

        $this->actingAs($user)
            ->delete($this->hh("/chores/{$chore->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast', 'Take out the bins deleted');

        $this->assertDatabaseMissing('chores', ['id' => $chore->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsChore(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)
            ->delete($this->hh("/chores/{$chore->id}"))
            ->assertNotFound();

        $this->assertDatabaseHas('chores', ['id' => $chore->id]);
    }
}
