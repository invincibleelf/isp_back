<?php

namespace App\Http\Controllers;

use App\CouncilorDetail;
use App\AgentDetail;

use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use App\Role;
use App\User;
use App\PasswordResets;
use App\SendMailable;
use App\StudentDetail;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Config;
use Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Psy\Util\Json;
use GuzzleHttp\Client;


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
                'status_code' => 400
            ]);
        }


        switch ($role->name) {
            case "student":
                $fields = ['firstName', 'lastName', 'middleName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'role'];
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
                        'role' => 'required'
                    ]
                );
                if ($validator->fails()) {
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }


//                TODO Implement while calling BUX API
//                Log::info("Create Student ".$credentials["email"]."in BUX API ".\Illuminate\Support\Facades\Config::get('constants.bux_base_url'));
//
//                $buxAPI = new Client([
//                    'base_uri'=>\Illuminate\Support\Facades\Config::get('constants.bux_base_url'),
//                    'timeout'=>2.0
//                ]);
//
//
//                $buxResponse = $buxAPI->get(\Illuminate\Support\Facades\Config::get('constants.bux_base_url')."showProfile");
//
//                return response(["s"=>$buxResponse]);


                Log::info("Create Student");
                $user = $this->createStudent($credentials);
                return response([
                    "success"=>true,
                    "status_code"=>500,
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
                    return response([
                        'success' => false,
                        'message' => $validator->messages(),
                        'status_code' => 400
                    ]);
                }

                $user = $this->createAgent($credentials);
                return response([
                    "success"=>true,
                    "status_code"=>500,
                    "email" => $user->email,
                    "token" => $user->token,
                    "role" => $user->role->name
                ]);
                break;

            default:
                return response([
                    'success' => false,
                    'message' => "Invalid role for registration",
                    'status_code' => 400
                ]);
                break;
        }
        //Set up transaction querry in database


    }

    protected function createStudent($credentials)
    {
        DB::beginTransaction();

        try {

            $user = new User();
            $user->phone = $credentials['phone'];
            $user->email = $credentials['email'];
            $user->password = $credentials['password'];
            $user->verified = true;
            $user->role()->associate((Role::where('name', $credentials['role'])->first()));
            Log::info("Save Student ");
            $user->save();

            $studentDetail = new StudentDetail();
            $studentDetail->firstname = $credentials['firstName'];
            $studentDetail->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
            $studentDetail->lastname = $credentials['lastName'];
            $studentDetail->dob = $credentials['dob'];
            $studentDetail->gender = $credentials['gender'];
            $studentDetail->national_id = $credentials['nationalId'];

            Log::info("Save Student details ");
            $user->studentDetails()->save($studentDetail);

            $user['token'] = $this->tokenFromUser($user['id']);


            DB::commit();
            return $user;

        } catch (Exception $e) {
            //Roll back database if error
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        };

    }

    protected function createAgent($credentials)
    {
        DB::beginTransaction();
        try {
            $user = new User();
            $user->phone = $credentials['phone'];
            $user->email = $credentials['email'];
            $user->password = $credentials['password'];
            $user->verified = false;
            $user->role()->associate((Role::where('name', $credentials['role'])->first()));

            Log::info("Save Agent ");
            $user->save();

            $agentDetails = new AgentDetail();
            $agentDetails->name = $credentials['agentName'];
            $agentDetails->location = $credentials['location'];
            $agentDetails->national_id = $credentials['nationalId'];
            $agentDetails->legal_registration_number = $credentials['legalRegistrationNumber'];
            $agentDetails->valid_bank_open = $credentials['validBankOpening'];
            $agentDetails->bank_account_number = $credentials['bankAccountNumber'];
            $agentDetails->bank_account_name = $credentials['bankAccountName'];

            //Default Status value
            $agentDetails->status = 0;

            Log::info("Save Agent Details for agent " . $user->email);
            $user->agentDetails()->save($agentDetails);


            $user['token'] = $this->tokenFromUser($user['id']);


            DB::commit();
            return $user;

        } catch (Exception $e) {
            //Roll back database if error
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }

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
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        if (auth()->attempt($credentials)) {

            $currentUser = Auth::user();

            if ($currentUser->verified) {

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

    public function passwordResetEmail(Request $request)
    {
        $fields = ['email', 'url'];
        // grab credentials from the request
        $credentials = $request->only($fields);
        foreach ($fields as $field) {
            $credentials[$field] = trim($credentials[$field]);
        }

        $validator = Validator::make(
            $credentials,
            [
                'email' => 'required|email|max:255',
                'url' => 'required'
            ]
        );
        if ($validator->fails()) {
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        $email = $credentials['email'];

        $user = User::where('email', '=', $email)->first();
        if (!$user) {
            return response([
                'success' => false,
                'message' => 'We can not find email you provided in our database! You can register a new account with this email.',
                'status_code' => 404
            ]);
        }

        // delete existings resets if exists
        PasswordResets::where('email', $email)->delete();

        $url = $credentials['url'];
        $token = str_random(64);
        $result = PasswordResets::create([
            'email' => $email,
            'token' => $token
        ]);

        if ($result) {
            Mail::to($email)->queue(new SendMailable($url, $token));
            return response([
                'success' => true,
                'message' => 'The mail has been sent successfully!',
                'status_code' => 201
            ]);
        }
        return response([
            'success' => false,
            'message' => $error,
            'status_code' => 500
        ]);
    }

    public function resetPassword(Request $request)
    {
        $fields = ['password', 'token'];
        // grab credentials from the request
        $credentials = $request->only($fields);
        foreach ($fields as $field) {
            $credentials[$field] = trim($credentials[$field]);
        }

        $validator = Validator::make(
            $credentials,
            [
                'password' => 'required|min:6',
                'token' => 'required'
            ]
        );
        if ($validator->fails()) {
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        $token = $credentials['token'];
        $pr = PasswordResets::where('token', $token)->first(['email', 'created_at']);
        $email = $pr['email'];
        if (!$email) {
            return response([
                'success' => false,
                'message' => 'Invalid reset password link!',
                'status_code' => 404
            ]);
        }

        $dateCreated = strtotime($pr['created_at']);
        $expireInterval = 86400; // token expire interval in seconds (24 h)
        $currentTime = time();

        if ($currentTime - $dateCreated > $expireInterval) {
            return response([
                'success' => false,
                'message' => 'The time to reset password has expired!',
                'status_code' => 400
            ]);
        }

        $password = bcrypt($credentials['password']);

        $updatedRows = User::where('email', $email)->update(['password' => $password]);
        if ($updatedRows > 0) {
            PasswordResets::where('token', $token)->delete();
            return response([
                'success' => true,
                'message' => 'The password has been changed successfully!',
                'status_code' => 200
            ]);
        }
        return response([
            'success' => false,
            'message' => $error,
            'status_code' => 500
        ]);
    }

    public function show(Request $request)
    {

        return response()->json(["users" => User::all()]);
    }

    public function details(Request $request)
    {

        return response()->json(["users" => User::all()]);
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
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        Log::info("Create Student with email" . $credentials['email']);

        DB::beginTransaction();

        try {

            $councilor = new User();
            $councilor->phone = $credentials['phone'];
            $councilor->email = $credentials['email'];
            $councilor->password = $credentials['password'];
            $councilor->verified = true;
            $councilor->role()->associate((Role::where('name', 'councilor')->first()));
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

            DB::commit();
            return response([
                "success" => true,
                "status-code" => 500,
                "councilor" => $councilor
            ]);

        } catch (Exception $e) {
            //Roll back database if error
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        };

        return response([
            'success' => false,
            'message' => "Error",
            'status_code' => 400
        ]);

    }


}
