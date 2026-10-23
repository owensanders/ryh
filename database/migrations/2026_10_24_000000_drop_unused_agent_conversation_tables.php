<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * laravel/ai's conversation storage was published with the package, but RYH's
 * agents don't remember conversations, so the tables were never used.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('agent_conversation_messages');
        Schema::dropIfExists('agent_conversations');
    }

    public function down(): void
    {
        $create = require base_path('vendor/laravel/ai/database/migrations/2026_01_11_000001_create_agent_conversations_table.php');
        $create->up();
    }
};
