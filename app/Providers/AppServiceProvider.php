<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\Fakes\E2eMealPlan;
use App\Models\Household;
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

        // Registered in routes/stripe.php instead, behind RequireStripeWebhookSecret.
        Cashier::ignoreRoutes();
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10));

        Cashier::useCustomerModel(Household::class);

        if ($this->app->environment('e2e')) {
            E2eMealPlan::register();
        }
    }
}
