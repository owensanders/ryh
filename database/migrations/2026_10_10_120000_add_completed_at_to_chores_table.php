<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When a chore was ticked off, so completed chores can be cleared out
        // after a while instead of loading on every board forever.
        Schema::table('chores', function (Blueprint $table): void {
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->index(['status', 'completed_at']);
        });

        // Chores already completed start their clock now, rather than all
        // disappearing at once on the first prune.
        DB::table('chores')->where('status', 'done')->update(['completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('chores', function (Blueprint $table): void {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropColumn('completed_at');
        });
    }
};
