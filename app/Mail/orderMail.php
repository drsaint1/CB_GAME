<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class orderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $currentDate;
    public $email;
    public $msg;
    public $subject;
    public $name;
    public $method;
    public $orderno;
    public $type;
    public $trans_ref;
    public $merchantName;
    public $total;
    public $paymentStatus;

    public function __construct($currentDate,$email,$name, $msg,$subject,$trans_ref,$orderno,$type,$method,$merchantName,$total,$paymentStatus)
    {
        $this->currentDate = $currentDate;
        $this->email =$email;
        $this->msg =$msg;
        $this->name =$name;
        $this->subject=$subject;
        $this->trans_ref=$trans_ref;
        $this->orderno=$orderno;
        $this->type=$type;
        $this->method = $method;
        $this->merchantName= $merchantName;
        $this->total = $total;
        $this->paymentStatus = $paymentStatus;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject:$this->subject,
        );
    }

    /**
     * Get the message content definition.
     */

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order',
            with: [
                'currentDate' => $this->currentDate,
                'email' => $this->email,
                'name' => $this->name,
                'msg' => $this->msg,
                'subject' => $this->subject,
                'trans_ref'=>$this->trans_ref,
                "orderno"=>$this->orderno,
                'type'=>$this->type,
                'method'=>$this->method,
                'merchantName'=>$this->merchantName,
                'total'=>$this->total,
                'paymentStatus'=>$this->paymentStatus

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
