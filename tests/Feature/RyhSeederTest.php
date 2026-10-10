<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RecipeTag;
use App\Models\Recipe;
use Database\Seeders\RyhSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RyhSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seededRecipesOnlyUseRealRecipeTags(): void
    {
        $this->seed(RyhSeeder::class);

        $invalid = Recipe::all()
            ->flatMap(fn (Recipe $recipe) => $recipe->tags ?? [])
            ->reject(fn (string $tag) => RecipeTag::tryFrom($tag) !== null);

        $this->assertNotEmpty(Recipe::all());
        $this->assertSame([], $invalid->values()->all(), 'Seeded tags must be RecipeTag values, or editing those recipes fails validation.');
    }
}
