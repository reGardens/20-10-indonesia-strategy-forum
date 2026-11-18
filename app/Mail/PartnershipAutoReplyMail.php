<?php

namespace App\Mail;

use App\Models\Isf\PartnershipProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnershipAutoReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PartnershipProposal $proposal
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Konfirmasi Permintaan Kemitraan — Indonesia Strategy Forum (ISF)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.isf.partnership_autoreply',
            with: [
                'proposal' => $this->proposal,
                'institutionName' => $this->proposal->institution_name,
                'picName' => $this->proposal->pic_name,
                'partnershipTypeLabel' => $this->proposal->partnership_type_label,
            ],
        );
    }
}

