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

/** Sent when a renewal payment fails, as the Terms of Service promise. */
class PaymentFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, RetriesEmailTrait, SerializesModels;

    public function __construct(public readonly Household $household) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We couldn't take your RYH payment");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-failed',
            with: [
                'householdName' => $this->household->name,
                'billingUrl' => route('house.index', ['household' => $this->household->slug]),
            ],
        );
    }
}
