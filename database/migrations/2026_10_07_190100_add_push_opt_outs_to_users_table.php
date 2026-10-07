<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The notification categories someone has turned push off for. Every
        // category pushes by default, so null means "send everything".
        Schema::table('users', function (Blueprint $table): void {
            $table->json('push_opt_outs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('push_opt_outs');
        });
    }
};
