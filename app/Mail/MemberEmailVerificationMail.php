<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
        public int $expiresInHours = 24,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Alamat Email Anda — Indonesia Strategy Forum (ISF)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.isf.member_verification',
            with: [
                'user' => $this->user,
                'userName' => $this->user->name ?: ($this->user->first_name . ' ' . $this->user->last_name),
                'verificationUrl' => $this->verificationUrl,
                'expiresInHours' => $this->expiresInHours,
            ],
        );
    }
}

