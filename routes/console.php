<?php

declare(strict_types=1);

use App\Actions\Budget\CarryForwardBudgetsAction;
use App\Actions\Documents\PruneOrphanedDocumentFilesAction;
use App\Ai\AiConfigService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:resume', function () {
    AiConfigService::resume();
    $this->info('AI features resumed.');
})->purpose('Resume AI features after topping up provider credit');

Artisan::command('notifications:prune {--days=90}', function () {
    $cutoff = now()->subDays((int) $this->option('days'));
    $deleted = 0;

    // In batches, so a big first prune never holds a long lock on the table.
    do {
        $batch = DatabaseNotification::query()->where('created_at', '<', $cutoff)->limit(1000)->delete();
        $deleted += $batch;
    } while ($batch > 0);

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
