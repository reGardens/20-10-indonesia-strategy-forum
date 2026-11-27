<?php

namespace App\Mail;

use App\Models\Isf\MemberReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberReportNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemberReport $report
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Laporan Member ISF] #{$this->report->id}: {$this->report->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-report-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
