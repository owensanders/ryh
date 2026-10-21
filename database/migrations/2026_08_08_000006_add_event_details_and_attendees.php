<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->string('location')->nullable()->after('is_all_day');
            $table->text('notes')->nullable()->after('location');
            $table->dropColumn('who_label');
        });

        Schema::create('calendar_event_user', function (Blueprint $table): void {
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['calendar_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('who_label')->nullable()->after('is_all_day');
            $table->dropColumn(['ends_at', 'location', 'notes']);
        });
    }
};
