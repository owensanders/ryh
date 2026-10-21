<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->date('streak_last_active_date')->nullable()->after('streak_days');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn('streak_last_active_date');
        });
    }
};
