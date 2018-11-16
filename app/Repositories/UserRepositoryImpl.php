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
use Illuminate\Support\Facades\Config;


class UserRepositoryImpl implements UserRepository
{


    public function getPayersByCurrentUser($currentUser)
    {
        $payers = User::with("payerDetails")->whereHas('payerDetails.student', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->studentDetails->id);
        })->where('status','=',Config::get('enums.status.ACTIVE'))->get();

        return $payers;
    }

    public function getPayerByIdAndCurrentUser($id, $currentUser)
    {
        $payer = User::with("payerDetails")->whereHas("payerDetails.student", function ($q) use ($currentUser) {
            $q->where('id', $currentUser->studentDetails->id);
        })->where('status','=',Config::get('enums.status.ACTIVE'))->find($id);

        return $payer;
    }

    public function getStudentsByCurrentUser($currentUser)
    {
        switch ($currentUser->role->name) {
            case 'councilor':
                $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                    $q->where('id', $currentUser->councilorDetails->id);
                })->get();
                break;

            case 'agent':
                $students = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                    $q->where('id', $currentUser->agentDetails->id);
                })->get();
                break;
        }

        return $students;
    }

    public function getStudentByIdAndCurrentUser($id, $currentUser)
    {
        switch ($currentUser->role->name) {
            case 'councilor':
                $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                    $q->where('id', $currentUser->councilorDetails->id);
                })->
                find($id);
                break;

            case 'agent':
                $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                    $q->where('id', $currentUser->agentDetails->id);
                })->
                find($id);
                break;

    }
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

    public function getVerifiedCouncilorByIdAndStatusAndCurrentAgent($id, $status, $currentAgent)
    {
        $councilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentAgent) {
            $q->where('id', $currentAgent->agentDetails->id);
        })->where('verified', '=', true)->where('status', '=', $status)->find($id);

        return $councilor;
    }

    public function transferStudents($oldCouncilor, $newCouncilor)
    {
        StudentDetail::with('councilor')->whereHas('councilor', function ($q) use ($oldCouncilor) {
            $q->where('id', $oldCouncilor->councilorDetails->id);
        })->update(["councilor_id" => $newCouncilor->councilorDetails->id]);
    }

    public function getStudentByPayer($payer)
    {
        $student = User::with('studentDetails')->whereHas('studentDetails',function ($q) use ($payer){
            $q->where('id',$payer->payerDetails->student_id);
        })->first();

        return $student;
    }


    public function getUserByStudentDetailsId($studentDetailsId){
        $student = User::with('studentDetails')->whereHas('studentDetails', function ($q) use ($studentDetailsId) {
            $q->where('id',$studentDetailsId);
        })->first();

        return $student;
    }


}