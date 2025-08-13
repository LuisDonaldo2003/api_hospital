<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class RecoveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de contraseña - Hospital IMSS Bienestar'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recovery-code',
            with: [
                'user' => $this->user,
                'code' => $this->user->recovery_code,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
