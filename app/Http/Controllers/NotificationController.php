<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notifications\ClearNotificationsAction;
use App\Actions\Notifications\MarkAllNotificationsReadAction;
use App\Actions\Notifications\MarkNotificationReadAction;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Notifications\NotificationsViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesUserTrait;

    public function index(Request $request, NotificationsViewModel $getNotifications): JsonResponse
    {
        return response()->json($getNotifications->toData($this->user($request)));
    }

    public function markAsRead(Request $request, string $id, MarkNotificationReadAction $markRead): RedirectResponse
    {
        $markRead->handle($this->user($request), $id);

        return back();
    }

    public function markAllAsRead(Request $request, MarkAllNotificationsReadAction $markAllRead): RedirectResponse
    {
        $markAllRead->handle($this->user($request));

        return back();
    }

    public function destroyAll(Request $request, ClearNotificationsAction $clear): RedirectResponse
    {
        $clear->handle($this->user($request));

        return back();
    }
}
