<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('today');
            $table->string('due_label')->nullable();
            $table->string('repeat_label')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chores');
    }
};
