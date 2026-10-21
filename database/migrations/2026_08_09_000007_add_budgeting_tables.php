<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('budget_categories', function (Blueprint $table): void {
            $table->string('icon')->nullable()->after('label');
        });

        Schema::create('income_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('meta')->nullable();
            $table->string('colour')->default('mint');
            // MoneyHelper is stored in pence to keep the arithmetic exact.
            $table->unsignedInteger('amount_pence');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['household_id']);
        });

        Schema::create('budget_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_category_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->string('label');
            $table->unsignedInteger('amount_pence');
            $table->timestamps();

            $table->index(['household_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
        Schema::dropIfExists('income_sources');

        Schema::table('budget_categories', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
    }
};
