<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Budget\SetUpBudgetMonthAction;
use App\Actions\Chores\NotifyChoresAction;
use App\Actions\Documents\UploadDocumentAction;
use App\Actions\House\InviteMemberAction;
use App\Actions\Meals\RequestAiMealPlanAction;
use App\Enums\HouseholdRoleEnum;
use App\Models\Chore;
use App\Models\DocumentFolder;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The actions guarding a race lock the household's row before they read
 * anything: with another connection holding it, the first thing they wait on
 * (here, until a one-second timeout) is their own `select … for update`. Uses real commits rather than
 * RefreshDatabase's transaction, so the second connection can see the rows.
 */
class HouseholdLockTest extends TestCase
{
    use DatabaseTruncation;

    private const string OTHER = 'mysql_other_request';

    /**
     * Each case's setup runs before the lock is taken (inserting a row that
     * points at the household would wait on it too); then the action under test.
     *
     * @return array<string, array{Closure(User, Household): mixed, Closure(User, Household, mixed): mixed}>
     */
    public static function guardedActions(): array
    {
        $nothing = fn (): null => null;

        return [
            'notify chores' => [
                fn (User $owner, Household $household) => Chore::factory()->create(['household_id' => $household->id, 'assigned_to' => $owner->id]),
                fn (User $owner, Household $household) => app(NotifyChoresAction::class)->handle($household),
            ],
            'ask for an AI meal plan' => [
                function (): void {
                    config(['ai.providers.anthropic.key' => 'test-key']);
                },
                fn (User $owner, Household $household) => app(RequestAiMealPlanAction::class)->handle($household, ['people' => 4, 'confidence' => 'comfortable'], $owner),
            ],
            'invite a member' => [
                fn () => Mail::fake(),
                fn (User $owner, Household $household) => app(InviteMemberAction::class)->handle($household, ['email' => 'gran@example.com', 'role' => 'adult'], $owner),
            ],
            'set up a budget month' => [
                $nothing,
                fn (User $owner, Household $household) => app(SetUpBudgetMonthAction::class)->handle($household, CarbonImmutable::now()->addMonth()),
            ],
            'upload a document' => [
                fn (User $owner, Household $household) => DocumentFolder::create(['household_id' => $household->id, 'name' => 'Car']),
                fn (User $owner, Household $household, mixed $folder) => app(UploadDocumentAction::class)
                    ->handle($folder, UploadedFile::fake()->create('mot.pdf', 10, 'application/pdf'), $owner),
            ],
        ];
    }

    /**
     * @param  Closure(User, Household): mixed  $setUp
     * @param  Closure(User, Household, mixed): mixed  $act
     */
    #[Test]
    #[DataProvider('guardedActions')]
    public function itLocksTheHouseholdBeforeReadingAnything(Closure $setUp, Closure $act): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $household = $owner->lastHousehold()->firstOrFail();
        $prepared = $setUp($owner, $household);

        config(['database.connections.'.self::OTHER => config('database.connections.mysql')]);
        $other = DB::connection(self::OTHER);
        $other->beginTransaction();
        $other->select('select id from households where id = ? for update', [$household->id]);

        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $act($owner, $household, $prepared);
            $this->fail('It ran while another request held the household lock.');
        } catch (QueryException $e) {
            // 1205: Lock wait timeout exceeded, and on its locking read of the household,
            // before it has decided anything, not on some later write that also needs the row.
            $this->assertSame(1205, $e->errorInfo[1] ?? null, $e->getMessage());
            $this->assertStringContainsString('`households`', $e->getSql());
            $this->assertStringContainsString('for update', $e->getSql());
        } finally {
            $other->rollBack();
            DB::purge(self::OTHER);
        }

        // An upload that couldn't take the lock leaves no file behind either.
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
