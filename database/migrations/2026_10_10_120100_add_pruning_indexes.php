<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The bell reads someone's latest notifications, newest first; the
        // nightly prune finds old ones by age.
        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
            $table->index('created_at');
        });

        // The nightly prune finds items ticked off a while ago.
        Schema::table('shopping_items', function (Blueprint $table): void {
            $table->index('completed_at');
        });

        // The app-wide daily cap counts households that generated an AI plan today.
        Schema::table('households', function (Blueprint $table): void {
            $table->index('ai_meal_plan_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropIndex(['ai_meal_plan_generated_at']);
        });

        Schema::table('shopping_items', function (Blueprint $table): void {
            $table->dropIndex(['completed_at']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'created_at']);
        });
    }
};
