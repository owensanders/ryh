<?php

declare(strict_types=1);

namespace App\ViewModels\Documents;

use App\Data\DocumentData;
use App\Data\DocumentFolderData;
use App\Data\DocumentsScreenData;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Household;
use Illuminate\Support\Collection;

class DocumentsViewModel
{
    public function toData(Household $household, ?DocumentFolder $active): DocumentsScreenData
    {
        $folders = $household->documentFolders()->withCount('documents')->get();
        $active ??= $folders->first();

        /** @var Collection<int, Document> $activeDocuments */
        $activeDocuments = $active !== null ? $active->documents()->with('addedBy')->latest()->get() : collect();

        $totalBytes = (int) $household->documents()->sum('size');
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
