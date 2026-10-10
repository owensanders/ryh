<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ChoreStatusEnum;
use App\Http\Requests\ChoreRequest;
use App\Http\Requests\MoveChoreRequest;
use App\Models\Chore;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Chores\CreateChoreUseCase;
use App\UseCases\Chores\DeleteChoreUseCase;
use App\UseCases\Chores\GetChoreBoardUseCase;
use App\UseCases\Chores\MoveChoreUseCase;
use App\UseCases\Chores\NotifyChoresUseCase;
use App\UseCases\Chores\ToggleChoreUseCase;
use App\UseCases\Chores\UpdateChoreUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChoreController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(Request $request, GetChoreBoardUseCase $getChoreBoard): Response
    {
        $household = $this->household($request);

        return Inertia::render('Chores', $getChoreBoard->execute($household));
    }

    public function store(ChoreRequest $request, CreateChoreUseCase $create): RedirectResponse
    {
        $chore = $create->execute($this->household($request), $request->toData(), $this->user($request));

        return back()->with('toast', "{$chore->name} added");
    }

    public function update(ChoreRequest $request, Chore $chore, UpdateChoreUseCase $update): RedirectResponse
    {
        $updated = $update->execute($chore, $request->toData(), $this->user($request));

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

        $notify->execute($household);

        return back()->with('toast', 'Reminders sent');
    }
}
