<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // An AI meal plan is written by a queued job, so the request that asks
        // for one, the job, and the page that shows it meet here. Its
        // suggestions are kept, so a plan finished while nobody was looking
        // isn't lost along with the week's allowance.
        Schema::create('ai_meal_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16);
            $table->json('answers');
            $table->json('meals')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_meal_plans');
    }
};
