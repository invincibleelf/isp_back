<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 23/10/18
 * Time: 10:13 AM
 */

namespace App\Services;


use App\Jobs\SendEmailJob;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordResetUserCreate;
use App\Mail\PaymentConfirmationMail;
use App\PasswordResets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailServiceImpl implements EmailService
{

    public function sendEmailToResetPassword($user, $url)
    {
        Log::info("Send password reset email to " . $user->email);
        PasswordResets::where('email', $user->email)->delete();

        $passwordReset = PasswordResets::create([
            'email' => $user->email,
            'token' => str_random(64)
        ]);

        $passwordResetMail = new PasswordResetMail($url, $passwordReset->token);


        Log::info("Dispatch Job for sending email");
        SendEmailJob::dispatch($passwordResetMail, $user->email);


        // Mail::to($passwordReset->email)->send(new PasswordResetUserCreate($user,$url, $passwordReset->token));
    }

    public function sendEmailToResetPasswordCreateUser($user, $url)
    {
        Log::info("Send password reset email to " . $user->email);
        PasswordResets::where('email', $user->email)->delete();

        $passwordReset = PasswordResets::create([
            'email' => $user->email,
            'token' => str_random(64)
        ]);

        $passwordResetMail = new PasswordResetUserCreate($user, $url, $passwordReset->token);

        Log::info("Dispatch Job for sending email");
        SendEmailJob::dispatch($passwordResetMail, $user->email);

    }

    public function sendEmailToConfirmPayment($user, $url)
    {
        $confirmPaymentMail = new PaymentConfirmationMail($user,$url);
        Log::info("Dispatch Job for sending email");
        SendEmailJob::dispatch($confirmPaymentMail, $user->email);
    }
}