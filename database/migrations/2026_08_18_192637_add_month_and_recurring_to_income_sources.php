<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('income_sources', function (Blueprint $table): void {
            $table->date('month')->nullable()->after('meta');
            $table->boolean('is_recurring')->default(false)->after('month');
        });

        DB::table('income_sources')->whereNull('month')->update([
            'month' => CarbonImmutable::now()->startOfMonth()->toDateString(),
        ]);
    }

    public function down(): void
    {
        Schema::table('income_sources', function (Blueprint $table): void {
            $table->dropColumn(['month', 'is_recurring']);
        });
    }
};
