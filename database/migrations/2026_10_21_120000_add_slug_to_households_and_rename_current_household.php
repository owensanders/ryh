<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The household a request is for now comes from its URL (/h/{slug}/...), not
 * from the user row, so two tabs on two households can't cross over.
 * `current_household_id` stays only to pick where /app lands, so it's renamed
 * to say so.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table): void {
            $table->string('slug', 48)->nullable()->unique()->after('name');
        });

        // Plain queries rather than the model, so this keeps working whatever the model becomes.
        foreach (DB::table('households')->select(['id', 'name'])->orderBy('id')->cursor() as $household) {
            do {
                $slug = (Str::slug(Str::limit($household->name, 30, '')) ?: 'home').'-'.Str::lower(Str::random(4));
            } while (DB::table('households')->where('slug', $slug)->exists());

            DB::table('households')->where('id', $household->id)->update(['slug' => $slug]);
        }

        Schema::table('households', function (Blueprint $table): void {
            $table->string('slug', 48)->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('current_household_id', 'last_household_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('last_household_id', 'current_household_id');
        });

        Schema::table('households', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
