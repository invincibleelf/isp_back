<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $link;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url,$token)
    {
        $this->link = $url.'/'.$token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Password Reset')->view('emails.password-reset');
    }
}
