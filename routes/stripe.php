<?php

declare(strict_types=1);

use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\RequireStripeWebhookSecretMiddleware;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController;

// Cashier's own routes (its auto-registration is switched off in
// AppServiceProvider), re-registered so the webhook can refuse to run
// without a signing secret.
Route::prefix(config('cashier.path'))->name('cashier.')->group(function (): void {
    Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
    Route::post('webhook', [StripeWebhookController::class, 'handleWebhook'])
        ->middleware(RequireStripeWebhookSecretMiddleware::class)
        ->name('webhook');
});
