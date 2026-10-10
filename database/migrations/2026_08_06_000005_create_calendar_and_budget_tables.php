<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->boolean('is_all_day')->default(false);
            $table->string('who_label')->nullable();
            $table->string('colour')->default('mint');
            $table->timestamps();

            $table->index(['household_id', 'starts_at']);
        });

        Schema::create('budget_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('colour')->default('coral');
            // MoneyHelper is stored in pence to keep the arithmetic exact.
            $table->unsignedInteger('budgeted_pence');
            $table->unsignedInteger('spent_pence')->default(0);
            $table->date('month');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
        Schema::dropIfExists('calendar_events');
    }
};
