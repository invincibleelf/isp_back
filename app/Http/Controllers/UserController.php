<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;


use App\User;
use App\Utilities;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;



class UserController extends Controller
{
    public function showProfile(Request $request)
    {
        $user = Auth::user();

        Log::info("Show profile for current user  " . $user->email);

        return new UserResource($user);
    }

    public function updateProfile(Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Update Profile for user " . $currentUser->email);

        switch ($currentUser->role->name) {
            case 'student':

                $fields = ['email', 'firstName', 'lastName', 'middleName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'firstName' => 'required|max:255',
                        'lastName' => 'required|max:255',
                        'middleName => max:255',
                        'dob' => 'required',
                        'gender' => 'required',
                        'phone' => 'required',
                        'nationalId' => 'required|unique:student_details,national_id',
                        'studentIdNumber' => 'required'
                    ]
                );
                if ($validator->fails()) {
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                try {
                    DB::beginTransaction();
                    $currentUser->studentDetails->firstname = $credentials['firstName'];
                    $currentUser->studentDetails->lastname = $credentials['lastName'];
                    $currentUser->studentDetails->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
                    $currentUser->studentDetails->dob = $credentials['dob'];
                    $currentUser->studentDetails->gender = $credentials['gender'];
                    $currentUser->studentDetails->national_id = $credentials['nationalId'];
                    $currentUser->studentDetails->student_id_number = $credentials['studentIdNumber'];
                    $currentUser->phone = $credentials['phone'];

                    Log::info("Update User with id " . $currentUser->id);
                    $currentUser->save();
                    $currentUser->studentDetails->save();


                    Log::info("Update Student with id " .$currentUser->bux_id. "in BUX API " . Config::get('constants.bux_base_url'));

                    $buxAPI = new Client([
                        'base_uri' => Config::get('constants.bux_base_url'),
                        'timeout' => 2.0
                    ]);

                    Log::info("Request to Bux API");
                    $buxResponse = $buxAPI->put(Config::get('constants.bux_base_url') . Config::get('constants.bux_student').$currentUser->bux_id, ['json' => Utilities::getJsonRequestForUpdateStudent($currentUser)]);
                    //Get body of the response in JSON (Must use decode because of the bug )
                    $contents = json_decode($buxResponse->getBody());

                    if (!$contents->code) {
                        throw new \Exception("Error");
                    }



                    DB::commit();

                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['Error' => $e->getMessage()], 500);
                }

                break;

            case 'councilor':
                $fields = ['firstName', 'lastName', 'middleName', 'phone', 'nationalId'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'firstName' => 'required|max:255',
                        'lastName' => 'required|max:255',
                        'middleName => max:255',
                        'phone' => 'required',
                        'nationalId' => 'required|unique:student_details,national_id'
                    ]
                );
                if ($validator->fails()) {
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                $currentUser->councilorDetails->firstname = $credentials['firstName'];
                $currentUser->councilorDetails->lastname = $credentials['lastName'];
                $currentUser->councilorDetails->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
                $currentUser->councilorDetails->national_id = $credentials['nationalId'];
                $currentUser->phone = $credentials['phone'];

                $currentUser->save();
                break;

            case 'agent':
                $fields = ['agentName', 'location', 'phone', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'validBankOpening'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'agentName' => 'required|max:255',
                        'phone' => 'required',
                        'nationalId' => 'required|unique:agent_details,national_id',
                        'bankAccountNumber' => 'required',
                        'bankAccountName' => 'required',
                        'validBankOpening' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                $currentUser->phone = $credentials['phone'];
                $currentUser->agentDetails->name = $credentials['agentName'];
                $currentUser->agentDetails->national_id = $credentials['nationalId'];
                $currentUser->agentDetails->location = $credentials['location'];
                $currentUser->agentDetails->legal_registration_number = $credentials['legalRegistrationNumber'];
                $currentUser->agentDetails->bank_account_number = $credentials['bankAccountNumber'];
                $currentUser->agentDetails->bank_account_name = $credentials['bankAccountName'];
                $currentUser->agentDetails->valid_bank_open = $credentials['validBankOpening'];

                $currentUser->save();
                $currentUser->agentDetails->save();


                break;
            default:
                return response([
                    "success" => "false",
                    "status_code" => "401",
                    "message" => "Invalid User Role"
                ]);
                break;


        }
        return new UserResource($currentUser);
    }

    public function getStudents()
    {
        $currentUser = Auth::user();

        if ($currentUser->role->name === "councilor") {
            $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->councilorDetails->id);
            })->get();


        } else if ($currentUser->role->name === "agent") {

            $students = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->agentDetails->id);
            })->get();

        }


        return response([
            "students" => $students

        ]);
    }

    public function getStudent($id)
    {
        $currentUser = Auth::user();

        if ($currentUser->role->name === "councilor") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->councilorDetails->id);
            })->find($id);

            if ($student !== null) {
                return response(["student" => $student]);
            } else {
                return response([
                    "success" => false,
                    "message" => "Student doesn't exist",
                    "status_code" => 404

                ]);
            }
        } else if ($currentUser->role->name === "agent") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->agentDetails->id);

            })->find($id);

            if ($student !== null) {
                return Response(["student" => $student]);
            } else {
                return response([
                    "success" => false,
                    "message" => "Student doesn't exist",
                    "status_code" => 404

                ]);
            }
        }


    }

    public function updateStudent($id)
    {


        return response(['test' => "OK"]);
    }


}

