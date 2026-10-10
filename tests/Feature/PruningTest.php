<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatus;
use App\Models\Chore;
use App\Models\ShoppingItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tickingAChoreOffRecordsWhenAndUntickingClearsIt(): void
    {
        $owner = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $owner->id]);

        $this->assertNull($chore->completed_at);

        $this->actingAs($owner)->patch("/chores/{$chore->id}/toggle");
        $this->assertNotNull($chore->refresh()->completed_at);

        $this->actingAs($owner)->patch("/chores/{$chore->id}/toggle");
        $this->assertNull($chore->refresh()->completed_at);
    }

    #[Test]
    public function aChoreCreatedAlreadyCompletedIsStamped(): void
    {
        $chore = Chore::factory()->create(['status' => ChoreStatus::Done]);

        $this->assertNotNull($chore->completed_at);
    }

    #[Test]
    public function theNightlyPruneClearsOnlyChoresCompletedOverThirtyDaysAgo(): void
    {
        $this->travel(-31)->days();
        $old = Chore::factory()->create(['status' => ChoreStatus::Done]);
        $stillToDo = Chore::factory()->create(['status' => ChoreStatus::Today]);
        $this->travelBack();

        $recent = Chore::factory()->create(['status' => ChoreStatus::Done]);

        $this->artisan('model:prune', ['--model' => [Chore::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($stillToDo);
        $this->assertModelExists($recent);
    }

    #[Test]
    public function theNightlyPruneClearsOnlyShoppingItemsTickedOffOverAWeekAgo(): void
    {
        $old = ShoppingItem::factory()->create(['completed_at' => now()->subDays(8)]);
        $recent = ShoppingItem::factory()->create(['completed_at' => now()->subDays(2)]);
        $notBought = ShoppingItem::factory()->create(['completed_at' => null]);

        $this->artisan('model:prune', ['--model' => [ShoppingItem::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($notBought);
    }

    #[Test]
    public function theNightlyPruneClearsNotificationsOverNinetyDaysOld(): void
    {
        $user = User::factory()->create();

        foreach (['old' => now()->subDays(91), 'recent' => now()->subDays(5)] as $body => $createdAt) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'chore_assigned',
                'data' => ['body' => $body],
                'created_at' => $createdAt,
            ]);
        }

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertSame(['recent'], $user->notifications()->get()->pluck('data.body')->all());
    }
}
