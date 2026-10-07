<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroyPushSubscriptionRequest;
use App\Http\Requests\Settings\StorePushSubscriptionRequest;
use App\Http\Requests\Settings\UpdatePushOptOutsRequest;
use App\UseCases\Settings\DeletePushSubscriptionUseCase;
use App\UseCases\Settings\SavePushSubscriptionUseCase;
use App\UseCases\Settings\SendTestPushUseCase;
use App\UseCases\Settings\ShowNotificationSettingsUseCase;
use App\UseCases\Settings\UpdatePushOptOutsUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Settings → Notifications: which categories push, and this device's push subscription. */
class NotificationController extends Controller
{
    public function edit(Request $request, ShowNotificationSettingsUseCase $show): Response
    {
        return Inertia::render('settings/Notifications', $show->execute($request->user()));
    }

    public function update(UpdatePushOptOutsRequest $request, UpdatePushOptOutsUseCase $update): RedirectResponse
    {
        $update->execute($request->user(), $request->optOuts());

        return back()->with('toast', 'Notification settings saved');
    }

    // The subscription endpoints are called with fetch() from the push setup
    // code rather than as Inertia visits, so they answer with a bare 204.

    public function subscribe(StorePushSubscriptionRequest $request, SavePushSubscriptionUseCase $save): HttpResponse
    {
        $save->execute(
            $request->user(),
            $request->validated('endpoint'),
            $request->validated('keys.p256dh'),
            $request->validated('keys.auth'),
            $request->validated('contentEncoding'),
        );

        return response()->noContent();
    }

    public function unsubscribe(DestroyPushSubscriptionRequest $request, DeletePushSubscriptionUseCase $delete): HttpResponse
    {
        $delete->execute($request->user(), $request->validated('endpoint'));

        return response()->noContent();
    }

    public function test(Request $request, SendTestPushUseCase $sendTest): RedirectResponse
    {
        $sendTest->execute($request->user());

        return back()->with('toast', 'Test notification sent');
    }
}
