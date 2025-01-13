<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $currentDate;
    public $name;
    public $email;

    public function __construct($currentDate,$name, $email)
    {
        $this->currentDate = $currentDate;
        $this->name =$name;
        $this->email =$email;

    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kobosquare Login Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'emails.login-notification',
    //     );
    // }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.login-notification',
            with: [
                'currentDate' => $this->currentDate,
                'name' => $this->name,
                'email' => $this->email
            ]
        );
    }



    public function attachments(): array
    {
        return [];
    }
}

