<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RecipeTagEnum;
use App\Models\CalendarEvent;
use App\Models\Recipe;
use Database\Seeders\RyhSeeder;
use Illuminate\Database\Eloquent\Model;
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
            ->reject(fn (string $tag) => RecipeTagEnum::tryFrom($tag) !== null);

        $this->assertNotEmpty(Recipe::all());
        $this->assertSame([], $invalid->values()->all(), 'Seeded tags must be RecipeTagEnum values, or editing those recipes fails validation.');
    }

    #[Test]
    public function seededEventsShowAtTheTimesTheDemoGivesThem(): void
    {
        $this->seed(RyhSeeder::class);

        $practice = CalendarEvent::query()->where('title', 'Football practice')->firstOrFail();

        $this->assertSame('18:00–19:30', $practice->timeLabel('Europe/London'));
    }

    #[Test]
    public function manyHouseholdsCanBeSeededAtOnce(): void
    {
        Model::unguarded(function (): void {
            $first = (new RyhSeeder())->household('one');
            $second = (new RyhSeeder())->household('two');

            $this->assertNotSame($first->slug, $second->slug);
            $this->assertSame(['sarah+one@ryh.test'], $first->activeOwners()->pluck('email')->all());
            $this->assertSame(['sarah+two@ryh.test'], $second->activeOwners()->pluck('email')->all());
        });
    }
}
