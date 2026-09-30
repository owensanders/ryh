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
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('trial_claimed_at')->nullable();
        });

        // Anyone who already owns a household has had their free month.
        DB::table('users')
            ->whereIn('id', DB::table('household_user')->where('role', 'owner')->select('user_id'))
            ->update(['trial_claimed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('trial_claimed_at');
        });
    }
};
