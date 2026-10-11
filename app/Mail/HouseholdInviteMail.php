<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Household;
use App\Traits\RetriesEmailTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HouseholdInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, RetriesEmailTrait, SerializesModels;

    public function __construct(
        public readonly Household $household,
        public readonly string $actionUrl,
        public readonly bool $hasAccount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->hasAccount
                ? "You're invited to join {$this->household->name} on RYH"
                : "Join {$this->household->name} on RYH — get started",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.household-invite',
            with: [
                'householdName' => $this->household->name,
                'actionUrl' => $this->actionUrl,
                'hasAccount' => $this->hasAccount,
            ],
        );
    }
}
