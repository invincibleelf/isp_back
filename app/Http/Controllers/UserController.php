<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;


use App\Http\Resources\UserResourceCollection;
use App\StudentDetail;
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

                $fields = ['email', 'firstName', 'lastName', 'middleName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber', 'countryCode'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'firstName' => 'required|max:255',
                        'lastName' => 'required|max:255',
                        'middleName => max:255',
                        'dob' => 'required',
                        'countryCode' => 'required_with:phone|numeric',
                        'phone' => 'required_with:countryCode|numeric',
                        'nationalId' => 'required',
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

                Log::info("Validate mobile number");
                if (array_key_exists('code', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['code'], $credentials['phone']);

                    if (!$isValid) {
                        return response([
                            'success' => false,
                            'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                            'status_code' => 400
                        ]);

                    }
                }


                try {
                    DB::beginTransaction();
                    $currentUser = $this->updateStudentDetails($currentUser, $credentials);

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
                $fields = ['firstName', 'lastName', 'middleName', 'phone', 'nationalId', 'countryCode'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'firstName' => 'required|max:255',
                        'lastName' => 'required|max:255',
                        'middleName => max:255',
                        'countryCode' => 'required_with:phone|numeric',
                        'phone' => 'required_with:countryCode|numeric',
                        'nationalId' => 'required'
                    ]
                );
                if ($validator->fails()) {
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                Log::info("Validate mobile number");
                if (array_key_exists('code', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['code'], $credentials['phone']);

                    if (!$isValid) {
                        return response([
                            'success' => false,
                            'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                            'status_code' => 400
                        ]);

                    }
                }

                $currentUser = $this->updateCouncilorDetails($currentUser, $credentials);

                Log::info("Save Councilor with id " . $currentUser->id);
                $currentUser->save();
                break;

            case 'agent':
                $fields = ['agentName', 'location', 'phone', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'validBankOpening', 'countryCode'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'agentName' => 'required|max:255',
                        'countryCode' => 'required_with:phone|numeric',
                        'phone' => 'required_with:countryCode|numeric',
                        'nationalId' => 'required',
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

                Log::info("Validate mobile number");
                if (array_key_exists('code', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['code'], $credentials['phone']);

                    if (!$isValid) {
                        return response([
                            'success' => false,
                            'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                            'status_code' => 400
                        ]);

                    }
                }

                if (array_key_exists('phone', $credentials)) {
                    $currentUser->phone = $credentials['countryCode'] . $credentials['phone'];
                }

                $currentUser->agentDetails->name = $credentials['agentName'];
                $currentUser->agentDetails->national_id = $credentials['nationalId'];
                $currentUser->agentDetails->location = $credentials['location'];
                $currentUser->agentDetails->legal_registration_number = array_key_exists('legalRegistrationNumber', $credentials) ? $credentials['legalRegistrationNumber'] : null;
                $currentUser->agentDetails->bank_account_number = $credentials['bankAccountNumber'];
                $currentUser->agentDetails->bank_account_name = $credentials['bankAccountName'];
                $currentUser->agentDetails->valid_bank_opening = $credentials['validBankOpening'];

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

        $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->get();

        return new UserResourceCollection($students);

// Code Needed id Agents allowed to view students
//        if ($currentUser->role->name === "councilor") {
//            $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->councilorDetails->id);
//            })->get();
//
//
//        }
//        else if ($currentUser->role->name === "agent") {
//
//            $students = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->agentDetails->id);
//            })->get();
//
//        }


    }

    public function getStudent($id)
    {
        $currentUser = Auth::user();
        Log::info("Get Student with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->find($id);

// Code Needed id Agents allowed to view students
//        if ($currentUser->role->name === "councilor") {
//            $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->councilorDetails->id);
//            })->find($id);
//        } else if ($currentUser->role->name === "agent") {
//            $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->agentDetails->id);
//
//            })->find($id);
//        }

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Student with id " . $id . " doesn't exist for " . $currentUser->email,
                "status_code" => 404

            ]);
        } else {
            return new UserResource($student);
        }
    }


    public function updateStudent($id, Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Update student with id " . $id . " by " . $currentUser->role->name . " " . $currentUser->email);

        $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->find($id);

// Code Needed if Agents are allowed to update student details
//        if ($currentUser->role->name === "councilor") {
//            $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->councilorDetails->id);
//            })->find($id);
//
//        } else if ($currentUser->role->name === "agent") {
//            $student = User::with('studentDetails')->whereHas('studentDetails.councilor.agent', function ($q) use ($currentUser) {
//                $q->where('id', $currentUser->agentDetails->id);
//
//            })->find($id);
//        }

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email,
                "status_code" => 404
            ]);
        }

        $fields = ['firstName', 'lastName', 'middleName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber', 'countryCode'];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName => max:255',
                'dob' => 'required',
                'gender' => 'required',
                'countryCode' => 'required_with:phone|numeric',
                'phone' => 'required_with:countryCode|numeric',
                'nationalId' => 'required',
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

        Log::info("Validate mobile number");
        if (array_key_exists('code', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['code'], $credentials['phone']);

            if (!$isValid) {
                return response([
                    'success' => false,
                    'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                    'status_code' => 400
                ]);

            }
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
        if (array_key_exists($credentials['phone'], $credentials)) {
            $student->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $student->studentDetails->firstname = $credentials['firstName'];
        $student->studentDetails->lastname = $credentials['lastName'];
        $student->studentDetails->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $student->studentDetails->dob = $credentials['dob'];
        $student->studentDetails->gender = array_key_exists('gender', $credentials) ? $credentials['gender'] : null;
        $student->studentDetails->national_id = $credentials['nationalId'];
        $student->studentDetails->student_id_number = $credentials['studentIdNumber'];

        return $student;
    }

    protected function updateStudentBux($student)
    {
        Log::info("Update Student id " . $student->id . " with bux_id " . $student->studentDetails->bux_id . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->put(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . $student->studentDetails->bux_id, ['json' => Utilities::getJsonRequestForUpdateStudent($student)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;

    }

    public function deleteStudent($id)
    {

        $currentUser = Auth::user();
        Log::info("Delete student with id " . $id . " by councilor " . $currentUser->email);

        $student = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->councilorDetails->id);
        })->find($id);

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Student with id " . $id . " doesn't exist for " . $currentUser->role->name . " " . $currentUser->email,
                "status_code" => 404
            ]);
        }

        try {
            DB::beginTransaction();

            $student->status = Config::get('enums.status.DELETED');
            $student->save();

            $responseBux = $this->deleteStudentBux($student);

            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }

            DB::commit();


            return response([
                "status" => true,
                "status_code" => 200,
                "message" => "Councilor with id " . $student->id . " deleted successfully"
            ]);


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }

    }

    protected function deleteStudentBux($student)
    {
        Log::info("Delete Student id " . $student->id . " with bux_id " . $student->studentDetails->bux_id . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->delete(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . $student->studentDetails->bux_id);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;
    }


    public function getCouncilors()
    {
        $currentUser = Auth::user();
        Log::info("Get Councilors for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilors = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->get();

        return new UserResourceCollection($councilors);


    }

    public function getCouncilor($id)
    {
        $currentUser = Auth::user();
        Log::info("Get Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->find($id);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Councilor with id " . $id . " doesn't exist for " . $currentUser->email,
                "status_code" => 404

            ]);
        }

        return new UserResource($councilor);

    }

    public function updateCouncilor($id, Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Update Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->find($id);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Councilor with id " . $id . " doesn't exist for " . $currentUser->email,
                "status_code" => 404

            ]);
        }

        $fields = ['firstName', 'lastName', 'middleName', 'phone', 'nationalId', 'countryCode'];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName => max:255',
                'countryCode' => 'required_with:phone|numeric',
                'phone' => 'required_with:countryCode|numeric',
                'nationalId' => 'required'
            ]
        );
        if ($validator->fails()) {
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        Log::info("Validate mobile number");
        if (array_key_exists('code', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['code'], $credentials['phone']);

            if (!$isValid) {
                return response([
                    'success' => false,
                    'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                    'status_code' => 400
                ]);

            }
        }
        try {
            DB::beginTransaction();
            $councilor = $this->updateCouncilorDetails($councilor, $credentials);

            Log::info("Save Councilor with id " . $councilor->id);
            $councilor->save();
            $councilor->councilorDetails->save();

            DB::commit();

            return new UserResource($councilor);
        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }


    }

    protected function updateCouncilorDetails($councilor, $credentials)
    {
        $councilor->councilorDetails->firstname = $credentials['firstName'];
        $councilor->councilorDetails->lastname = $credentials['lastName'];
        $councilor->councilorDetails->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
        $councilor->councilorDetails->national_id = $credentials['nationalId'];

        if (array_key_exists('phone', $credentials)) {
            $councilor->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        return $councilor;
    }

    public function deleteCouncilor($id)
    {
        $currentUser = Auth::user();
        Log::info("Delete Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->find($id);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response([
                "success" => false,
                "message" => "Councilor with id " . $id . " doesn't exist for " . $currentUser->email,
                "status_code" => 404

            ]);
        }

        try {
            DB::beginTransaction();

            $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($councilor) {
                $q->where('id', $councilor->councilorDetails->id);
            })->get();

            if (!empty($students)) {
                return response([
                    "success" => false,
                    "message" => "Students Exists for councilor. Unable to delete councilor"
                ]);
            }

            $councilor->status = Config::get('enums.status.DELETED');
            $councilor->save();
            DB::commit();
            return response([
                "status" => true,
                "message" => "Councilor with id " . $councilor->id . " deleted successfully"
            ]);


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }


    }

    public function transferStudents(Request $request)
    {

        $fields = ["fromId", "toId"];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'fromId' => 'required|integer',
                'toId' => 'required|integer'
            ]
        );
        if ($validator->fails()) {
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        Log::info("Transfer Student from councilor with id " . $credentials['fromId'] . " to with id " . $credentials["toId"]);
        $currentUser = Auth::user();
        $status = Config::get("enums.status.ACTIVE");

        $oldCouncilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->where('status', '=', $status)->find($credentials['fromId']);

        $newCouncilor = User::with('councilorDetails')->whereHas('councilorDetails.agent', function ($q) use ($currentUser) {
            $q->where('id', $currentUser->agentDetails->id);
        })->where('verified', '=', true)->where('status', '=', $status)->find($credentials['toId']);

        if (!$oldCouncilor || !$newCouncilor) {
            Log::error("Councilor doesn't exists");
            return response([
                'success' => false,
                'message' => "Councilor doesn't exist",
                'status_code' => 400
            ]);
        }

        $students = User::with('studentDetails')->whereHas('studentDetails.councilor', function ($q) use ($oldCouncilor) {
            $q->where('id', $oldCouncilor->councilorDetails->id);
        })->get();

        if ($students->isEmpty()) {
            Log::error("Students doesn't exist for councilor with id " . $oldCouncilor->id);
            return response([
                'success' => false,
                'message' => "Students doesn't exist for councilor " . $oldCouncilor->email . " with id " . $oldCouncilor->id,
                'status_code' => 400
            ]);
        }

        try {
            DB::beginTransaction();

            Log::info("Update all the students with new councilor id " . $newCouncilor->councilorDetails->id);

            StudentDetail::with('councilor')->whereHas('councilor', function ($q) use ($oldCouncilor) {
                $q->where('id', $oldCouncilor->councilorDetails->id);
            })->update(["councilor_id" => $newCouncilor->councilorDetails->id]);

            DB::commit();

            return response([
                "success" => true,
                "status_code" => 200,
                "message" => "Students transfered successfully",
            ]);

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }

    }


}

