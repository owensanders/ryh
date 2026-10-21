<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Evidence for the Terms' rule that under-18s only join with a parent or
        // guardian's agreement: who confirmed it for a Teen or Child member, and when.
        Schema::table('household_user', function (Blueprint $table): void {
            $table->foreignId('guardian_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('guardian_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('household_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('guardian_confirmed_by');
            $table->dropColumn('guardian_confirmed_at');
        });
    }
};
