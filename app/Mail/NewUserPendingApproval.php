<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Aarambh Legal] New registration awaiting your approval — '.$this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-user-pending-approval',
            with: [
                'user' => $this->user,
                'approvalUrl' => url('/admin/users?tableFilters[status][value]=pending'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
