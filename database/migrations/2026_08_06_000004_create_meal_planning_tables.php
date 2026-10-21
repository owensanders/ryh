<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('duration_label')->nullable();
            $table->string('difficulty')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedTinyInteger('tint')->default(0);
            $table->boolean('is_favourite')->default(false);
            $table->timestamps();
        });

        Schema::create('planned_meals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cook_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('planned_on');
            $table->string('slot')->default('dinner');
            $table->unsignedInteger('missing_ingredients')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'planned_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_meals');
        Schema::dropIfExists('recipes');
    }
};
