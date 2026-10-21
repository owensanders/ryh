<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chores', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('name');
            $table->string('repeat')->default('none')->after('due_date');
        });

        DB::table('chores')->where('status', 'recurring')->update(['status' => 'today']);

        // Backfill a due date consistent with each row's existing column, so a chore
        // sitting in Upcoming doesn't suddenly render as "Due today" after the migration.
        DB::table('chores')->whereIn('status', ['today', 'done'])->update(['due_date' => now()->toDateString()]);
        DB::table('chores')->where('status', 'upcoming')->update(['due_date' => now()->addDay()->toDateString()]);

        Schema::table('chores', function (Blueprint $table): void {
            $table->date('due_date')->nullable(false)->change();
            $table->dropColumn(['due_label', 'repeat_label']);
        });
    }

    public function down(): void
    {
        Schema::table('chores', function (Blueprint $table): void {
            $table->string('due_label')->nullable();
            $table->string('repeat_label')->nullable();
            $table->dropColumn(['due_date', 'repeat']);
        });
    }
};
