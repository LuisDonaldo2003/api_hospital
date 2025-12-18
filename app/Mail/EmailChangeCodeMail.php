<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailChangeCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    /**
     * Asunto del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código para cambiar tu correo electrónico'
        );
    }

    /**
     * Vista del correo y datos pasados.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-code',
            with: [
                'user' => $this->user,
                'code' => $this->user->email_change_code,
                'new_email' => $this->user->new_email_request,
            ],
        );
    }

    /**
     * Archivos adjuntos (en este caso ninguno).
     */
    public function attachments(): array
    {
        return [];
    }
}
