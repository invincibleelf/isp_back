<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 22/10/18
 * Time: 4:59 PM
 */

namespace App\Repositories;


use App\User;
use App\StudentDetail;
use App\PayerDetail;

class UserRepositoryImpl implements UserRepository
{


    public function getPayersByCurrentUser($currentUser)
    {
        $payers = User::with("payerDetails")->whereHas('payerDetails.student', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->studentDetails->id);
        })->get();

        return $payers;
    }

    public function getPayerByIdAndCurrentUser($id, $currentUser)
    {
        $payer = User::with("payerDetails")->whereHas("payerDetails.student", function ($q) use ($currentUser) {
            $q->where('id', $currentUser->studentDetails->id);
        })->find($id);

        return $payer;
    }
}