<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\PaletteEnum;
use App\Models\DocumentFolder;
use App\Models\Household;

class CreateDocumentFolderAction
{
    public function handle(Household $household, string $name): DocumentFolder
    {
        return $household->documentFolders()->create([
            'name' => $name,
            'icon' => '📁',
            'colour' => PaletteEnum::Mint,
            'position' => (int) $household->documentFolders()->max('position') + 1,
        ]);
    }
}
