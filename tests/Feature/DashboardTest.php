<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guestsAreRedirectedToTheLoginPage(): void
    {
        $this->get('/h/anyone/dashboard')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    #[Test]
    public function itRefusesUsersWhoAreNotInAHousehold(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.setup', absolute: false));
    }

    #[Test]
    public function aPendingMemberDoesNotAppearInTheFamilyStrip(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['household_id' => $user->household_id, 'name' => 'Margaret Parker', 'pending' => true]);

        $this->actingAs($user)
            ->get($this->hh('/dashboard'))
            ->assertOk()
            ->assertInertia(function ($page) {
                /** @var list<array<string, mixed>> $family */
                $family = $page->toArray()['props']['dashboard']['family'];

                $this->assertFalse(collect($family)->contains('name', 'Margaret Parker'));
            });
    }

    #[Test]
    public function itShowsTonightsDinnerTodaysChoresAndTheDefaultShoppingList(): void
    {
        $household = Household::create(['name' => 'The Parkers', 'location' => 'Bristol', 'streak_days' => 12, 'trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['household_id' => $household->id, 'name' => 'Sarah Parker']);

        $recipe = Recipe::factory()->create([
            'household_id' => $household->id,
            'name' => 'Lemon chicken traybake',
            'tags' => ['Healthy'],
        ]);
        PlannedMeal::factory()->create([
            'household_id' => $household->id,
            'recipe_id' => $recipe->id,
            'cook_id' => $user->id,
            'planned_on' => now()->toDateString(),
        ]);

        $list = ShoppingList::factory()->create(['household_id' => $household->id, 'name' => 'Tesco', 'slug' => 'tesco']);
        ShoppingItem::factory()->count(3)->create(['shopping_list_id' => $list->id]);
        ShoppingItem::factory()->done()->create(['shopping_list_id' => $list->id]);

        Chore::factory()->count(2)->create(['household_id' => $household->id, 'status' => ChoreStatusEnum::Today]);
        Chore::factory()->create(['household_id' => $household->id, 'status' => ChoreStatusEnum::Done]);

        $this->actingAs($user)
            ->get($this->hh('/dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('dashboard.greeting', fn (string $greeting) => str_contains($greeting, 'Sarah'))
                ->where('dashboard.streakDays', 12)
                ->where('dashboard.tonight.name', 'Lemon chicken traybake')
                ->where('dashboard.tonight.tags', ['Healthy'])
                ->where('dashboard.shoppingList.name', 'Tesco')
                ->where('dashboard.shoppingList.remaining', 3)
                ->where('dashboard.choreProgress.total', 3)
                ->where('dashboard.choreProgress.done', 1)
                ->where('dashboard.choreProgress.percentage', 33)
            );
    }

    #[Test]
    public function itSkipsAnEmptyFirstListInFavourOfOneWithItems(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id]);

        ShoppingList::factory()->create(['household_id' => $household->id, 'name' => 'Empty one', 'position' => 0]);
        $withItems = ShoppingList::factory()->create(['household_id' => $household->id, 'name' => 'Has stuff', 'position' => 1]);
        ShoppingItem::factory()->create(['shopping_list_id' => $withItems->id]);

        $this->actingAs($user)
            ->get($this->hh('/dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('dashboard.shoppingList.name', 'Has stuff'));
    }

    #[Test]
    public function itShowsTheEmptyStateWhenEveryListIsEmpty(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id]);

        ShoppingList::factory()->create(['household_id' => $household->id]);
        ShoppingList::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user)
            ->get($this->hh('/dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('dashboard.shoppingList', null));
    }

    #[Test]
    public function itLooksUpTheViewersMembershipOncePerRequest(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id]);

        DB::enableQueryLog();

        $this->actingAs($user)->get($this->hh('/dashboard'))->assertOk();

        $lookups = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_starts_with($query['query'], 'select * from `household_user` where `user_id`'));

        $this->assertCount(1, $lookups);
    }
}
