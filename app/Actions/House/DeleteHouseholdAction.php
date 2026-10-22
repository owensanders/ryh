<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A household and everything in it. Its rows cascade from the household;
 * its uploaded documents live outside the database, under
 * documents/{household}, so that folder goes too, once the delete commits.
 * Stopping its subscription is the caller's job, before this.
 */
class DeleteHouseholdAction
{
    public function handle(Household $household): void
    {
        $documents = "documents/{$household->id}";

        $household->delete();

        DB::afterCommit(fn () => Storage::disk(config('documents.disk'))->deleteDirectory($documents));
    }
}
