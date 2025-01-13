<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class promoteMail extends Mailable
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
    public $proBusiness;
    public $planName;
    public $duration;
    public $created_at;
    public $expiry_date;

    public function __construct($currentDate,$email,$name, $msg,$subject,$trans_ref,$paymentStatus,$method,$proBusiness,$total,$planName,$duration,$created_at, $expiry_date)
    {
        $this->currentDate = $currentDate;
        $this->email =$email;
        $this->msg =$msg;
        $this->name =$name;
        $this->subject=$subject;
        $this->trans_ref=$trans_ref;
        $this->proBusiness=$proBusiness;
        $this->planName=$planName;
        $this->method = $method;
        $this->duration= $duration;
        $this->created_at= $created_at;
        $this->expiry_date= $expiry_date;
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
            markdown: 'emails.promote',
            with: [
                'currentDate' => $this->currentDate,
                'email' => $this->email,
                'name' => $this->name,
                'msg' => $this->msg,
                'subject' => $this->subject,
                'trans_ref'=>$this->trans_ref,
                "proBusiness"=>$this->proBusiness,
                'duration'=>$this->duration,
                'method'=>$this->method,
                'duration'=>$this->duration,
                'total'=>$this->total,
                'paymentStatus'=>$this->paymentStatus,
                'created_at'=>$this->created_at,
                'expiry_date'=>$this->expiry_date,
                'planName'=>$this->planName
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
