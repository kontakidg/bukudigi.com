<?php

namespace App\Mail;

use App\Models\Author;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthorRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Author $author)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pendaftaran penulis sedang di-review ⏳');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.author-registered', with: ['author' => $this->author]);
    }
}
