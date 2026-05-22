<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bukumu sudah LIVE di bukudigi.com 🎉');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.book-approved', with: ['book' => $this->book]);
    }
}
