<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book, public string $reason)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Buku belum bisa kami publikasikan ⚠️');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.book-rejected',
            with: ['book' => $this->book, 'reason' => $this->reason]
        );
    }
}
