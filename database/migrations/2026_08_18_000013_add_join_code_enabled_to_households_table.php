<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->boolean('join_code_enabled')->default(true)->after('join_code');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn('join_code_enabled');
        });
    }
};
