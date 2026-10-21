<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
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
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($user)
            ->post($this->hh('/document-folders'), ['name' => 'Insurance'])
            ->assertRedirect();

        $this->assertDatabaseHas('document_folders', ['household_id' => $user->household_id, 'name' => 'Insurance']);
    }

    #[Test]
    public function itRejectsAWhitespaceOnlyFolderName(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($user)
            ->post($this->hh('/document-folders'), ['name' => '   '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('document_folders', 0);
    }

    #[Test]
    public function itDeletesAFolderAndItsDocuments(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
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
            ->delete($this->hh("/document-folders/{$folder->id}"))
            ->assertRedirect(route('documents.index'));

        $this->assertDatabaseMissing('document_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function itUploadsAFileIntoAFolder(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Adult]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);

        $this->actingAs($user)
            ->post($this->hh("/document-folders/{$folder->id}/documents"), [
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
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
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
            ->get($this->hh("/documents/{$document->id}/download"))
            ->assertOk();

        $stranger = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $this->actingAs($stranger)
            ->get($this->hh("/documents/{$document->id}/download"))
            ->assertNotFound();
    }

    #[Test]
    public function onS3ADownloadSendsTheBrowserStraightToTheBucket(): void
    {
        config(['documents.disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path, \DateTimeInterface $expiration, array $options) => 'https://bucket.example/'.$path.'?disposition='.rawurlencode($options['ResponseContentDisposition']),
        );

        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $document = $folder->documents()->create([
            'household_id' => $owner->household_id,
            'name' => 'Deed.pdf',
            'path' => 'documents/1/deed.pdf',
            'extension' => 'pdf',
            'size' => 1024,
        ]);

        $this->actingAs($owner)
            ->get($this->hh("/documents/{$document->id}/download"))
            ->assertRedirect('https://bucket.example/documents/1/deed.pdf?disposition='.rawurlencode('attachment; filename=Deed.pdf'));
    }

    #[Test]
    public function itMovesADocumentToAnotherFolder(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $property = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $insurance = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Insurance']);
        $document = $this->documentIn($property, 'Policy.pdf');

        $this->actingAs($owner)
            ->patch($this->hh("/documents/{$document->id}/move"), ['folder_id' => $insurance->id])
            ->assertRedirect()
            ->assertSessionHas('toast', '“Policy.pdf” moved to Insurance');

        $this->assertSame($insurance->id, $document->fresh()->document_folder_id);
    }

    #[Test]
    public function itWontMoveADocumentIntoAnotherHouseholdsFolder(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $document = $this->documentIn($folder, 'Deed.pdf');

        $stranger = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $theirFolder = DocumentFolder::create(['household_id' => $stranger->household_id, 'name' => 'Theirs']);

        $this->actingAs($owner)
            ->patch($this->hh("/documents/{$document->id}/move"), ['folder_id' => $theirFolder->id])
            ->assertNotFound();

        $this->assertSame($folder->id, $document->fresh()->document_folder_id);
    }

    #[Test]
    public function itWontMoveAnotherHouseholdsDocument(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $document = $this->documentIn($folder, 'Deed.pdf');

        $stranger = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $theirFolder = DocumentFolder::create(['household_id' => $stranger->household_id, 'name' => 'Theirs']);

        $this->actingAs($stranger)
            ->patch($this->hh("/documents/{$document->id}/move"), ['folder_id' => $theirFolder->id])
            ->assertNotFound();

        $this->assertSame($folder->id, $document->fresh()->document_folder_id);
    }

    #[Test]
    public function movingADocumentNeedsAnExistingFolder(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $owner->household_id, 'name' => 'Property']);
        $document = $this->documentIn($folder, 'Deed.pdf');

        $this->actingAs($owner)->patch($this->hh("/documents/{$document->id}/move"), [])->assertSessionHasErrors('folder_id');
        $this->actingAs($owner)->patch($this->hh("/documents/{$document->id}/move"), ['folder_id' => 999999])->assertSessionHasErrors('folder_id');

        $this->assertSame($folder->id, $document->fresh()->document_folder_id);
    }

    #[Test]
    public function itDeletesADocument(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
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
            ->delete($this->hh("/documents/{$document->id}"))
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    #[Test]
    public function itComputesTheStorageMeterFromTotalDocumentSize(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'A.pdf', 'path' => 'a.pdf', 'extension' => 'pdf', 'size' => 1024 * 1024 * 1024,
        ]);

        $this->actingAs($user)
            ->get($this->hh('/documents'))
            ->assertInertia(fn ($page) => $page
                ->where('storageLabel', '1.0 GB of 20.0 GB')
            );
    }

    #[Test]
    public function itShowsTheStorageMeterInSmallerUnitsForSmallUploads(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $folder = DocumentFolder::create(['household_id' => $user->household_id, 'name' => 'Property']);
        $folder->documents()->create([
            'household_id' => $user->household_id,
            'name' => 'A.pdf', 'path' => 'a.pdf', 'extension' => 'pdf', 'size' => 900 * 1024,
        ]);

        $this->actingAs($user)
            ->get($this->hh('/documents'))
            ->assertInertia(fn ($page) => $page
                ->where('storageLabel', '900.0 KB of 20.0 GB')
            );
    }

    #[Test]
    public function teenAndChildRolesAreForbiddenFromEveryDocumentsRoute(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRoleEnum::Teen]);
        $child = User::factory()->create(['household_id' => $teen->household_id, 'role' => HouseholdRoleEnum::Child]);
        $folder = DocumentFolder::create(['household_id' => $teen->household_id, 'name' => 'Property']);
        $document = $this->documentIn($folder, 'Deed.pdf');

        foreach ([$teen, $child] as $user) {
            $this->actingAs($user)->get($this->hh('/documents'))->assertForbidden();
            $this->actingAs($user)->post($this->hh('/document-folders'), ['name' => 'Nope'])->assertForbidden();
            $this->actingAs($user)->delete($this->hh("/document-folders/{$folder->id}"))->assertForbidden();
            $this->actingAs($user)->patch($this->hh("/documents/{$document->id}/move"), ['folder_id' => $folder->id])->assertForbidden();
        }
    }

    private function documentIn(DocumentFolder $folder, string $name): Document
    {
        return $folder->documents()->create([
            'household_id' => $folder->household_id,
            'name' => $name,
            'path' => "documents/{$folder->household_id}/{$name}",
            'extension' => 'pdf',
            'size' => 1024,
        ]);
    }
}
