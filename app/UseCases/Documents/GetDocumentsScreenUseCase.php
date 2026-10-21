<?php

declare(strict_types=1);

namespace App\UseCases\Documents;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Data\DocumentData;
use App\Data\DocumentFolderData;
use App\Data\DocumentsScreenData;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Household;
use Illuminate\Support\Collection;

class GetDocumentsScreenUseCase
{
    public function __construct(private readonly DocumentRepositoryInterface $documents) {}

    public function execute(Household $household, ?DocumentFolder $active): DocumentsScreenData
    {
        $folders = $this->documents->foldersFor($household);
        $active ??= $folders->first();

        /** @var Collection<int, Document> $activeDocuments */
        $activeDocuments = $active !== null ? $this->documents->documentsFor($active) : collect();

        $totalBytes = $this->documents->totalSizeFor($household);
        $quotaBytes = (int) config('documents.quota_bytes');

        return new DocumentsScreenData(
            folders: $folders
                ->map(fn (DocumentFolder $folder) => DocumentFolderData::fromModel($folder, $folder->documents_count))
                ->values()
                ->all(),
            active: $active !== null ? DocumentFolderData::fromModel($active, $activeDocuments->count()) : null,
            documents: $activeDocuments->map(DocumentData::fromModel(...))->values()->all(),
            storageLabel: $this->formatBytes($totalBytes).' of '.$this->formatBytes($quotaBytes),
            storagePct: $quotaBytes > 0 ? max(2, (int) round($totalBytes / $quotaBytes * 100)) : 0,
        );
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = max($bytes, 0);
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return number_format($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
