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

    public function getStudentsByCurrentUser($currentUser)
    {
        $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->get();

        return $students;
    }

    public function getStudentByIdAndCurrentUser($id, $currentUser)
    {
        $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->find($id);

        return $student;
    }

    public function getCouncilorsByCurrentUser($currentUser)
    {
        $councilors = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->get();

        return $councilors;


    }

    public function getCouncilorByIdAndCurrentUser($id, $currentUser)
    {
        $councilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->find($id);

        return $councilor;
    }

}