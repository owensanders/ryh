<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexMealPlannerRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'week' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'week.date_format' => 'That week is not a date we recognise.',
        ];
    }

    /**
     * The default failedValidation() redirects to url()->previous(), which for a
     * bookmarked or shared link with no in-app referrer resolves to '/'. Redirect
     * to the bare route instead, so the page still renders — falling back to the
     * current week — with the error available to show.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('meals.index')->withErrors($validator)->withInput(),
        );
    }
}
