<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Aarambh Legal] Registration not approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-rejected',
            with: ['user' => $this->user],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
