<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddRecipeIngredientsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'shopping_list_id' => ['required', 'integer', 'exists:shopping_lists,id'],
        ];
    }

    public function shoppingListId(): int
    {
        return (int) $this->validated('shopping_list_id');
    }
}
