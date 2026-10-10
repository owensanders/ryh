<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ResolvesUserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesUserTrait;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->user($request)->notifications()->latest()->limit(20)->get(),
        );
    }

    /**
     * Opening a notification is reading it — mark it and go straight to what
     * it's about in one request, so the bell never has to race a navigation.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $this->user($request)->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return is_string($url) ? redirect($url) : back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->user($request)->unreadNotifications->markAsRead();

        return back();
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $this->user($request)->notifications()->delete();

        return back();
    }
}
