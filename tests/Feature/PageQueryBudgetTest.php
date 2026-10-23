<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Http\Middleware\HandleInertiaRequestsMiddleware;
use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\Household;
use App\Models\IncomeSource;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Each page runs a fixed number of queries, however much the household has:
 * a page that runs more for a bigger household is loading something per
 * row (an N+1). And each stays within its budget, so a new query on a page
 * is a decision, not an accident. Raise a budget only for a query the page
 * needs.
 */
class PageQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private int $queries = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::listen(function (): void {
            $this->queries++;
        });
    }

    /**
     * Each budget includes the four queries every household page runs: the
     * signed-in user's household, the one in the URL, the membership and
     * the subscription.
     *
     * @return array<string, array{string, int}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['/dashboard', 15],
            'calendar' => ['/calendar', 7],
            'chores' => ['/chores', 8],
            'meal planner' => ['/meals', 13],
            'recipes' => ['/recipes', 6],
            'shopping' => ['/shopping', 6],
            'budget' => ['/budget', 9],
            'documents' => ['/documents', 8],
            'house' => ['/house', 5],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function aPageRunsTheSameQueriesHoweverMuchTheHouseholdHas(string $path, int $budget): void
    {
        $small = $this->queriesFor($this->ownerOfHouseholdWith(2), $path);
        $large = $this->queriesFor($this->ownerOfHouseholdWith(8), $path);

        $this->assertSame($small, $large, "{$path} runs more queries for a bigger household, so something is loaded per row.");
        $this->assertLessThanOrEqual($budget, $large, "{$path} is over its query budget.");
    }

    #[Test]
    public function theMealPlannerLeavesTheRecipeListOutUntilTheDialogAsks(): void
    {
        $owner = $this->ownerOfHouseholdWith(3);

        $this->actingAs($owner)->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->missing('recipes')->has('days', 7));

        $this->actingAs($owner)->get($this->hh('/meals'), $this->partial('MealPlanner', 'recipes'))
            ->assertOk()
            ->assertJsonCount(3, 'props.recipes');
    }

    #[Test]
    public function theAiPlansPollRunsOnlyWhatItAsksFor(): void
    {
        $owner = $this->ownerOfHouseholdWith(8);

        // Every page's four, then the AI plan: not the week, recipes, members, budget or lists.
        $this->assertSame(5, $this->queriesFor($owner, '/meals', $this->partial('MealPlanner', 'aiPlan')));
    }

    #[Test]
    public function sendingIngredientsToAListReloadsOnlyTheLists(): void
    {
        $owner = $this->ownerOfHouseholdWith(8);

        // Every page's four, then the lists with their counts: not the recipes.
        $this->assertSame(5, $this->queriesFor($owner, '/recipes', $this->partial('Recipes', 'shoppingLists')));
    }

    /**
     * How many queries a visit runs, after one visit to settle anything a
     * first visit does once (remembering the household it was in, say).
     *
     * @param  array<string, string>  $headers
     */
    private function queriesFor(User $owner, string $path, array $headers = []): int
    {
        $this->actingAs($owner)->get($this->hh($path), $headers)->assertOk();

        $this->queries = 0;
        $this->actingAs($owner)->get($this->hh($path), $headers)->assertOk();

        return $this->queries;
    }

    /** @return array<string, string> */
    private function partial(string $component, string $props): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequestsMiddleware::class)->version(Request::create('/')),
            'X-Inertia-Partial-Component' => $component,
            'X-Inertia-Partial-Data' => $props,
        ];
    }

    /** A household with `$size` of everything: members, chores, events, recipes, meals, lists of items, budget lines, folders of documents. */
    private function ownerOfHouseholdWith(int $size): User
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $household = Household::findOrFail($owner->last_household_id);
        $today = CarbonImmutable::today('Europe/London');

        $members = collect(range(1, $size))
            ->map(fn () => User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult]))
            ->push($owner);

        foreach (range(1, $size) as $n) {
            $member = $members[$n % $members->count()];

            Chore::factory()->create(['household_id' => $household->id, 'assigned_to' => $member->id, 'position' => $n]);

            CalendarEvent::factory()->create([
                'household_id' => $household->id,
                'starts_at' => $today->setTime(9 + $n, 0),
            ])->attendees()->sync([$member->id]);

            $recipe = Recipe::factory()->create([
                'household_id' => $household->id,
                'is_favourite' => true,
                'ingredients' => [['name' => 'Eggs', 'quantity' => '2']],
            ]);
            PlannedMeal::factory()->create([
                'household_id' => $household->id,
                'recipe_id' => $recipe->id,
                'cook_id' => $member->id,
                'planned_on' => $today->toDateString(),
            ]);

            $list = ShoppingList::factory()->create(['household_id' => $household->id, 'position' => $n]);
            ShoppingItem::factory()->count($size)->create(['shopping_list_id' => $list->id]);

            BudgetCategory::factory()->create(['household_id' => $household->id, 'month' => $today->startOfMonth(), 'position' => $n]);
            IncomeSource::factory()->create(['household_id' => $household->id, 'month' => $today->startOfMonth(), 'position' => $n]);

            $folder = $household->documentFolders()->create(['name' => "Folder {$n}", 'icon' => 'folder', 'colour' => 'mint', 'position' => $n]);

            foreach (range(1, $size) as $d) {
                $household->documents()->create([
                    'document_folder_id' => $folder->id,
                    'added_by' => $member->id,
                    'name' => "Document {$n}-{$d}",
                    'path' => "documents/{$n}-{$d}.pdf",
                    'extension' => 'pdf',
                    'size' => 1024,
                    'tags' => [],
                ]);
            }
        }

        return $owner;
    }
}
