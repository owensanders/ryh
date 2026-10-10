<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Enums\PaletteEnum;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentRepository implements DocumentRepositoryInterface
{
    /** @return Collection<int, DocumentFolder> folders with a `documents_count` on each */
    public function foldersFor(Household $household): Collection
    {
        return $household->documentFolders()->withCount('documents')->get();
    }

    /** @return Collection<int, Document> */
    public function documentsFor(DocumentFolder $folder): Collection
    {
        return $folder->documents()->with('addedBy')->latest()->get();
    }

    public function createFolder(Household $household, string $name): DocumentFolder
    {
        $position = (int) $household->documentFolders()->max('position') + 1;

        return $household->documentFolders()->create([
            'name' => $name,
            'icon' => '📁',
            'colour' => PaletteEnum::Mint,
            'position' => $position,
        ]);
    }

    public function deleteFolder(DocumentFolder $folder): void
    {
        $paths = $folder->documents->pluck('path')->all();

        if ($paths !== []) {
            Storage::disk(config('documents.disk'))->delete($paths);
        }

        $folder->delete();
    }

    public function storeUpload(DocumentFolder $folder, UploadedFile $file, ?User $uploader): Document
    {
        $path = Storage::disk(config('documents.disk'))
            ->putFile("documents/{$folder->household_id}", $file);

        return $folder->documents()->create([
            'household_id' => $folder->household_id,
            'added_by' => $uploader?->id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            // Truncated defensively: mimes: validates content, not filename shape,
            // so an unusually long suffix (e.g. "report.pdfbackupcopy") could
            // otherwise overflow the column.
            'extension' => mb_substr($file->getClientOriginalExtension(), 0, 20),
            'size' => $file->getSize(),
        ]);
    }

    public function findFolder(int $id): ?DocumentFolder
    {
        return DocumentFolder::find($id);
    }

    public function moveDocument(Document $document, DocumentFolder $folder): Document
    {
        $document->update(['document_folder_id' => $folder->id]);

        return $document->refresh();
    }

    public function deleteDocument(Document $document): void
    {
        Storage::disk(config('documents.disk'))->delete($document->path);

        $document->delete();
    }

    public function totalSizeFor(Household $household): int
    {
        return (int) $household->documents()->sum('size');
    }
}
