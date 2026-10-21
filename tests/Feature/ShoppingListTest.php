<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShoppingListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itFallsBackToTheFirstListWhenNoSlugIsGiven(): void
    {
        $user = User::factory()->create();
        ShoppingList::factory()->create(['household_id' => $user->household_id, 'name' => 'Tesco', 'position' => 0]);
        ShoppingList::factory()->create(['household_id' => $user->household_id, 'name' => 'Aldi', 'position' => 1]);

        $this->actingAs($user)
            ->get($this->hh('/shopping'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('active.name', 'Tesco')->count('lists', 2));
    }

    #[Test]
    public function itAddsAnItemToTheEndOfTheList(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id, 'slug' => 'tesco']);
        ShoppingItem::factory()->create(['shopping_list_id' => $list->id, 'position' => 4]);

        $this->actingAs($user)
            ->post($this->hh('/shopping/tesco/items'), ['name' => 'Olive oil'])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $added = ShoppingItem::where('name', 'Olive oil')->sole();

        $this->assertSame(5, $added->position);
        $this->assertSame('x1', $added->quantity);
        $this->assertFalse($added->isDone());
    }

    #[Test]
    public function itRejectsAnItemWithoutAName(): void
    {
        $user = User::factory()->create();
        ShoppingList::factory()->create(['household_id' => $user->household_id, 'slug' => 'tesco']);

        $this->actingAs($user)
            ->post($this->hh('/shopping/tesco/items'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function itTogglesAnItemBothWays(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);
        $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);

        $this->actingAs($user)->patch($this->hh("/shopping-items/{$item->id}/toggle"))->assertRedirect();
        $this->assertTrue($item->refresh()->isDone());

        $this->actingAs($user)->patch($this->hh("/shopping-items/{$item->id}/toggle"))->assertRedirect();
        $this->assertFalse($item->refresh()->isDone());
    }

    #[Test]
    public function itCreatesAShoppingList(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/shopping-lists'), ['name' => 'Aldi', 'colour' => 'sun'])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertDatabaseHas('shopping_lists', [
            'household_id' => $user->household_id,
            'name' => 'Aldi',
            'slug' => 'aldi',
            'colour' => 'sun',
        ]);
    }

    #[Test]
    public function itRenamesAShoppingList(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id, 'name' => 'Tesco', 'slug' => 'tesco', 'colour' => 'mint']);

        $this->actingAs($user)
            ->patch($this->hh("/shopping-lists/{$list->id}"), ['name' => 'Big Tesco', 'colour' => 'sky'])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $list->refresh();
        $this->assertSame('Big Tesco', $list->name);
        $this->assertSame('sky', $list->colour->value);
        $this->assertSame('tesco', $list->slug);
    }

    #[Test]
    public function itDoesNotRenameAnotherHouseholdsShoppingList(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirList = ShoppingList::factory()->create(['household_id' => $stranger->household_id, 'name' => 'Theirs']);

        $this->actingAs($user)
            ->patch($this->hh("/shopping-lists/{$theirList->id}"), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Theirs', $theirList->refresh()->name);
    }

    #[Test]
    public function itDeletesAShoppingListAndItsItems(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);
        $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);

        $this->actingAs($user)
            ->delete($this->hh("/shopping-lists/{$list->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertDatabaseMissing('shopping_lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('shopping_items', ['id' => $item->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsShoppingList(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirList = ShoppingList::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)->delete($this->hh("/shopping-lists/{$theirList->id}"))->assertNotFound();

        $this->assertDatabaseHas('shopping_lists', ['id' => $theirList->id]);
    }

    #[Test]
    public function itUpdatesAShoppingItem(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);
        $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id, 'name' => 'Milk']);

        $this->actingAs($user)
            ->patch($this->hh("/shopping-items/{$item->id}"), ['name' => 'Oat milk', 'quantity' => 'x2'])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $item->refresh();
        $this->assertSame('Oat milk', $item->name);
        $this->assertSame('x2', $item->quantity);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsShoppingItem(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirList = ShoppingList::factory()->create(['household_id' => $stranger->household_id]);
        $theirItem = ShoppingItem::factory()->create(['shopping_list_id' => $theirList->id, 'name' => 'Theirs']);

        $this->actingAs($user)
            ->patch($this->hh("/shopping-items/{$theirItem->id}"), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Theirs', $theirItem->refresh()->name);
    }

    #[Test]
    public function itDeletesAShoppingItem(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);
        $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);

        $this->actingAs($user)
            ->delete($this->hh("/shopping-items/{$item->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertDatabaseMissing('shopping_items', ['id' => $item->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsShoppingItem(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirList = ShoppingList::factory()->create(['household_id' => $stranger->household_id]);
        $theirItem = ShoppingItem::factory()->create(['shopping_list_id' => $theirList->id]);

        $this->actingAs($user)->delete($this->hh("/shopping-items/{$theirItem->id}"))->assertNotFound();

        $this->assertDatabaseHas('shopping_items', ['id' => $theirItem->id]);
    }

    #[Test]
    public function itDoesNotLeakItemsFromAnotherHousehold(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $theirList = ShoppingList::factory()->create(['household_id' => $stranger->household_id, 'slug' => 'theirs']);
        $theirItem = ShoppingItem::factory()->create(['shopping_list_id' => $theirList->id]);

        $this->actingAs($user)->get($this->hh('/shopping/theirs'))->assertNotFound();
        $this->actingAs($user)->post($this->hh('/shopping/theirs/items'), ['name' => 'Milk'])->assertNotFound();
        $this->actingAs($user)->patch($this->hh("/shopping-items/{$theirItem->id}/toggle"))->assertNotFound();

        $this->assertFalse($theirItem->refresh()->isDone());
    }
}
