<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;

return new class extends Migration {
    public function up(): void
    {
        // One row per browser/device that has turned on push notifications
        // (laravel-notification-channels/webpush). Polymorphic, so account
        // deletion removes a user's rows explicitly.
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', PushSubscription::ENDPOINT_MAX_LENGTH)->charset('ascii')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
