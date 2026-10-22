<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Documents\DeleteDocumentAction;
use App\Enums\HouseholdRoleEnum;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/** Stored files and document rows stay in step: no row without its file, and no file left behind for long. */
class DocumentFilesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aDocumentsFileIsOnlyDeletedOnceItsRowIsGoneForGood(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $document = $this->storedDocument(DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Car']), 'mot.pdf');

        try {
            DB::transaction(function () use ($document): void {
                app(DeleteDocumentAction::class)->handle($document);

                throw new RuntimeException('Something later in the same transaction failed.');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        Storage::disk('local')->assertExists($document->path);

        // The rolled-back delete left this instance believing it was gone (fresh() would return null), so load it again.
        app(DeleteDocumentAction::class)->handle(Document::query()->findOrFail($document->id));

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function deletingTheirAccountAsSoleOwnerDeletesTheHouseholdsFilesAndNoOneElses(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $neighbour = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $theirs = $this->storedDocument(DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Car']), 'mot.pdf');
        $notTheirs = $this->storedDocument(DocumentFolder::create(['household_id' => $neighbour->household_id, 'name' => 'Car']), 'mot.pdf');

        $this->actingAs($owner)->delete('/settings/profile', ['password' => 'password'])->assertRedirect();

        Storage::disk('local')->assertMissing($theirs->path);
        $this->assertSame([], Storage::disk('local')->allFiles("documents/{$owner->household_id}"));
        Storage::disk('local')->assertExists($notTheirs->path);
    }

    #[Test]
    public function leavingAsSoleOwnerDeletesTheHouseholdsFiles(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $document = $this->storedDocument(DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Car']), 'mot.pdf');

        $this->actingAs($owner)
            ->delete('/settings/households/current', ['household_id' => $owner->household_id])
            ->assertRedirect();

        $this->assertDatabaseMissing('households', ['id' => $owner->household_id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function pruningDeletesOldFilesNothingPointsToAndNothingElse(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $kept = $this->storedDocument(DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Car']), 'mot.pdf');
        $disk = Storage::disk('local');

        $oldOrphan = "documents/{$user->household_id}/crashed-upload.pdf";
        $freshOrphan = "documents/{$user->household_id}/uploading-right-now.pdf";
        $disk->put($oldOrphan, 'pdf');
        $disk->put($freshOrphan, 'pdf');
        $aDayAndABitAgo = now()->subHours(25)->getTimestamp();
        touch($disk->path($oldOrphan), $aDayAndABitAgo);
        touch($disk->path($kept->path), $aDayAndABitAgo);

        $this->artisan('documents:prune-orphans')
            ->expectsOutput('Deleted 1 orphaned document files.')
            ->assertSuccessful();

        $disk->assertMissing($oldOrphan);
        $disk->assertExists($freshOrphan);
        $disk->assertExists($kept->path);
    }

    private function storedDocument(DocumentFolder $folder, string $name): Document
    {
        $path = "documents/{$folder->household_id}/".uniqid().'-'.$name;
        Storage::disk('local')->put($path, 'pdf');

        return $folder->documents()->create([
            'household_id' => $folder->household_id,
            'name' => $name,
            'path' => $path,
            'extension' => 'pdf',
            'size' => 3,
        ]);
    }
}
