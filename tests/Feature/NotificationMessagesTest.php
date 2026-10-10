<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatus;
use App\Enums\NotificationCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\AiCreditsExhaustedNotification;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventDeletedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use App\Notifications\ChoreActionedNotification;
use App\Notifications\ChoreAssignedNotification;
use App\Notifications\ChoreDeletedNotification;
use App\Notifications\ChoreEditedNotification;
use App\Notifications\ChoresReminderNotification;
use App\Notifications\HouseholdNotification;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealDeletedNotification;
use App\Notifications\MealEditedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What each notification says and where it leads: the same text shows in the
 * bell and as the phone push, titled with the household's name.
 */
class NotificationMessagesTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::factory()->create(['name' => 'Sarah Parker']);
        $this->recipient = User::factory()->create(['household_id' => $this->actor->household_id]);
    }

    #[Test]
    public function choreNotificationsNameWhoDidWhat(): void
    {
        $chore = Chore::factory()->create(['household_id' => $this->actor->household_id, 'name' => 'Take the bins out']);

        $this->assertMessage(new ChoreAssignedNotification($chore, $this->actor), 'chore_assigned', 'Sarah Parker assigned you "Take the bins out"', 'chores.index', NotificationCategory::Chores, ['chore_id' => $chore->id]);
        $this->assertMessage(new ChoreEditedNotification($chore, $this->actor), 'chore_edited', 'Sarah Parker updated "Take the bins out"', 'chores.index', NotificationCategory::Chores, ['chore_id' => $chore->id]);
        $this->assertMessage(new ChoreDeletedNotification($chore, $this->actor), 'chore_deleted', 'Sarah Parker deleted "Take the bins out"', 'chores.index', NotificationCategory::Chores);

        $chore->status = ChoreStatus::Done;
        $this->assertMessage(new ChoreActionedNotification($chore, $this->actor), 'chore_actioned', 'Sarah Parker completed "Take the bins out"', 'chores.index', NotificationCategory::Chores, ['chore_id' => $chore->id]);
        $chore->status = ChoreStatus::Today;
        $this->assertMessage(new ChoreActionedNotification($chore, $this->actor), 'chore_actioned', 'Sarah Parker reopened "Take the bins out"', 'chores.index', NotificationCategory::Chores, ['chore_id' => $chore->id]);
    }

    #[Test]
    public function theChoresReminderCountsWhatsOutstanding(): void
    {
        $household = $this->actor->household;

        $this->assertMessage(new ChoresReminderNotification($household, 1), 'chores_reminder', 'You have 1 outstanding chore', 'chores.index', NotificationCategory::Chores);
        $this->assertMessage(new ChoresReminderNotification($household, 3), 'chores_reminder', 'You have 3 outstanding chores', 'chores.index', NotificationCategory::Chores);
    }

    #[Test]
    public function calendarNotificationsNameWhoDidWhat(): void
    {
        $event = CalendarEvent::factory()->create(['household_id' => $this->actor->household_id, 'title' => 'Dentist']);

        $this->assertMessage(new CalendarEventAssignedNotification($event, $this->actor), 'calendar_event_assigned', 'Sarah Parker added you to "Dentist"', 'calendar.index', NotificationCategory::Calendar, ['event_id' => $event->id]);
        $this->assertMessage(new CalendarEventUpdatedNotification($event, $this->actor), 'calendar_event_updated', 'Sarah Parker updated "Dentist"', 'calendar.index', NotificationCategory::Calendar, ['event_id' => $event->id]);
        $this->assertMessage(new CalendarEventDeletedNotification($event, $this->actor), 'calendar_event_deleted', 'Sarah Parker deleted "Dentist"', 'calendar.index', NotificationCategory::Calendar);
    }

    #[Test]
    public function mealNotificationsNameTheRecipe(): void
    {
        $meal = PlannedMeal::factory()->create([
            'household_id' => $this->actor->household_id,
            'recipe_id' => Recipe::factory()->create(['household_id' => $this->actor->household_id, 'name' => 'Lemon chicken traybake'])->id,
            'cook_id' => $this->recipient->id,
        ]);

        $this->assertMessage(new MealAssignedNotification($meal, $this->actor), 'meal_assigned', 'Sarah Parker assigned you to cook "Lemon chicken traybake"', 'meals.index', NotificationCategory::Meals, ['meal_id' => $meal->id]);
        $this->assertMessage(new MealEditedNotification($meal, $this->actor), 'meal_edited', 'Sarah Parker updated "Lemon chicken traybake"', 'meals.index', NotificationCategory::Meals, ['meal_id' => $meal->id]);
        $this->assertMessage(new MealDeletedNotification($meal, $this->actor), 'meal_deleted', 'Sarah Parker removed "Lemon chicken traybake" from the meal plan', 'meals.index', NotificationCategory::Meals);
    }

    #[Test]
    public function theAiCreditAlertTellsTheAdminHowToResume(): void
    {
        config(['meal_planner.pause_hours' => 6]);

        $mail = (new AiCreditsExhaustedNotification('Anthropic'))->toMail(new AnonymousNotifiable);
        $text = implode("\n", $mail->introLines);

        $this->assertSame(['mail'], (new AiCreditsExhaustedNotification('Anthropic'))->via(new AnonymousNotifiable));
        $this->assertSame('RYH: AI meal planning paused — out of credit', $mail->subject);
        $this->assertStringContainsString('The Anthropic API reported the account is out of credit', $text);
        $this->assertStringContainsString('retries automatically after 6 hours', $text);
        $this->assertStringContainsString('php artisan ai:resume', $text);
    }

    /** @param  array<string, int>  $ids */
    private function assertMessage(HouseholdNotification $notification, string $type, string $body, string $route, NotificationCategory $category, array $ids = []): void
    {
        $this->assertSame(
            ['type' => $type, ...$ids, 'user_id' => $this->recipient->id, 'body' => $body, 'url' => route($route)],
            $notification->toArray($this->recipient),
            "{$type} bell entry",
        );
        $this->assertSame($category, $notification->category(), "{$type} category");
        $this->assertSame($type, $notification->broadcastType(), "{$type} live type");

        $push = $notification->toPushMessage($this->recipient);
        $this->assertSame($this->actor->household->name, $push->title, "{$type} push title");
        $this->assertSame($body, $push->body, "{$type} push body");
        $this->assertSame(route($route), $push->url, "{$type} push link");
        $this->assertSame("{$type}-{$this->recipient->id}", $push->tag, "{$type} push tag");
    }
}
