<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The streak is each person's own: the days in a row they've signed
        // in. A household's shared count can't be split between its members,
        // so everyone starts again from their next login.
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('streak_days')->default(0);
            $table->date('streak_last_active_date')->nullable();
        });

        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn(['streak_days', 'streak_last_active_date']);
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->unsignedInteger('streak_days')->default(0);
            $table->date('streak_last_active_date')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['streak_days', 'streak_last_active_date']);
        });
    }
};
