<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->string('address')->nullable()->after('location');
            $table->json('settings')->nullable()->after('streak_days');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('adult')->after('household_id');
            $table->boolean('pending')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'pending']);
        });

        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn(['address', 'settings']);
        });
    }
};
