<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\DeletePushSubscriptionAction;
use App\Actions\Settings\SavePushSubscriptionAction;
use App\Actions\Settings\SendTestPushAction;
use App\Actions\Settings\UpdatePushOptOutsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroyPushSubscriptionRequest;
use App\Http\Requests\Settings\StorePushSubscriptionRequest;
use App\Http\Requests\Settings\UpdatePushOptOutsRequest;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Settings\NotificationSettingsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Settings → Notifications: which categories push, and this device's push subscription. */
class NotificationController extends Controller
{
    use ResolvesUserTrait;

    public function edit(Request $request, NotificationSettingsViewModel $settings): Response
    {
        return Inertia::render('settings/Notifications', $settings->toData($this->user($request)));
    }

    public function update(UpdatePushOptOutsRequest $request, UpdatePushOptOutsAction $update): RedirectResponse
    {
        $update->handle($this->user($request), $request->optOuts());

        return back()->with('toast', 'Notification settings saved');
    }

    // The subscription endpoints are called with fetch() from the push setup
    // code rather than as Inertia visits, so they answer with a bare 204.

    public function subscribe(StorePushSubscriptionRequest $request, SavePushSubscriptionAction $save): HttpResponse
    {
        $save->handle(
            $this->user($request),
            $request->toInput(),
        );

        return response()->noContent();
    }

    public function unsubscribe(DestroyPushSubscriptionRequest $request, DeletePushSubscriptionAction $delete): HttpResponse
    {
        $delete->handle($this->user($request), $request->endpoint());

        return response()->noContent();
    }

    public function test(Request $request, SendTestPushAction $sendTest): RedirectResponse
    {
        $sendTest->handle($this->user($request));

        return back()->with('toast', 'Test notification sent');
    }
}
