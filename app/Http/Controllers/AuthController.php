<?php

namespace App\Http\Controllers;

use App\CouncilorDetail;
use App\AgentDetail;
use App\Http\Resources\LoginResource;
use App\Role;
use App\User;
use App\PasswordResets;
use App\SendMailable;
use App\StudentDetail;
use App\Utilities;
use App\Http\Resources\UserResource;
use App\Mail\PasswordResetUserCreate;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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


    public function register(Guard $auth, Request $request)
    {

        Log::info("Initlaize User Registration with email : " . $request['email'] . "and Role :" . $request['role']);

        $role = Role::where('name', $request['role'])->first();

        if ($role === null) {
            return response([
                'success' => false,
                'message' => "Invalid role for registration",
                'status_code' => 403
            ]);
        }


        switch ($role->name) {
            case "student":

                $fields = ['firstName', 'lastName', 'middleName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'role', 'studentIdNumber','countryCode'];
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
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                Log::info("Validate mobile number");
                if(array_key_exists('countryCode', $credentials) ){
                    $isValid = Utilities::validatePhoneNumber($credentials['countryCode'],$credentials['phone']);

                    if(!$isValid) {
                        return response([
                            'success' => false,
                            'message' => "Phone number " . $credentials['countryCode'].$credentials['phone'] . " is not valid ",
                            'status_code' => 400
                        ]);

                    }
                }

                Log::info("Create Student");

                try {
                    DB::beginTransaction();
                    $student = new User();
                    $student = $this->createStudent($student,$credentials);

                    $responseBux = $this->createStudentBux($student);

                    if (!$responseBux->code) {
                        Log::error("Error from Bux API");
                        throw new \Exception("Error from bux API");
                    }

                    $student->studentDetails->bux_id = $responseBux->details->id;
                    $student->studentDetails->save();

                    $token = $this->tokenFromUser($student->id);

                    DB::commit();

                    return response([
                        "success" => true,
                        "status_code" => 200,
                        "email" => $student->email,
                        "token" => $token,
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
                $fields = ['agentName', 'location', 'email', 'phone', 'password', 'confirmPassword', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'validBankOpening', 'role','countryCode'];
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
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                Log::info("Validate mobile number");
                if(array_key_exists('countryCode', $credentials) ){
                    $isValid = Utilities::validatePhoneNumber($credentials['countryCode'],$credentials['phone']);

                    if(!$isValid) {
                        return response([
                            'success' => false,
                            'message' => "Phone number " . $credentials['countryCode'].$credentials['phone'] . " is not valid ",
                            'status_code' => 400
                        ]);

                    }
                }

                Log::info("Create Agent");

                try {
                    DB::beginTransaction();
                    $user = $this->createAgent($credentials);


                } catch (\Exception $e) {
                    //Roll back database if error
                    Log::error("Error while saving to database" . $e->getMessage());
                    DB::rollback();
                    return response()->json(['error' => $e->getMessage()], 500);
                }


                return response([
                    "success" => true,
                    "status_code" => 200,
                    "email" => $user->email,
                    "token" => $user->token,
                    "role" => $user->role->name
                ]);
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

    protected function createStudent($student,$credentials)
    {

        $student = new User();

        if(array_key_exists('countryCode',$credentials)){
            $student->phone = $credentials['countryCode'].$credentials['phone'];
        }
        $student->email = $credentials['email'];
        $student->password = bcrypt($credentials['password']);
        $student->verified = true;
        $student->status = Config::get('enums.status.ACTIVE');
        $student->role()->associate((Role::where('name', 'student')->first()));

        Log::info("Save Student ");
        $student->save();

        $studentDetail = new StudentDetail();
        $studentDetail->firstname = $credentials['firstName'];
        $studentDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $studentDetail->lastname = $credentials['lastName'];
        $studentDetail->dob = $credentials['dob'];
        $studentDetail->gender = array_key_exists("gender", $credentials) ? $credentials['gender'] : null;
        $studentDetail->national_id = $credentials['nationalId'];
        $studentDetail->student_id_number = $credentials['studentIdNumber'];

        Log::info("Save Student details ");
        $student->studentDetails()->save($studentDetail);

        return $student;


    }

    protected function createStudentBux($student){

        Log::info("Create Student " . $student->email . "in BUX API " . Config::get('constants.bux_base_url'));
        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->post(Config::get('constants.bux_base_url') . Config::get('constants.bux_student'), ['json' => Utilities::getJsonRequestForUpdateStudent($student)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;


    }

    protected function createAgent($credentials)
    {
        $user = new User();
        if(array_key_exists('countryCode',$credentials)){
            $user->phone = $credentials['countryCode'].$credentials['phone'];
        }

        $user->email = $credentials['email'];
        $user->password = bcrypt($credentials['password']);
        $user->verified = false;
        $user->status = Config::get('enums.status.ACTIVE');
        $user->role()->associate((Role::where('name', $credentials['role'])->first()));

        Log::info("Save Agent ");
        $user->save();

        $agentDetails = new AgentDetail();
        $agentDetails->name = $credentials['agentName'];
        $agentDetails->location = $credentials['location'];
        $agentDetails->national_id = $credentials['nationalId'];
        $agentDetails->legal_registration_number = array_key_exists('legalRegistrationNumber', $credentials) ? $credentials['legalRegistrationNumber'] : null;
        $agentDetails->valid_bank_opening = $credentials['validBankOpening'];
        $agentDetails->bank_account_number = $credentials['bankAccountNumber'];
        $agentDetails->bank_account_name = $credentials['bankAccountName'];

        //Default Status value
        $agentDetails->status = 0;

        Log::info("Save Agent Details for agent " . $user->email);
        $user->agentDetails()->save($agentDetails);


        $user['token'] = $this->tokenFromUser($user['id']);


        DB::commit();
        return $user;

    }


    protected function login(Request $request)
    {
        Log::info("Request is " . $request);

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


    public function createCouncilor(Request $request)
    {

        Log::info("Initlaize Councilor Registration with email : " . $request['email']);

        $fields = ['firstName', 'lastName', 'middleName', 'email', 'password', 'confirmPassword', 'phone', 'nationalId','url','countryCode'];

        // grab credentials from the request
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials, [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName => max:255',
                'countryCode' => 'required_with:phone|numeric',
                'phone' => 'required_with:countryCode|numeric',
                'email' => 'required|email|max:255|unique:login_users_c',
                'nationalId' => 'required',
                'password' => 'required|min:6',
                'confirmPassword' => 'required_with:password|same:password',
                'url'=>'required|url'
            ]
        );

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        Log::info("Validate mobile number");
        if(array_key_exists('countryCode', $credentials) ){
            $isValid = Utilities::validatePhoneNumber($credentials['countryCode'],$credentials['phone']);

            if(!$isValid) {
                return response([
                    'success' => false,
                    'message' => "Phone number " . $credentials['countryCode'].$credentials['phone'] . " is not valid ",
                    'status_code' => 400
                ]);

            }
        }


        Log::info("Create Councilor with email" . $credentials['email']);

        DB::beginTransaction();

        try {

            $councilor = new User();
            if(array_key_exists('countryCode',$credentials)){
                $councilor->phone = $credentials['countryCode'].$credentials['phone'];
            }
            $councilor->email = $credentials['email'];
            $councilor->password = bcrypt($credentials['password']);
            $councilor->verified = true;
            $councilor->role()->associate((Role::where('name', 'councilor')->first()));
            $councilor->status = Config::get('enums.status.ACTIVE');
            Log::info("Save Councilor ");
            $councilor->save();


            $councilorDetail = new CouncilorDetail();
            $councilorDetail->firstname = $credentials['firstName'];
            $councilorDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
            $councilorDetail->lastname = $credentials['lastName'];
            $councilorDetail->national_id = $credentials['nationalId'];
            $councilorDetail->status = 0;

            $councilorDetail->agent()->associate(Auth::user()->agentDetails);


            Log::info("Save Councilor Details ");
            $councilor->councilorDetails()->save($councilorDetail);

            $this->sendEmailToResetPassword($councilor,$credentials['url']);

            DB::commit();


            return new UserResource($councilor);

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database" . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);

        }


    }

    public function createStudentByCouncilor(Request $request){
        $currentUser = Auth::user();

        $fields = ['firstName', 'lastName', 'middleName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'studentIdNumber','url','countryCode'];
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
                'studentIdNumber' => 'required',
                'url'=>'required|url'
            ]
        );
        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }


        if(array_key_exists('code', $credentials) ){
            $isValid = Utilities::validatePhoneNumber($credentials['code'],$credentials['phone']);

            if(!$isValid) {
                return response([
                    'success' => false,
                    'message' => "Phone number " . $credentials['phone'] . " is not valid ",
                    'status_code' => 400
                ]);

            }
        }

        Log::info("Create Student by councilor");

        try {
            DB::beginTransaction();
            $student = new User();


            $student = $this->createStudent($student,$credentials);
            $student->studentDetails->councilor()->associate($currentUser->councilorDetails);

            $responseBux = $this->createStudentBux($student);

            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }


            $student->studentDetails->bux_id = $responseBux->details->id;

            $student->studentDetails->save();

            $token = $this->tokenFromUser($student->id);

            $this->sendEmailToResetPassword($student,$credentials['url']);

            DB::commit();

            return response([
                "success" => true,
                "status_code" => 200,
                "email" => $student->email,
                "token" => $token,
                "role" => $student->role->name
            ]);



        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database" . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    protected function sendEmailToResetPassword($councilor,$url){
        Log::info("Send password reset email to ".$councilor->email);
        PasswordResets::where('email', $councilor->email)->delete();

        $passwordReset = PasswordResets::create([
            'email' => $councilor->email,
            'token' => str_random(64)
        ]);

        Mail::to($passwordReset->email)->send(new PasswordResetUserCreate($councilor,$url, $passwordReset->token));
    }


}
