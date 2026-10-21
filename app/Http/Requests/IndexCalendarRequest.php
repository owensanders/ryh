<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexCalendarRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.date_format' => 'That month is not a month we recognise.',
        ];
    }

    /**
     * The default failedValidation() redirects to url()->previous(), which for a
     * bookmarked or shared link with no in-app referrer resolves to '/'. Redirect
     * to the bare route instead, so the page still renders — falling back to the
     * current month — with the error available to show.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('calendar.index')->withErrors($validator)->withInput(),
        );
    }
}
