<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\Fakes\E2eMealPlanFake;
use App\Models\Household;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /** Any signed-in request: far above a person clicking around, well below a script. */
    private const int APP_PER_MINUTE = 120;

    private const int GUEST_FORMS_PER_MINUTE = 5;

    private const int GUEST_FORMS_PER_HOUR = 20;

    private const int PASSWORD_PER_MINUTE = 5;

    private const int JOIN_CODE_PER_MINUTE = 5;

    private const int JOIN_CODE_PER_HOUR = 20;

    private const int EMAILS_PER_HOUR = 10;

    private const int UPLOADS_PER_MINUTE = 20;

    /** Bursts only: the weekly plan and AI_MEAL_PLAN_DAILY_CAP are the real limits. Failed validation counts too. */
    private const int AI_PER_MINUTE = 5;

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

        $this->configureRateLimiting();

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

    /**
     * Named limiters for the routes' throttle:<name> middleware. Signed-in
     * limits count per user, guest ones per IP (so TRUSTED_PROXIES must be
     * right in production). Where a limiter has two windows, each gets its own
     * key, or they'd share one counter.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('app', fn (Request $request) => Limit::perMinute(self::APP_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));

        // Playwright signs up dozens of people from one IP; e2e isn't testing limits.
        RateLimiter::for('guest-forms', fn (Request $request) => $this->app->environment('e2e') ? Limit::none() : [
            Limit::perMinute(self::GUEST_FORMS_PER_MINUTE)->by('minute:'.$request->ip()),
            Limit::perHour(self::GUEST_FORMS_PER_HOUR)->by('hour:'.$request->ip()),
        ]);

        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(self::PASSWORD_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('join-code', fn (Request $request) => [
            Limit::perMinute(self::JOIN_CODE_PER_MINUTE)->by('minute:'.($request->user()?->id ?: $request->ip())),
            Limit::perHour(self::JOIN_CODE_PER_HOUR)->by('hour:'.($request->user()?->id ?: $request->ip())),
        ]);

        RateLimiter::for('emails', fn (Request $request) => Limit::perHour(self::EMAILS_PER_HOUR)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(self::UPLOADS_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(self::AI_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
