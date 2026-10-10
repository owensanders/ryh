<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Household;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent ahead of an annual renewal, as the Terms of Service promise. */
class RenewalReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Household $household,
        public readonly string $amountLabel,
        public readonly string $renewsOnLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your RYH subscription renews on {$this->renewsOnLabel}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.renewal-reminder',
            with: [
                'householdName' => $this->household->name,
                'amountLabel' => $this->amountLabel,
                'renewsOnLabel' => $this->renewsOnLabel,
                'billingUrl' => route('house.index'),
                'termsUrl' => route('terms'),
            ],
        );
    }
}
