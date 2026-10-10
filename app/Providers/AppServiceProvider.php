<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\Fakes\E2eMealPlanFake;
use App\Models\Household;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Registered in routes/stripe.php instead, behind RequireStripeWebhookSecretMiddleware.
        Cashier::ignoreRoutes();
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10));

        Cashier::useCustomerModel(Household::class);

        // Repositories eager-load what their callers use (CLAUDE.md,
        // "Repositories own every query"). A lazy load throws everywhere but
        // production, where it's logged instead so a miss never breaks a page.
        Model::preventLazyLoading();

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                Log::warning('Lazy-loaded a relation; eager-load it in the repository.', [
                    'model' => $model::class,
                    'relation' => $relation,
                ]);
            });
        }

        if ($this->app->environment('e2e')) {
            E2eMealPlanFake::register();
        }
    }
}
