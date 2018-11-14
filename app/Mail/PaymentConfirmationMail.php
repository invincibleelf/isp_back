<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $link;

    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( $user,$link)
    {
        $this->user = $user;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Confirmation')->view('emails.payment-confirmation');
    }
}
