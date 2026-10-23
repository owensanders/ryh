<?php

declare(strict_types=1);

use App\Actions\Budget\CarryForwardBudgetsAction;
use App\Actions\Documents\PruneOrphanedDocumentFilesAction;
use App\Actions\Notifications\PruneNotificationsAction;
use App\Ai\AiConfigService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('ai:resume', function () {
    AiConfigService::resume();
    $this->info('AI features resumed.');
})->purpose('Resume AI features after topping up provider credit');

Artisan::command('notifications:prune {--days=90}', function (PruneNotificationsAction $prune) {
    $deleted = $prune->handle(CarbonImmutable::now()->subDays((int) $this->option('days')));
    $this->info("Pruned {$deleted} notifications.");
})->purpose('Delete bell notifications older than the given number of days');

Artisan::command('documents:prune-orphans {--hours=24}', function (PruneOrphanedDocumentFilesAction $prune) {
    $deleted = $prune->handle(now()->subHours((int) $this->option('hours')));

    $this->info("Deleted {$deleted} orphaned document files.");
})->purpose('Delete stored document files that no document points to');

Artisan::command('budget:carry-forward', function (CarryForwardBudgetsAction $carryForward) {
    $households = $carryForward->handle();

    $this->info("Set up this month's budget for {$households} households.");
})->purpose("Bring recurring budget categories and income into each household's current month");

// Completed chores (Chore::KEEP_COMPLETED_DAYS), ticked-off shopping items
// (ShoppingItem::KEEP_COMPLETED_DAYS) and handled Stripe events
// (StripeWebhookEvent::KEEP_DAYS), then old notifications, then document
// files nothing points to any more.
Schedule::command('model:prune')->dailyAt('03:00')->onOneServer()->withoutOverlapping();
Schedule::command('notifications:prune')->dailyAt('03:15')->onOneServer()->withoutOverlapping();
Schedule::command('documents:prune-orphans')->dailyAt('03:30')->onOneServer()->withoutOverlapping();

// Hourly, since households reach the 1st at their own midnight. Repeats do nothing.
Schedule::command('budget:carry-forward')->hourly()->onOneServer()->withoutOverlapping();
