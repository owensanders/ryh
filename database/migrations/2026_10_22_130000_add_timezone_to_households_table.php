<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Where the household lives, so "today", the dashboard and the calendar
        // turn over at its midnight rather than UTC's. Households made before
        // this are all in the UK.
        Schema::table('households', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('Europe/London')->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
