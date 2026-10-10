<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ResolvesUserTrait;
use App\UseCases\Notifications\ClearNotificationsUseCase;
use App\UseCases\Notifications\GetNotificationsUseCase;
use App\UseCases\Notifications\MarkAllNotificationsReadUseCase;
use App\UseCases\Notifications\OpenNotificationUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesUserTrait;

    public function index(Request $request, GetNotificationsUseCase $getNotifications): JsonResponse
    {
        return response()->json($getNotifications->execute($this->user($request)));
    }

    /**
     * Opening a notification is reading it — mark it and go straight to what
     * it's about in one request, so the bell never has to race a navigation.
     */
    public function markAsRead(Request $request, string $id, OpenNotificationUseCase $open): RedirectResponse
    {
        $url = $open->execute($this->user($request), $id);

        return $url !== null ? redirect($url) : back();
    }

    public function markAllAsRead(Request $request, MarkAllNotificationsReadUseCase $markAllRead): RedirectResponse
    {
        $markAllRead->execute($this->user($request));

        return back();
    }

    public function destroyAll(Request $request, ClearNotificationsUseCase $clear): RedirectResponse
    {
        $clear->execute($this->user($request));

        return back();
    }
}
