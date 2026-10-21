<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaletteEnum;
use App\Models\BudgetCategory;
use App\Models\IncomeSource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRendersCategoriesAndIncomeForTheMonth(): void
    {
        $user = User::factory()->create();
        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'label' => 'Food & shopping',
            'budgeted_pence' => 50000,
        ]);
        IncomeSource::factory()->create(['household_id' => $user->household_id, 'amount_pence' => 200000]);

        $this->actingAs($user)
            ->get($this->hh('/budget'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget')
                ->count('categories', 1)
                ->count('income', 1)
                ->where('categories.0.label', 'Food & shopping')
            );
    }

    #[Test]
    public function itCreatesACategoryAppendedToTheViewedMonth(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/budget/categories'), ['label' => 'Pets', 'budgeted' => '75'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Pets added');

        $this->assertDatabaseHas('budget_categories', [
            'household_id' => $user->household_id,
            'label' => 'Pets',
            'budgeted_pence' => 7500,
        ]);
    }

    #[Test]
    public function itGivesEachNewCategoryTheLeastUsedColour(): void
    {
        $user = User::factory()->create();

        foreach (['Food', 'Utilities', 'Fuel', 'Pets', 'Gifts', 'Holidays'] as $label) {
            $this->actingAs($user)->post($this->hh('/budget/categories'), ['label' => $label, 'budgeted' => '50']);
        }

        $colours = BudgetCategory::query()->orderBy('position')->pluck('colour')->map->value->all();

        $this->assertSame(['coral', 'mint', 'lilac', 'sun', 'sky', 'coral'], $colours);
    }

    #[Test]
    public function itLeavesTheIconBlankWhenACategoryHasNone(): void
    {
        $user = User::factory()->create();
        BudgetCategory::factory()->recurring()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->get($this->hh('/budget'))
            ->assertInertia(fn ($page) => $page
                ->where('categories.0.icon', '')
                ->where('categories.0.isRecurring', true)
            );
    }

    #[Test]
    public function itCreatesARecurringCategory(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/budget/categories'), ['label' => 'Rent', 'budgeted' => '900', 'is_recurring' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('budget_categories', [
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'is_recurring' => true,
        ]);
    }

    #[Test]
    public function itUpdatesACategory(): void
    {
        $user = User::factory()->create();
        $category = BudgetCategory::factory()->create(['household_id' => $user->household_id, 'label' => 'Fuel']);

        $this->actingAs($user)
            ->patch($this->hh("/budget/categories/{$category->id}"), ['label' => 'Transport & fuel', 'budgeted' => '220'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Transport & fuel updated');

        $category->refresh();
        $this->assertSame('Transport & fuel', $category->label);
        $this->assertSame(22000, $category->budgeted_pence);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsCategory(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = BudgetCategory::factory()->create(['household_id' => $stranger->household_id, 'label' => 'Fuel']);

        $this->actingAs($user)
            ->patch($this->hh("/budget/categories/{$category->id}"), ['label' => 'Hijacked', 'budgeted' => '10'])
            ->assertNotFound();

        $this->assertSame('Fuel', $category->refresh()->label);
    }

    #[Test]
    public function itDeletesACategory(): void
    {
        $user = User::factory()->create();
        $category = BudgetCategory::factory()->create(['household_id' => $user->household_id, 'label' => 'Subscriptions']);

        $this->actingAs($user)
            ->delete($this->hh("/budget/categories/{$category->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast', 'Subscriptions removed');

        $this->assertDatabaseMissing('budget_categories', ['id' => $category->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsCategory(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $category = BudgetCategory::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)
            ->delete($this->hh("/budget/categories/{$category->id}"))
            ->assertNotFound();

        $this->assertDatabaseHas('budget_categories', ['id' => $category->id]);
    }

    #[Test]
    public function itCreatesAnIncomeSource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/budget/income'), ['label' => 'Salary', 'meta' => 'Paid 28th', 'amount' => '2480'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Salary added');

        $this->assertDatabaseHas('income_sources', [
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'amount_pence' => 248000,
        ]);
    }

    #[Test]
    public function itUpdatesAnIncomeSource(): void
    {
        $user = User::factory()->create();
        $income = IncomeSource::factory()->create(['household_id' => $user->household_id, 'label' => 'Salary']);

        $this->actingAs($user)
            ->patch($this->hh("/budget/income/{$income->id}"), ['label' => 'Sarah — salary', 'amount' => '2500'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Sarah — salary updated');

        $this->assertSame('Sarah — salary', $income->refresh()->label);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsIncomeSource(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $income = IncomeSource::factory()->create(['household_id' => $stranger->household_id, 'label' => 'Salary']);

        $this->actingAs($user)
            ->patch($this->hh("/budget/income/{$income->id}"), ['label' => 'Hijacked', 'amount' => '10'])
            ->assertNotFound();

        $this->assertSame('Salary', $income->refresh()->label);
    }

    #[Test]
    public function itDeletesAnIncomeSource(): void
    {
        $user = User::factory()->create();
        $income = IncomeSource::factory()->create(['household_id' => $user->household_id, 'label' => 'Airbnb']);

        $this->actingAs($user)
            ->delete($this->hh("/budget/income/{$income->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast', 'Airbnb removed');

        $this->assertDatabaseMissing('income_sources', ['id' => $income->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsIncomeSource(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $income = IncomeSource::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)
            ->delete($this->hh("/budget/income/{$income->id}"))
            ->assertNotFound();

        $this->assertDatabaseHas('income_sources', ['id' => $income->id]);
    }

    #[Test]
    public function itCreatesARecurringIncomeSource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/budget/income'), ['label' => 'Salary', 'amount' => '2480', 'is_recurring' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('income_sources', [
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'is_recurring' => true,
        ]);
    }

    #[Test]
    public function itCarriesForwardRecurringIncomeIntoAnEmptyMonth(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $nextMonth = $thisMonth->addMonth();

        IncomeSource::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'amount_pence' => 248000,
            'month' => $thisMonth,
        ]);
        IncomeSource::factory()->create([
            'household_id' => $user->household_id,
            'label' => 'One-off bonus',
            'month' => $thisMonth,
        ]);

        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();

        $this->assertDatabaseHas('income_sources', [
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'amount_pence' => 248000,
            'is_recurring' => true,
            'month' => $nextMonth->toDateString(),
        ]);
        $this->assertDatabaseMissing('income_sources', [
            'household_id' => $user->household_id,
            'label' => 'One-off bonus',
            'month' => $nextMonth->toDateString(),
        ]);
    }

    #[Test]
    public function itDoesNotDuplicateCarriedIncomeOnRepeatedVisits(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $nextMonth = $thisMonth->addMonth();

        IncomeSource::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'month' => $thisMonth,
        ]);

        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();
        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();

        $this->assertDatabaseCount('income_sources', 2);
    }

    #[Test]
    public function itCarriesForwardIncomeAcrossAGapMonth(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $twoMonthsAhead = $thisMonth->addMonths(2);

        IncomeSource::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'month' => $thisMonth,
        ]);

        // The month in between is never visited, so it's genuinely empty.
        $this->actingAs($user)->get($this->hh('/budget?month=').$twoMonthsAhead->format('Y-m'))->assertOk();

        $this->assertDatabaseHas('income_sources', [
            'household_id' => $user->household_id,
            'label' => 'Salary',
            'month' => $twoMonthsAhead->toDateString(),
        ]);
    }

    #[Test]
    public function itCarriesForwardRecurringCategoriesIntoAnEmptyMonth(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $nextMonth = $thisMonth->addMonth();

        BudgetCategory::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'colour' => PaletteEnum::Sky,
            'budgeted_pence' => 90000,
            'month' => $thisMonth,
        ]);
        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'label' => 'One-off holiday',
            'month' => $thisMonth,
        ]);

        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();

        $this->assertDatabaseHas('budget_categories', [
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'colour' => 'sky',
            'budgeted_pence' => 90000,
            'is_recurring' => true,
            'month' => $nextMonth->toDateString(),
        ]);
        $this->assertDatabaseMissing('budget_categories', [
            'household_id' => $user->household_id,
            'label' => 'One-off holiday',
            'month' => $nextMonth->toDateString(),
        ]);
    }

    #[Test]
    public function itDoesNotDuplicateCarriedCategoriesOnRepeatedVisits(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $nextMonth = $thisMonth->addMonth();

        BudgetCategory::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'month' => $thisMonth,
        ]);

        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();
        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();

        $this->assertDatabaseCount('budget_categories', 2);
    }

    #[Test]
    public function itCarriesForwardAcrossAGapMonth(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $twoMonthsAhead = $thisMonth->addMonths(2);

        BudgetCategory::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'budgeted_pence' => 90000,
            'month' => $thisMonth,
        ]);

        // The month in between is never visited, so it's genuinely empty.
        $this->actingAs($user)->get($this->hh('/budget?month=').$twoMonthsAhead->format('Y-m'))->assertOk();

        $this->assertDatabaseHas('budget_categories', [
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'month' => $twoMonthsAhead->toDateString(),
        ]);
    }

    #[Test]
    public function itKeepsCarriedForwardCategoriesIndependentlyEditable(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();
        $nextMonth = $thisMonth->addMonth();

        BudgetCategory::factory()->recurring()->create([
            'household_id' => $user->household_id,
            'label' => 'Rent',
            'budgeted_pence' => 90000,
            'month' => $thisMonth,
        ]);

        $this->actingAs($user)->get($this->hh('/budget?month=').$nextMonth->format('Y-m'))->assertOk();

        $carried = BudgetCategory::query()->whereDate('month', $nextMonth->toDateString())->firstOrFail();

        $this->actingAs($user)
            ->patch($this->hh("/budget/categories/{$carried->id}"), ['label' => 'Rent', 'budgeted' => '950'])
            ->assertRedirect();

        $this->assertSame(95000, $carried->refresh()->budgeted_pence);
        $this->assertDatabaseHas('budget_categories', [
            'household_id' => $user->household_id,
            'month' => $thisMonth->toDateString(),
            'budgeted_pence' => 90000,
        ]);
    }

    #[Test]
    public function itIncludesThreeMonthsOfHistory(): void
    {
        $user = User::factory()->create();
        $thisMonth = CarbonImmutable::now()->startOfMonth();

        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'budgeted_pence' => 50000,
            'month' => $thisMonth,
        ]);
        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'budgeted_pence' => 30000,
            'month' => $thisMonth->subMonth(),
        ]);

        $this->actingAs($user)
            ->get($this->hh('/budget'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget')
                ->count('history', 3)
                ->where('history.2.totalPence', 50000)
                ->where('history.1.totalPence', 30000)
                ->where('history.0.totalPence', 0)
            );
    }
}
