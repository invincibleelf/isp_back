<?php

namespace App\Http\Controllers;

use App\BankAccount;
use App\Role;
use App\User;
use App\PasswordResets;
use App\SendMailable;
use App\UserDetail;
use Illuminate\Support\Facades\Log;
use Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Psy\Util\Json;


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

        Log::info("Initlaize User Student Registration with email : " . $request['email'] . "and Role :" . $request['role']);


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
                        'dob' => 'required|date',
                        'gender' => 'required',
                        'phone' => 'required',
                        'email' => 'required|email|max:255|unique:users',
                        'nationalId' => 'required|unique:users,national_id',
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

                $user = $this->createStudent($credentials);
                return response($user->only(['email', 'token']));
                break;

            case "agent":
                $fields = ['agentName', 'location', 'email', 'phone', 'password', 'confirmPassword', 'nationalId', 'legalRegistrationNumber', 'bankAccountNumber', 'bankAccountName', 'openingBank', 'role'];
                // grab credentials from the request
                $credentials = $request->only($fields);
                $validator = Validator::make(
                    $credentials,
                    [
                        'agentName' => 'required|max:255',
                        'phone' => 'required',
                        'email' => 'required|email|max:255|unique:users',
                        'nationalId' => 'required|unique:users,national_id',
                        'password' => 'required|min:6',
                        'confirmPassword' => 'required_with:password|same:password',
                        'bankAccountNumber' => 'required',
                        'bankAccountName' => 'required',
                        'openingBank' => 'required',
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
                return response($user->only(['email', 'token']));
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

            $user = User::create([
                'phone' => $credentials['phone'],
                'email' => $credentials['email'],
                'password' => bcrypt($credentials['password']),
                'national_id' => $credentials['nationalId']
            ]);

            $userDetail = new UserDetail();
            $userDetail->firstname = $credentials['firstName'];
            $userDetail->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
            $userDetail->lastname = $credentials['lastName'];
            $userDetail->dob = $credentials['dob'];
            $userDetail->gender = $credentials['gender'];

            $user->userDetails()->save($userDetail);

            $user['token'] = $this->tokenFromUser($user['id']);

            $user->roles()->attach(Role::where('name', $credentials['role'])->first());

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
            $user = User::create([
                'phone' => $credentials['phone'],
                'email' => $credentials['email'],
                'password' => bcrypt($credentials['password']),
                'national_id' => $credentials['nationalId']
            ]);

            $userDetail = new UserDetail();
            $userDetail->agent_name = $credentials['agentName'];
            $userDetail->location = $credentials['location'];
            $userDetail->legal_registration_number = $credentials['legalRegistrationNumber'];

            $user->userDetails()->save($userDetail);


            $bank = new BankAccount();
            $bank -> account_number = $credentials['bankAccountNumber'];
            $bank->account_name =  $credentials['bankAccountName'];
            $bank ->bank_name =  $credentials['openingBank'];

            $user->bankAccount()->save($bank);

            $user['token'] = $this->tokenFromUser($user['id']);


            $user->roles()->attach(Role::where('name', $credentials['role'])->first());

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

        if (auth()->attempt($credentials)) {
            $result['token'] = auth()->issue();
            $result['email'] = $credentials['email'];
            return response($result);
        }

        return response(['Invalid Credentials']);
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
}
