<?php

namespace App\Mail;

use App\Models\AuthorPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthorPayoutProcessedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AuthorPayout $payout) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '💸 Royalti kamu sudah ditransfer — bukudigi.com');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.author-payout-processed', with: ['payout' => $this->payout]);
    }
}
