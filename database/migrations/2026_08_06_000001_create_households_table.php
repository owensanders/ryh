<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedInteger('streak_days')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('household_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('initials', 4)->nullable()->after('name');
            $table->string('colour')->default('mint')->after('initials');
            $table->string('status_line')->nullable()->after('colour');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('household_id');
            $table->dropColumn(['initials', 'colour', 'status_line']);
        });

        Schema::dropIfExists('households');
    }
};
