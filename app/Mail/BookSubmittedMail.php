<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Buku kamu sudah disubmit & lagi di-review 📖');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.book-submitted', with: ['book' => $this->book]);
    }
}
