<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->string('join_code')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn('join_code');
        });
    }
};
