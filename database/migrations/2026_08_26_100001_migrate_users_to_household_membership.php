<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replaces the single `household_id` FK on `users` with real many-to-many
     * membership via `household_user`, plus a `current_household_id` "which
     * one am I looking at right now" pointer. One combined migration rather
     * than a phased rollout — this app has no separate deploy step to protect
     * (Sail, single environment) and CLAUDE.md rules out compat shims.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('current_household_id')->nullable()->after('id')
                ->constrained('households')->nullOnDelete();
        });

        DB::table('users')->whereNotNull('household_id')->orderBy('id')
            ->each(function (object $user): void {
                DB::table('household_user')->insert([
                    'household_id' => $user->household_id,
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'pending' => $user->pending,
                    'pending_reason' => $user->pending_reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('users')->where('id', $user->id)
                    ->update(['current_household_id' => $user->household_id]);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['household_id']);
            $table->dropColumn(['household_id', 'role', 'pending', 'pending_reason']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('household_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('adult');
            $table->boolean('pending')->default(false);
            $table->string('pending_reason')->nullable();
        });

        DB::table('household_user')->orderBy('id')->each(function (object $membership): void {
            DB::table('users')->where('id', $membership->user_id)->update([
                'household_id' => $membership->household_id,
                'role' => $membership->role,
                'pending' => $membership->pending,
                'pending_reason' => $membership->pending_reason,
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['current_household_id']);
            $table->dropColumn('current_household_id');
        });
    }
};
