<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Data\MemberData;
use App\Enums\ChoreStatusEnum;
use App\Http\Requests\ChoreRequest;
use App\Http\Requests\MoveChoreRequest;
use App\Models\Chore;
use App\Models\Household;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Chores\CreateChoreUseCase;
use App\UseCases\Chores\DeleteChoreUseCase;
use App\UseCases\Chores\GetChoreBoardUseCase;
use App\UseCases\Chores\MoveChoreUseCase;
use App\UseCases\Chores\NotifyChoresUseCase;
use App\UseCases\Chores\ToggleChoreUseCase;
use App\UseCases\Chores\UpdateChoreUseCase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChoreController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function __construct(private readonly HouseholdRepositoryInterface $households) {}

    public function index(Request $request, GetChoreBoardUseCase $getChoreBoard): Response
    {
        $household = $this->household($request);

        return Inertia::render('Chores', [
            ...$getChoreBoard->execute($household),
            'members' => $this->households->members($household)->map(MemberData::fromModel(...))->values(),
            'notifyChoresAvailableAt' => $this->notifyChoresAvailableAt($household),
        ]);
    }

    public function store(ChoreRequest $request, CreateChoreUseCase $create): RedirectResponse
    {
        $chore = $create->execute($this->household($request), $request->choreAttributes(), $this->user($request));

        return back()->with('toast', "{$chore->name} added");
    }

    public function update(ChoreRequest $request, Chore $chore, UpdateChoreUseCase $update): RedirectResponse
    {
        $updated = $update->execute($chore, $request->choreAttributes(), $this->user($request));

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Request $request, Chore $chore, DeleteChoreUseCase $delete): RedirectResponse
    {
        $name = $chore->name;
        $delete->execute($chore, $this->user($request));

        return back()->with('toast', "{$name} deleted");
    }

    public function toggle(Request $request, Chore $chore, ToggleChoreUseCase $toggle): RedirectResponse
    {
        $updated = $toggle->execute($chore, $this->user($request));

        return back()->with('toast', $updated->done ? "{$updated->name} — nice one" : null);
    }

    public function move(MoveChoreRequest $request, Chore $chore, MoveChoreUseCase $move): RedirectResponse
    {
        $status = ChoreStatusEnum::from($request->validated('status'));
        $move->execute($chore, $status);

        return back()->with('toast', "Moved to {$status->label()}");
    }

    public function notify(Request $request, NotifyChoresUseCase $notify): RedirectResponse
    {
        $household = $this->household($request);

        abort_if($this->notifyChoresAvailableAt($household) !== null, 429);

        $notify->execute($household);

        return back()->with('toast', 'Reminders sent');
    }

    /** When the "Notify Chores" button becomes usable again — null once it's already available. */
    private function notifyChoresAvailableAt(Household $household): ?Carbon
    {
        if ($household->last_chore_notification_sent_at === null) {
            return null;
        }

        $availableAt = $household->last_chore_notification_sent_at->addMinutes(config('notifications.chore_reminder_cooldown_minutes'));

        return $availableAt->isFuture() ? $availableAt : null;
    }
}
