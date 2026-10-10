<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\HouseholdRole;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const string ENDPOINT = 'https://push.example.test/sub/123';

    /** @return array<string, mixed> */
    private function subscription(string $endpoint = self::ENDPOINT): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    #[Test]
    public function itShowsEveryCategoryOnByDefault(): void
    {
        config(['webpush.vapid.public_key' => 'test-public-key']);

        $this->actingAs(User::factory()->create())
            ->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings/Notifications')
                ->where('vapidPublicKey', 'test-public-key')
                ->has('categories', 3)
                ->where('categories.0.value', 'chores')
                ->where('categories.0.enabled', true)
                ->where('categories.1.value', 'calendar')
                ->where('categories.2.value', 'meals'));
    }

    #[Test]
    public function itSaysPushIsUnavailableWithoutVapidKeys(): void
    {
        config(['webpush.vapid.public_key' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/settings/notifications')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('vapidPublicKey', null));
    }

    #[Test]
    public function guestsAreSentToLogIn(): void
    {
        $this->get('/settings/notifications')->assertRedirect('/login');
        $this->postJson('/settings/notifications/subscriptions', $this->subscription())->assertUnauthorized();
    }

    #[Test]
    public function itSavesTheCategoriesSomeoneTurnsOff(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/notifications', ['opt_outs' => ['meals', 'calendar']])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Notification settings saved');

        $this->assertSame(['meals', 'calendar'], $user->fresh()->push_opt_outs);

        $this->actingAs($user)->patch('/settings/notifications', ['opt_outs' => []]);
        $this->assertSame([], $user->fresh()->push_opt_outs);
    }

    #[Test]
    public function itRejectsUnknownCategories(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch('/settings/notifications', ['opt_outs' => ['budget']])
            ->assertSessionHasErrors('opt_outs.0');
    }

    #[Test]
    public function itSavesAndRemovesThisDevicesSubscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/settings/notifications/subscriptions', $this->subscription())->assertNoContent();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => self::ENDPOINT,
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
            'content_encoding' => 'aes128gcm',
        ]);

        // Subscribing again (e.g. after keys rotate) updates rather than duplicates.
        $this->actingAs($user)->postJson('/settings/notifications/subscriptions', $this->subscription())->assertNoContent();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        $this->actingAs($user)->deleteJson('/settings/notifications/subscriptions', ['endpoint' => self::ENDPOINT])->assertNoContent();
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => self::ENDPOINT]);
    }

    #[Test]
    public function itValidatesTheSubscription(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/settings/notifications/subscriptions', [
                'endpoint' => 'http://insecure.example.test/sub',
                'keys' => ['p256dh' => 'public-key'],
                'contentEncoding' => 'gzip',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.auth', 'contentEncoding']);
    }

    #[Test]
    public function nobodyCanRemoveSomeoneElsesSubscription(): void
    {
        $owner = User::factory()->create();
        $owner->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token');

        $this->actingAs(User::factory()->create())
            ->deleteJson('/settings/notifications/subscriptions', ['endpoint' => self::ENDPOINT])
            ->assertNoContent();

        $this->assertSame(1, $owner->pushSubscriptions()->count());
    }

    #[Test]
    public function aDeviceThatSignsIntoAnotherAccountMovesToIt(): void
    {
        $first = User::factory()->create();
        $first->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token');
        $second = User::factory()->create();

        $this->actingAs($second)->postJson('/settings/notifications/subscriptions', $this->subscription())->assertNoContent();

        $this->assertSame(0, $first->pushSubscriptions()->count());
        $this->assertSame(1, $second->pushSubscriptions()->count());
    }

    #[Test]
    public function itSendsATestPushAndLimitsHowOften(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/notifications/test')
            ->assertSessionHas('toast', 'Test notification sent');

        Notification::assertSentTo($user, PushNotification::class, fn (PushNotification $push) => $push->message->url === route('notifications.edit'));

        $this->actingAs($user)->post('/settings/notifications/test');
        $this->actingAs($user)->post('/settings/notifications/test');
        $this->actingAs($user)->post('/settings/notifications/test')->assertTooManyRequests();
    }

    #[Test]
    public function deletingYourAccountRemovesYourSubscriptions(): void
    {
        $user = User::factory()->create(['household_id' => null, 'role' => HouseholdRole::Adult]);
        $user->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token');

        $this->actingAs($user)->delete('/settings/profile', ['password' => 'password']);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => self::ENDPOINT]);
    }
}
