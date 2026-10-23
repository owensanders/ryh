<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentMoveRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'folder_id' => ['required', 'integer', 'exists:document_folders,id'],
        ];
    }

    public function folderId(): int
    {
        return (int) $this->validated('folder_id');
    }
}
