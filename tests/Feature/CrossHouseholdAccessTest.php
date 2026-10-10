<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\DocumentFolder;
use App\Models\IncomeSource;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Every policy answers 404 for another household's records, so an owner can't even confirm they exist. */
class CrossHouseholdAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{Closure(): string, string}> Something in another household => the request to try on it. */
    public static function strangersRecords(): array
    {
        return [
            'chore' => [fn () => '/chores/'.Chore::factory()->create()->id, 'patch'],
            'chore toggle' => [fn () => '/chores/'.Chore::factory()->create()->id.'/toggle', 'patch'],
            'calendar event' => [fn () => '/calendar/events/'.CalendarEvent::factory()->create()->id, 'delete'],
            'planned meal' => [fn () => '/meals/'.PlannedMeal::factory()->create()->id, 'delete'],
            'recipe' => [fn () => '/recipes/'.Recipe::factory()->create()->id, 'delete'],
            'shopping list' => [fn () => '/shopping-lists/'.ShoppingList::factory()->create()->id, 'delete'],
            'shopping item' => [fn () => '/shopping-items/'.ShoppingItem::factory()->create()->id.'/toggle', 'patch'],
            'budget category' => [fn () => '/budget/categories/'.BudgetCategory::factory()->create()->id, 'delete'],
            'income source' => [fn () => '/budget/income/'.IncomeSource::factory()->create()->id, 'delete'],
            'document folder' => [fn () => '/documents/'.DocumentFolder::create(['household_id' => User::factory()->create()->household_id, 'name' => 'Theirs'])->id, 'get'],
            'member' => [fn () => '/house/members/'.User::factory()->create()->id, 'delete'],
        ];
    }

    /** @param  Closure(): string  $url */
    #[Test]
    #[DataProvider('strangersRecords')]
    public function anOwnerGetsA404ForAnotherHouseholdsRecord(Closure $url, string $method): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->{$method}($url())->assertNotFound();
    }
}
