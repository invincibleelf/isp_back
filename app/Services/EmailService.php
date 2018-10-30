<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 23/10/18
 * Time: 10:13 AM
 */

namespace App\Services;


interface EmailService
{

    public function  sendEmailToResetPassword($user,$url);

    public function sendEmailToResetPasswordCreateUser($user,$url);

    public function sendEmailToConfirmPayment($user, $url);
}