<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CompanyOnboard extends Mailable
{
    use Queueable, SerializesModels;

    public $name, $token;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $token)
    {
        $this->name = $name;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address')),
            subject: 'Confirm Your Email to Access Your Account',
        );
    }

    private function generateOnboardLink(){
        return config('app.front.url').URL::temporarySignedRoute('onboard-logistics', now()->addMinutes(30) , [
                'token' => $this->token,
            ], false);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.company-onboard',
            with: [
                'url' => $this->generateOnboardLink(),
                'name' => $this->name
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
