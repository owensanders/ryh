<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Inputs\PendingInviteInput;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Opening the sign-up page: from the landing page with an email to fill in,
 * or from a household invite's signed link. The invite is kept in the
 * session until they register, rather than in a hidden form field they
 * could change.
 */
class ShowRegisterRequest extends FormRequest
{
    /**
     * Nothing to validate: a stray or malformed value is ignored, not refused.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /** The landing page's email, to fill in. */
    public function email(): string
    {
        $email = $this->query('email');

        return is_string($email) ? $email : '';
    }

    /** Remembers a signed invite link's household and role for RegisterRequest::pendingInvite(). */
    public function rememberInvite(): void
    {
        $household = $this->query('invite_household');

        // Signed, so the values are as the invite wrote them.
        if (! $this->hasValidSignature() || ! is_numeric($household)) {
            return;
        }

        $guardian = $this->query('invite_guardian');
        $guardianAt = $this->query('invite_guardian_at');

        $this->session()->put(PendingInviteInput::SESSION_KEY, PendingInviteInput::from([
            'householdId' => (int) $household,
            'role' => (string) $this->query('invite_role'),
            'guardianId' => is_numeric($guardian) ? (int) $guardian : null,
            'guardianConfirmedAt' => is_numeric($guardianAt) ? CarbonImmutable::createFromTimestamp((int) $guardianAt) : null,
        ]));
    }

    /** Invited members may be children joining a parent's household, so the terms box words its age statement for them. */
    public function isInvited(): bool
    {
        return $this->session()->get(PendingInviteInput::SESSION_KEY) instanceof PendingInviteInput;
    }
}
