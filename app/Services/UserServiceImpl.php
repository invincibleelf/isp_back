<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 22/10/18
 * Time: 11:40 AM
 */

namespace App\Services;

use App\PayerDetail;
use App\Role;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class UserServiceImpl implements UserService
{


    public function createPayer($payer, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $payer->phone = $credentials['countryCode'] . $credentials['phone'];
        }
        $payer->email = $credentials['email'];
        $payer->password = bcrypt($credentials['password']);
        $payer->verified = true;
        $payer->status = Config::get('enums.status.ACTIVE');
        $payer->role()->associate((Role::where('name', 'payer')->first()));

        Log::info("Save Payer");
        $payer->save();

        $payerDetail = new PayerDetail();
        $payerDetail->firstname = $credentials['firstName'];
        $payerDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $payerDetail->lastname = $credentials['lastName'];
        $payerDetail->dob = $credentials['dob'];
        $payerDetail->gender = array_key_exists("gender", $credentials) ? $credentials['gender'] : null;
        $payerDetail->national_id = $credentials['nationalId'];
        $payerDetail->bank_account_number = $credentials['bankAccountNumber'];

        $payerDetail->student()->associate(Auth::user()->studentDetails);


        Log::info("Save Payer details ");
        $payer->payerDetails()->save($payerDetail);


        return $payer;
    }



    public function updatePayer($payer, $credentials)
    {

        if (array_key_exists($credentials['phone'], $credentials)) {
            $payer->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $payer->payerDetails->firstname = $credentials['firstName'];
        $payer->payerDetails->lastname = $credentials['lastName'];
        $payer->payerDetails->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $payer->payerDetails->dob = $credentials['dob'];
        $payer->payerDetails->gender = array_key_exists('gender', $credentials) ? $credentials['gender'] : null;
        $payer->payerDetails->bank_account_number = $credentials['bankAccountNumber'];
        $payer->payerDetails->national_id = $credentials['nationalId'];

        $payer->payerDetails->save();

        $payer->save();

        return $payer;
    }

    public function getFailureResponse($message, $code)
    {
        return [
            'success' => false,
            'message' => $message,
            'status_code' => $code
        ];
    }

    public function successMessage($message, $code)
    {
        return [
            'success' => true,
            'message' => $message,
            'status_code' => $code
        ];
    }
}