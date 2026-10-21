<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationCategoryEnum;
use App\Models\Chore;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventDeletedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use App\Notifications\Channels\QueuedBroadcastChannel;
use App\Notifications\Channels\QueuedWebPushChannel;
use App\Notifications\ChoreActionedNotification;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreDeletedNotification;
use App\Notifications\ChoreEditedNotification;
use App\Notifications\ChoresReminderNotification;
use App\Notifications\HouseholdNotification;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealDeletedNotification;
use App\Notifications\MealEditedNotification;
use App\Notifications\PushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(User $user, string $endpoint = 'https://push.example.test/abc'): void
    {
        $user->updatePushSubscription($endpoint, 'public-key', 'auth-token', 'aes128gcm');
    }

    #[Test]
    public function itOnlyPushesToPeopleWhoHaveTurnedItOnForADevice(): void
    {
        $owner = User::factory()->create();
        $chore = Chore::factory()->create(['household_id' => $owner->household_id]);
        $notification = new ChoreAssignedNotification($chore, $owner);

        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $this->assertNotContains(QueuedWebPushChannel::class, $notification->via($partner));

        $this->subscribe($partner);
        $this->assertContains(QueuedWebPushChannel::class, $notification->via($partner));
        $this->assertContains('database', $notification->via($partner));
        $this->assertContains(QueuedBroadcastChannel::class, $notification->via($partner));
    }

    #[Test]
    public function aCategoryTheyHaveTurnedOffIsNotPushedButStillReachesTheBell(): void
    {
        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id, 'push_opt_outs' => [NotificationCategoryEnum::Chores->value]]);
        $this->subscribe($partner);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id]);

        $this->assertSame(['database', QueuedBroadcastChannel::class], (new ChoreAssignedNotification($chore, $owner))->via($partner));
    }

    #[Test]
    public function aPushIsQueuedTitledWithTheHouseholdName(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $this->subscribe($partner);

        $this->actingAs($owner)->post($this->hh('/chores'), ['name' => 'Clean the gutters', 'assigned_to' => $partner->id]);

        // The bell is still updated synchronously.
        $this->assertSame(1, $partner->notifications()->count());

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($owner, $partner) {
            $message = $job->notification instanceof PushNotification ? $job->notification->message : null;

            return $message !== null
                && $job->notifiables->pluck('id')->all() === [$partner->id]
                && $message->title === $owner->lastHousehold->name
                && $message->body === "{$owner->name} assigned you \"Clean the gutters\""
                && $message->url === route('chores.index', ['household' => $owner->lastHousehold->slug]);
        });
    }

    #[Test]
    public function aPushAboutADeletedChoreStillGoesOut(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $this->subscribe($partner);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $partner->id]);
        $chore->delete();

        $partner->notify(new ChoreDeletedNotification($chore, $owner));

        // Plain values only, so the job can't fail reloading the deleted chore.
        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) {
            return $job->notification instanceof PushNotification
                && unserialize(serialize($job->notification)) instanceof PushNotification;
        });
    }

    #[Test]
    public function thePushPayloadOpensThePageItIsAbout(): void
    {
        $owner = User::factory()->create();
        $partner = User::factory()->create(['household_id' => $owner->household_id]);
        $push = new PushNotification((new ChoreAssignedNotification(
            Chore::factory()->create(['household_id' => $owner->household_id, 'name' => 'Bins']),
            $owner,
        ))->toPushMessage($partner));

        $payload = $push->toWebPush($partner, $push)->toArray();

        $this->assertSame($owner->lastHousehold->name, $payload['title']);
        $this->assertSame("{$owner->name} assigned you \"Bins\"", $payload['body']);
        $this->assertSame('/icons/icon-192.png', $payload['icon']);
        $this->assertSame("chore_assigned-{$partner->id}", $payload['tag']);
        $this->assertSame(['url' => route('chores.index', ['household' => $owner->lastHousehold->slug])], $payload['data']);
    }

    /** @return array<string, array{class-string<HouseholdNotification>, NotificationCategoryEnum}> */
    public static function notificationCategories(): array
    {
        return [
            'chore assigned' => [ChoreAssignedNotification::class, NotificationCategoryEnum::Chores],
            'chore edited' => [ChoreEditedNotification::class, NotificationCategoryEnum::Chores],
            'chore actioned' => [ChoreActionedNotification::class, NotificationCategoryEnum::Chores],
            'chore deleted' => [ChoreDeletedNotification::class, NotificationCategoryEnum::Chores],
            'chores reminder' => [ChoresReminderNotification::class, NotificationCategoryEnum::Chores],
            'event assigned' => [CalendarEventAssignedNotification::class, NotificationCategoryEnum::Calendar],
            'event updated' => [CalendarEventUpdatedNotification::class, NotificationCategoryEnum::Calendar],
            'event deleted' => [CalendarEventDeletedNotification::class, NotificationCategoryEnum::Calendar],
            'meal assigned' => [MealAssignedNotification::class, NotificationCategoryEnum::Meals],
            'meal edited' => [MealEditedNotification::class, NotificationCategoryEnum::Meals],
            'meal deleted' => [MealDeletedNotification::class, NotificationCategoryEnum::Meals],
        ];
    }

    /** @param class-string<HouseholdNotification> $class */
    #[Test]
    #[DataProvider('notificationCategories')]
    public function eachNotificationIsFiledUnderTheRightCategory(string $class, NotificationCategoryEnum $category): void
    {
        $notification = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        $this->assertSame($category, $notification->category());
    }
}
