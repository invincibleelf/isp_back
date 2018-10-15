<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;


use App\Http\Resources\UserResourceCollection;
use App\User;
use App\Utilities;
use Illuminate\Http\Response;
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
                    $currentUser = $this->updateStudentDetails($currentUser);

                    Log::info("Update User with id " . $currentUser->id);
                    $currentUser->save();
                    $currentUser->studentDetails->save();

                    $responseBux = $this->updateStudentBux($currentUser);


                    if (!$responseBux->code) {
                        throw new \Exception("Error from Bux API");
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

        Log::info("Get Students for " . $currentUser->role->name . " with email " . $currentUser->email);

        if ($currentUser->role->name === "councilor") {
            $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->councilorDetails->id);
            })->get();


        } else if ($currentUser->role->name === "agent") {

            $students = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->agentDetails->id);
            })->get();

        }


        return new UserResourceCollection($students);
    }

    public function getStudent($id)
    {
        $currentUser = Auth::user();
        Log::info("Get Student with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        if ($currentUser->role->name === "councilor") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->councilorDetails->id);
            })->find($id);
        } else if ($currentUser->role->name === "agent") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->agentDetails->id);

            })->find($id);
        }

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Student with id " . $id . " doesn't exist for " . $currentUser->email,
                "status_code" => 404

            ]);
        } else {
            return new UserResource($currentUser);
        }
    }


    public function updateStudent($id, Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Update student with id " . $id . " by " . $currentUser->role->name . " " . $currentUser->email);

        if ($currentUser->role->name === "councilor") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->councilorDetails->id);
            })->find($id);

        } else if ($currentUser->role->name === "agent") {
            $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
                $q->where('id', $currentUser->agentDetails->id);

            })->find($id);
        }

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email,
                "status_code" => 404
            ]);
        }

        $fields = ['firstName', 'lastName', 'middleName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber'];
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
            $student = $this->updateStudentDetails($student, $credentials);

            Log::info("Update Student with id " . $student->id);
            $student->save();
            $student->studentDetails->save();

            $responseBux = $this->updateStudentBux($student);


            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }

            DB::commit();

            return new UserResource($student);


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database" . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }


    }


    protected function updateStudentDetails($student, $credentials)
    {
        $student->phone = $credentials['phone'];
        $student->studentDetails->firstname = $credentials['firstName'];
        $student->studentDetails->lastname = $credentials['lastName'];
        $student->studentDetails->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
        $student->studentDetails->dob = $credentials['dob'];
        $student->studentDetails->gender = $credentials['gender'];
        $student->studentDetails->national_id = $credentials['nationalId'];
        $student->studentDetails->student_id_number = $credentials['studentIdNumber'];

        return $student;
    }

    protected function updateStudentBux($student)
    {
        Log::info("Update Student id " . $student->id . " with bux_id " . $student->bux_id . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->put(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . $student->bux_id, ['json' => Utilities::getJsonRequestForUpdateStudent($student)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;

    }

    public function deleteStudent($id)
    {

        return response(['test' => "OK"]);
    }

}

