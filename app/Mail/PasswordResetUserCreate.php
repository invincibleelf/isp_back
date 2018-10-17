<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetUserCreate extends Mailable
{
    use Queueable, SerializesModels;
    public $link;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $url, $token)
    {
        $this->link = $url . '/' . $token;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Password Reset')->view('emails.password-reset-user-create');
    }
}
