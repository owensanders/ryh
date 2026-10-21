<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('budget_transactions');

        Schema::table('budget_categories', function (Blueprint $table): void {
            $table->dropColumn('spent_pence');
        });
    }

    public function down(): void
    {
        Schema::table('budget_categories', function (Blueprint $table): void {
            $table->unsignedInteger('spent_pence')->default(0);
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
};
