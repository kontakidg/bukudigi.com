<?php

namespace App\Mail;

use App\Models\Author;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthorVerifiedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Author $author)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Akun penulis kamu sudah aktif 🎉');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.author-verified', with: ['author' => $this->author]);
    }
}
