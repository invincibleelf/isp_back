<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 23/10/18
 * Time: 10:13 AM
 */

namespace App\Services;


use App\Mail\PasswordResetUserCreate;
use App\PasswordResets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailServiceImpl implements EmailService
{

    public function sendEmailToResetPassword($user, $url)
    {
        Log::info("Send password reset email to ".$user->email);
        PasswordResets::where('email', $user->email)->delete();

        $passwordReset = PasswordResets::create([
            'email' => $user->email,
            'token' => str_random(64)
        ]);

        Mail::to($passwordReset->email)->send(new PasswordResetUserCreate($user,$url, $passwordReset->token));
    }
}