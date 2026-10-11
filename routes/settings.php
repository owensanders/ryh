<?php

use App\Http\Controllers\Settings\HouseholdController;
use App\Http\Controllers\Settings\NotificationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'throttle:app'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:password')->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/notifications', [NotificationController::class, 'edit'])->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationController::class, 'update'])->name('notifications.update');
    Route::post('settings/notifications/subscriptions', [NotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::delete('settings/notifications/subscriptions', [NotificationController::class, 'unsubscribe'])->name('notifications.unsubscribe');
    Route::post('settings/notifications/test', [NotificationController::class, 'test'])->middleware('throttle:3,1')->name('notifications.test');

    Route::get('settings/households', [HouseholdController::class, 'edit'])->name('households.edit');
    Route::patch('settings/households/current', [HouseholdController::class, 'switchCurrent'])->name('households.switch');
    Route::delete('settings/households/current', [HouseholdController::class, 'leave'])->name('households.leave');
});
