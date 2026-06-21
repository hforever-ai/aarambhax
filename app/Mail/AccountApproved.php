<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Aarambh Legal] Your account has been approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-approved',
            with: [
                'user' => $this->user,
                'loginUrl' => url('/login'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
