<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\Services\UserService;
use App\StudentDetail;
use App\User;
use App\Utilities;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class UserController extends Controller
{

    private $userRepository;

    private $userService;

    private $emailService;

    /*
     * Constructor injection of  services and repositories
     */

    public function __construct(UserRepository $userRepository, UserService $userService, EmailService $emailService)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->emailService = $emailService;
    }

    public function showProfile(Request $request)
    {
        $currentUser = Auth::user();

        Log::info("Show profile for current user " . $currentUser->email);

        return response(new UserResource($currentUser));
    }

    public function updateProfile(Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Update Profile for current user " . $currentUser->email . " with id " . $currentUser->id);

        switch ($currentUser->role->name) {
            case 'student':

                $fields = ['email', 'firstName', 'lastName', 'middleName', 'chineseName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber', 'countryCode'];
                $credentials = $request->only($fields);

                $validator = Validator::make(
                    $credentials,
                    [
                        'firstName' => 'required|max:255',
                        'lastName' => 'required|max:255',
                        'middleName' => 'max:255',
                        'chineseName' => 'max:255',
                        'dob' => 'required',
                        'countryCode' => 'required_with:phone|numeric',
                        'phone' => 'required_with:countryCode|numeric',
                        'nationalId' => 'required',
                        'studentIdNumber' => 'required'
                    ]
                );
                if ($validator->fails()) {
                    Log::error("Validation Error");
                    return response($this->userService->getFailureResponse($validator->messages(), '400'));
                }


                Log::info("Validate mobile number");
                if (array_key_exists('countryCode', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
                    if (!$isValid) {
                        Log::error("Phone number " . $credentials['phone'] . " is not valid ");
                        return response($this->userService->getFailureResponse("Phone number " . $credentials['phone'] . " is not valid ", 400));

                    }
                }


                try {

                    DB::beginTransaction();
                    $currentUser = $this->userService->updateStudent($currentUser, $credentials);

                    $responseBux = $this->userService->updateStudentAtBux($currentUser);

                    if (!$responseBux->code) {
                        Log::error("Error from Bux API");
                        throw new \Exception("Error from bux API");
                    }

                    DB::commit();

                    return response(new UserResource($currentUser));


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
                    Log::error("Validation Error");
                    return response($this->userService->getFailureResponse($validator->messages(), '400'));
                }


                Log::info("Validate mobile number");
                if (array_key_exists('countryCode', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
                    if (!$isValid) {
                        Log::error("Phone number " . $credentials['phone'] . " is not valid ");
                        return response($this->userService->getFailureResponse("Phone number " . $credentials['phone'] . " is not valid ", 400));

                    }
                }

                try {

                    DB::beginTransaction();

                    $councilor = $this->userService->updateCouncilor($currentUser, $credentials);

                    DB::commit();

                    return response(new UserResource($councilor));
                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['Error' => $e->getMessage()], 500);
                }

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
                    Log::error("Validation Error");
                    return response($this->userService->getFailureResponse($validator->messages(), '400'));
                }


                Log::info("Validate mobile number");
                if (array_key_exists('countryCode', $credentials)) {
                    $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
                    if (!$isValid) {
                        Log::error("Phone number " . $credentials['phone'] . " is not valid ");
                        return response($this->userService->getFailureResponse("Phone number " . $credentials['phone'] . " is not valid ", 400));

                    }
                }

                try {

                    DB::beginTransaction();

                    $currentUser = $this->userService->updateAgent($currentUser, $credentials);

                    DB::commit();

                    return response(new UserResource($currentUser));

                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['Error' => $e->getMessage()], 500);
                }

                break;

            //TODO Logic if needed for other roles
            default:
                return response([
                    "success" => "false",
                    "status_code" => "401",
                    "message" => "Invalid User Role"
                ]);
                break;


        }

    }

    //TODO Move this method to CouncilorController

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

