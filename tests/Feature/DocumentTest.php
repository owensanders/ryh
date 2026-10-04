<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesAFolder(): void
    {
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);

        $this->actingAs($user)
            ->post('/document-folders', ['name' => 'Insurance'])
            ->assertRedirect();

        $this->assertDatabaseHas('document_folders', ['household_id' => $user->household_id, 'name' => 'Insurance']);
    }

    #[Test]
    public function itRejectsAWhitespaceOnlyFolderName(): void
    {
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);

        $this->actingAs($user)
            ->post('/document-folders', ['name' => '   '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('document_folders', 0);
    }

    #[Test]
    public function itDeletesAFolderAndItsDocuments(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $document = $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'Deed.pdf',
            'path' => 'documents/1/deed.pdf',
            'extension' => 'pdf',
            'size' => 1024,
        ]);
        Storage::disk('local')->put($document->path, 'contents');

        $this->actingAs($user)
            ->delete("/document-folders/{$folder->id}")
            ->assertRedirect(route('documents.index'));

        $this->assertDatabaseMissing('document_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function itUploadsAFileIntoAFolder(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRole::Adult]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);

        $this->actingAs($user)
            ->post("/document-folders/{$folder->id}/documents", [
                'file' => UploadedFile::fake()->create('deed.pdf', 500, 'application/pdf'),
            ])
            ->assertRedirect();

        $document = Document::query()->firstOrFail();
        $this->assertSame('deed.pdf', $document->name);
        $this->assertSame('pdf', $document->extension);
        $this->assertSame($user->id, $document->added_by);
        Storage::disk('local')->assertExists($document->path);
    }

    #[Test]
    public function itDownloadsADocumentButNotAnotherHouseholdsDocument(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $folder = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $document = $folder->documents()->create([
            'household_id' => $owner->household_id,
            'name' => 'Deed.pdf',
            'path' => 'documents/1/deed.pdf',
            'extension' => 'pdf',
            'size' => 1024,
        ]);
        Storage::disk('local')->put($document->path, 'contents');

        $this->actingAs($owner)
            ->get("/documents/{$document->id}/download")
            ->assertOk();

        $stranger = User::factory()->create(['role' => HouseholdRole::Owner]);
        $this->actingAs($stranger)
            ->get("/documents/{$document->id}/download")
            ->assertNotFound();
    }

    #[Test]
    public function itDeletesADocument(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $document = $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'Deed.pdf',
            'path' => 'documents/1/deed.pdf',
            'extension' => 'pdf',
            'size' => 1024,
        ]);
        Storage::disk('local')->put($document->path, 'contents');

        $this->actingAs($user)
            ->delete("/documents/{$document->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function itComputesTheStorageMeterFromTotalDocumentSize(): void
    {
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'A.pdf', 'path' => 'a.pdf', 'extension' => 'pdf', 'size' => 1024 * 1024 * 1024,
        ]);

        $this->actingAs($user)
            ->get('/documents')
            ->assertInertia(fn ($page) => $page
                ->where('storageLabel', '1.0 GB of 20.0 GB')
            );
    }

    #[Test]
    public function itShowsTheStorageMeterInSmallerUnitsForSmallUploads(): void
    {
        $user = User::factory()->create(['role' => HouseholdRole::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'A.pdf', 'path' => 'a.pdf', 'extension' => 'pdf', 'size' => 900 * 1024,
        ]);

        $this->actingAs($user)
            ->get('/documents')
            ->assertInertia(fn ($page) => $page
                ->where('storageLabel', '900.0 KB of 20.0 GB')
            );
    }

    #[Test]
    public function teenAndChildRolesAreForbiddenFromEveryDocumentsRoute(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRole::Teen]);
        $child = User::factory()->create(['household_id' => $teen->household_id, 'role' => HouseholdRole::Child]);
        $folder = DocumentFolder::create(['household_id' => $teen->household_id, 'name' => 'Property']);

        foreach ([$teen, $child] as $user) {
            $this->actingAs($user)->get('/documents')->assertForbidden();
            $this->actingAs($user)->post('/document-folders', ['name' => 'Nope'])->assertForbidden();
            $this->actingAs($user)->delete("/document-folders/{$folder->id}")->assertForbidden();
        }
    }
}
