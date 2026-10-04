<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ChoresReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function indexOnlyReturnsTheActingUsersNotifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->notify(new ChoresReminderNotification($user->household, 2));
        $other->notify(new ChoresReminderNotification($other->household, 3));

        $response = $this->actingAs($user)->getJson('/notifications')->assertOk();

        $this->assertCount(1, $response->json());
    }

    #[Test]
    public function markAsReadFlipsReadAt(): void
    {
        $user = User::factory()->create();
        $user->notify(new ChoresReminderNotification($user->household, 1));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->patch("/notifications/{$notification->id}/read")->assertRedirect(route('chores.index'));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    #[Test]
    public function markAllAsReadClearsEveryUnreadNotification(): void
    {
        $user = User::factory()->create();
        $user->notify(new ChoresReminderNotification($user->household, 1));
        $user->notify(new ChoresReminderNotification($user->household, 2));

        $this->actingAs($user)->post('/notifications/read-all')->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    #[Test]
    public function destroyAllRemovesOnlyTheActingUsersNotifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ChoresReminderNotification($user->household, 1));
        $other->notify(new ChoresReminderNotification($other->household, 1));

        $this->actingAs($user)->delete('/notifications')->assertRedirect();

        $this->assertSame(0, $user->notifications()->count());
        $this->assertSame(1, $other->notifications()->count());
    }
}
