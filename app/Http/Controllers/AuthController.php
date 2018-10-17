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
use Psy\Util\Json;
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

                $fields = ['firstName', 'lastName', 'middleName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'role', 'studentIdNumber'];
                // grab credentials from the request
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
                        'email' => 'required|email|max:255|unique:login_users_c',
                        'nationalId' => 'required|unique:student_details,national_id',
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

                Log::info("Create Student");

                try {
                    DB::beginTransaction();
                    $user = $this->createStudent($credentials);

                    if ($user === null) {
                        throw new \Exception("Cannot save student to bux application", '0');
                    }
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

            case "agent":
                $fields = ['agentName', 'location', 'email', 'phone', 'password', 'confirmPassword', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'validBankOpening', 'role'];
                // grab credentials from the request
                $credentials = $request->only($fields);
                $validator = Validator::make(
                    $credentials,
                    [
                        'agentName' => 'required|max:255',
                        'phone' => 'required',
                        'email' => 'required|email|max:255|unique:login_users_c',
                        'nationalId' => 'required|unique:agent_details,national_id',
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

    protected function createStudent($credentials)
    {

        $user = new User();
        $user->phone = $credentials['phone'];
        $user->email = $credentials['email'];
        $user->password = bcrypt($credentials['password']);
        $user->verified = true;
        $user->status = Config::get('enums.status.ACTIVE');
        $user->role()->associate((Role::where('name', $credentials['role'])->first()));
        Log::info("Save Student ");
        $user->save();

        $studentDetail = new StudentDetail();
        $studentDetail->firstname = $credentials['firstName'];
        $studentDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $studentDetail->lastname = $credentials['lastName'];
        $studentDetail->dob = $credentials['dob'];
        $studentDetail->gender = array_key_exists("gender", $credentials) ? $credentials['gender'] : null;
        $studentDetail->national_id = $credentials['nationalId'];
        $studentDetail->student_id_number = $credentials['studentIdNumber'];

        Log::info("Save Student details ");
        $user->studentDetails()->save($studentDetail);


        Log::info("Create Student " . $credentials["email"] . "in BUX API " . Config::get('constants.bux_base_url'));
        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->post(Config::get('constants.bux_base_url') . Config::get('constants.bux_student'), ['json' => Utilities::getJsonRequestForUpdateStudent($user)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        if (!$contents->code) {
            return null;
        }

        $user->studentDetails->bux_id = $contents->details->id;
        $user->save();
        $user->studentDetails->save();

        $user['token'] = $this->tokenFromUser($user['id']);


        DB::commit();
        return $user;


    }

    protected function createAgent($credentials)
    {
        $user = new User();
        $user->phone = $credentials['phone'];
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

        $fields = ['firstName', 'lastName', 'middleName', 'email', 'password', 'confirmPassword', 'phone', 'nationalId'];

        // grab credentials from the request
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials, [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName => max:255',
                'phone' => 'required',
                'email' => 'required|email|max:255|unique:login_users_c',
                'nationalId' => 'required|unique:councilor_details,national_id',
                'password' => 'required|min:6',
                'confirmPassword' => 'required_with:password|same:password'
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


        Log::info("Create Councilor with email" . $credentials['email']);

        DB::beginTransaction();

        try {

            $councilor = new User();
            $councilor->phone = $credentials['phone'];
            $councilor->email = $credentials['email'];
            $councilor->password = bcrypt($credentials['password']);
            $councilor->verified = true;
            $councilor->role()->associate((Role::where('name', 'councilor')->first()));
            $councilor->status = Config::get('enums.status.ACTIVE');
            Log::info("Save Councilor ");
            $councilor->save();


            $councilorDetail = new CouncilorDetail();
            $councilorDetail->firstname = $credentials['firstName'];
            $councilorDetail->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
            $councilorDetail->lastname = $credentials['lastName'];
            $councilorDetail->national_id = $credentials['nationalId'];
            $councilorDetail->status = 0;

            $councilorDetail->agent()->associate(Auth::user()->agentDetails);


            Log::info("Save Councilor Details ");
            $councilor->councilorDetails()->save($councilorDetail);

            $this->sendEmailToResetPassword($councilor);

            DB::commit();


            return new UserResource($councilor);

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database" . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);

        }


    }

    protected function sendEmailToResetPassword($councilor){
        Log::info("Send password reset email to ".$councilor->email);
        PasswordResets::where('email', $councilor->email)->delete();

        $passwordReset = PasswordResets::create([
            'email' => $councilor->email,
            'token' => str_random(64)
        ]);

        Mail::to($passwordReset->email)->send(new PasswordResetUserCreate($councilor,Config::get('constants.password_reset_url'), $passwordReset->token));
    }


}
