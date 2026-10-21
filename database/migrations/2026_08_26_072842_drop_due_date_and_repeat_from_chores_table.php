<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('chores')->where('status', 'upcoming')->update(['status' => 'today']);

        Schema::table('chores', function (Blueprint $table): void {
            $table->dropColumn(['due_date', 'repeat']);
        });
    }

    public function down(): void
    {
        Schema::table('chores', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('name');
            $table->string('repeat')->default('none')->after('due_date');
        });

        DB::table('chores')->update(['due_date' => now()->toDateString()]);
    }
};
