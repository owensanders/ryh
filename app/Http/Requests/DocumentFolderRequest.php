<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentFolderRequest extends FormRequest
{
    /** Trimmed first, so a whitespace-only name correctly fails `required`. */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the folder a name.',
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }
}
