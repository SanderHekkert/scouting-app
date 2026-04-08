<?php

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserInvitation $invitation) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Je bent uitgenodigd voor Scouting App',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
            with: [
                'acceptUrl' => route('invitations.accept', $this->invitation->token),
                'expiresAt' => optional($this->invitation->expires_at)?->format('d-m-Y H:i'),
                'logoDataUri' => $this->logoDataUri(),
            ],
        );
    }

    private function logoDataUri(): string
    {
        $path = public_path('images/logo.png');
        if (! is_file($path)) {
            return '';
        }

        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($path) ?: 'image/png')
            : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
