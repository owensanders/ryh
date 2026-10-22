<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequestsMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** A write that reloads only some props (`only: [...]`) still gets its toast. */
class PartialReloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aPartialReloadSendsTheFlashAndOnlyThePropsAskedFor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['toast' => 'Hoover the stairs added'])
            ->get($this->hh('/chores'), [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) app(HandleInertiaRequestsMiddleware::class)->version(Request::create('/')),
                'X-Inertia-Partial-Component' => 'Chores',
                'X-Inertia-Partial-Data' => 'columns,scores',
            ])
            ->assertOk()
            ->assertJsonPath('props.flash.toast', 'Hoover the stairs added')
            ->assertJsonStructure(['props' => ['columns', 'scores']])
            ->assertJsonMissingPath('props.members');
    }
}
