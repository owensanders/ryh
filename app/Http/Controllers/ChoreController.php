<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Chores\CreateChoreAction;
use App\Actions\Chores\DeleteChoreAction;
use App\Actions\Chores\MoveChoreAction;
use App\Actions\Chores\NotifyChoresAction;
use App\Actions\Chores\ToggleChoreAction;
use App\Actions\Chores\UpdateChoreAction;
use App\Enums\ChoreStatusEnum;
use App\Http\Requests\ChoreRequest;
use App\Http\Requests\MoveChoreRequest;
use App\Models\Chore;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Chores\ChoreBoardViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChoreController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(Request $request, ChoreBoardViewModel $getChoreBoard): Response
    {
        $household = $this->household($request);

        return Inertia::render('Chores', $getChoreBoard->toData($household, $this->user($request)));
    }

    public function store(ChoreRequest $request, CreateChoreAction $create): RedirectResponse
    {
        $chore = $create->handle($this->household($request), $request->validated(), $this->user($request));

        return back()->with('toast', "{$chore->name} added");
    }

    public function update(ChoreRequest $request, Chore $chore, UpdateChoreAction $update): RedirectResponse
    {
        $updated = $update->handle($chore, $request->validated(), $this->user($request));

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Request $request, Chore $chore, DeleteChoreAction $delete): RedirectResponse
    {
        $name = $chore->name;
        $delete->handle($chore, $this->user($request));

        return back()->with('toast', "{$name} deleted");
    }

    public function toggle(Request $request, Chore $chore, ToggleChoreAction $toggle): RedirectResponse
    {
        $updated = $toggle->handle($chore, $this->user($request));

        return back()->with('toast', $updated->status === ChoreStatusEnum::Done ? "{$updated->name} — nice one" : null);
    }

    public function move(MoveChoreRequest $request, Chore $chore, MoveChoreAction $move): RedirectResponse
    {
        $status = ChoreStatusEnum::from($request->validated('status'));
        $move->handle($chore, $status);

        return back()->with('toast', "Moved to {$status->label()}");
    }

    public function notify(Request $request, NotifyChoresAction $notify): RedirectResponse
    {
        $household = $this->household($request);

        $notify->handle($household);

        return back()->with('toast', 'Reminders sent');
    }
}
