<?php

namespace App\Mail;

use App\Models\Isf\Letter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArticleRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Letter $letter,
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Status Kurasi Artikel — Indonesia Strategy Forum (ISF)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.isf.article_rejection',
            with: [
                'letter' => $this->letter,
                'authorName' => $this->letter->sender_name ?: 'Penulis ISF',
                'articleTitle' => $this->letter->title,
                'reason' => $this->reason,
            ],
        );
    }
}

