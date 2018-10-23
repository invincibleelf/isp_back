<?php

namespace App\Http\Controllers;

use App\AgentDetail;
use App\Http\Resources\LoginResource;
use App\Repositories\UserRepository;
use App\Role;
use App\Services\EmailService;
use App\Services\UserService;
use App\User;
use App\StudentDetail;
use App\Utilities;


use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;


class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    use RegistersUsers;

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

    public function register(Guard $auth, Request $request)
    {

        Log::info("Initlaize User Registration with email : " . $request['email'] . "and Role :" . $request['role']);

        $role = Role::where('name', $request['role'])->first();

        if ($role === null) {
            return response($this->userService->getFailureResponse("Invalid role for registration", 400));
        }


        switch ($role->name) {
            case "student":

                $fields = ['firstName', 'lastName', 'middleName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'role', 'studentIdNumber', 'countryCode'];
                // grab credentials from the request
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
                        'email' => 'required|email|max:255|unique:login_users_c',
                        'nationalId' => 'required',
                        'password' => 'required|min:6',
                        'confirmPassword' => 'required_with:password|same:password',
                        'role' => 'required',
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
                    $student = new User();
                    $student = $this->userService->createStudent($student, $credentials);

                    $responseBux = $this->userService->createStudentAtBux($student);

                    if (!$responseBux->code) {
                        Log::error("Error from Bux API");
                        throw new \Exception("Error from bux API");
                    }

                    $student->studentDetails->bux_id = $responseBux->details->id;
                    $student->studentDetails->save();

                    DB::commit();

                    return response([
                        "success" => true,
                        "status_code" => 200,
                        "email" => $student->email,
                        "role" => $student->role->name
                    ]);
                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['error' => $e->getMessage()], 500);
                }

                break;

            case "agent":
                $fields = ['agentName', 'location', 'email', 'phone', 'password', 'confirmPassword', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'validBankOpening', 'role', 'countryCode'];
                // grab credentials from the request
                $credentials = $request->only($fields);
                $validator = Validator::make(
                    $credentials,
                    [
                        'agentName' => 'required|max:255',
                        'countryCode' => 'required_with:phone|numeric',
                        'phone' => 'required_with:countryCode|numeric',
                        'email' => 'required|email|max:255|unique:login_users_c',
                        'nationalId' => 'required',
                        'password' => 'required|min:6',
                        'confirmPassword' => 'required_with:password|same:password',
                        'bankAccountNumber' => 'required',
                        'bankAccountName' => 'required',
                        'validBankOpening' => 'required',
                        'role' => 'required'
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
                    $agent = new User();

                    $user = $this->userService->createAgent($agent,$credentials);

                    DB::commit();

                    return response([
                        "success" => true,
                        "status_code" => 200,
                        "email" => $agent->email,
                        "role" => $agent->role->name
                    ]);

                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['error' => $e->getMessage()], 500);
                }

                break;

            default:
                Log::error("Invalid Role for registration");
                return response([
                    'success' => false,
                    'message' => "Invalid role for registration",
                    'status_code' => 403
                ]);
                break;
        }
        //Set up transaction querry in database


    }



    protected function login(Request $request)
    {
        auth()->shouldUse('api');
        // grab credentials from the request
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        if (auth()->attempt($credentials)) {

            $currentUser = Auth::user();

            if ($currentUser->verified && $currentUser->status === Config::get('enums.status.ACTIVE')) {

                return new LoginResource($currentUser);

            } else {
                auth()->logout();
            }
        }

        return response([
            'success' => false,
            'message' => "Invalid Credentials",
            'status_code' => 403
        ]);

    }

    public function tokenFromUser($id)
    {
        // generating a token from a given user.
        $user = User::find($id);

        auth()->shouldUse('api');
        // logs in the user
        auth()->login($user);

        // get and return a new token
        $token = auth()->issue();

        return $token;
    }


}
