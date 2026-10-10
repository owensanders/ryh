<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Chore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Repositories eager-load what their callers use (CLAUDE.md, "Repositories own every query"). */
class LazyLoadingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    /** On everywhere; production only swaps throwing for a logged warning. */
    public function lazyLoadingIsPrevented(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    #[Test]
    public function lazyLoadingARelationAcrossACollectionThrows(): void
    {
        Chore::factory()->count(2)->create();

        $this->expectException(LazyLoadingViolationException::class);

        Chore::query()->get()->each(fn (Chore $chore) => $chore->assignee);
    }
}
