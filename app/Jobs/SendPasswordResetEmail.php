<?php

namespace App\Jobs;

use App\Mail\PasswordResetUserCreate;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $mailable;
    private $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($mailable,$email)
    {
        $this->mailable = $mailable;
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {


        Log::info("Send email to ".$this->email);
        Mail::to($this->email)->send($this->mailable);
    }
}
