<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->timestamp('ai_meal_plan_generated_at')->nullable()->after('streak_last_active_date');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn('ai_meal_plan_generated_at');
        });
    }
};
