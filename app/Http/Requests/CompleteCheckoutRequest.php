<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Coming back from Stripe Checkout, which adds the session it finished. */
class CompleteCheckoutRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'session_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'in:house'],
        ];
    }

    public function sessionId(): ?string
    {
        $id = $this->validated('session_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Started from the House tab, so they go back there. */
    public function fromHouse(): bool
    {
        return $this->validated('from') === 'house';
    }
}
