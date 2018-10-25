<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\PasswordResets;
use App\Services\EmailService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class PasswordController extends Controller
{
    private $emailService;
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function changePassword(Request $request)
    {


        $currentUser = Auth::user();
        Log::info("Change password request for user " . $currentUser->email);

        $fields = ['oldPassword', 'newPassword', 'confirmPassword'];
        $credentials = $request->only($fields);

        $validator = Validator::make($credentials, [
            'oldPassword' => 'required',
            'newPassword' => 'required|min:6',
            'confirmPassword' => 'required_with:newPassword|same:newPassword',
        ]);

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        if (Hash::check($credentials['oldPassword'], $currentUser->password)) {

            $currentUser->password = bcrypt($credentials['newPassword']);
            $currentUser->save();
            return response([
                "success" => true,
                "status_code" => 200,
                "message" => "Password changed successfully"]);
        } else {
            Log::error("Old password doesn't match");
            return response([
                "success" => false,
                "status_code" => 400,
                "message" => "Old password doesn't match"
            ]);
        }


    }


    public function passwordResetEmail(Request $request)
    {


        $fields = ["email", "url"];
        $credentials = $request->only($fields);

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'url' => 'required|url'
        ]);

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        Log::info("Request to send password reset email for " . $credentials['email']);

        $user = User::where('email', $credentials['email'])->where('verified', true)->first();

        if (!$user) {
            return response([
                "success" => false,
                "message" => "We can not find email " . $credentials['email'] . " in our database! You can register a new account with this email.",
                "status_code" => 400
            ]);
        }

        $this->emailService->sendEmailToResetPassword($user, $credentials['url']);

        return response([
            "success" => true,
            "status_code" => 200,
            "message" => "Password reset email send successfully"
        ]);
    }

    public function resetPassword(Request $request)
    {

        $fields = ["password", "confirmPassword", "token"];
        $credentials = $request->only($fields);

        $validator = Validator::make($credentials, [
            'password' => 'required|min:6',
            'confirmPassword' => 'required_with:password|same:password',
            'token' => "required"
        ]);

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response([
                'success' => false,
                'message' => $validator->messages(),
                'status_code' => 400
            ]);
        }

        $passwordReset = PasswordResets::where('token',$credentials['token'])->first();

        if(!$passwordReset){
            return response([
                'success' => false,
                'status_code' => 404,
                'message' => "Invalid passsword reset link"
            ]);
        }

        $dateCreated = strtotime($passwordReset->created_at);
        $expireInterval = 86400; // token expire interval in seconds (24 h)
        $currentTime = time();

        if ($currentTime - $dateCreated > $expireInterval) {
            return response([
                'success' => false,
                'message' => 'The time to reset password has expired!',
                'status_code' => 400
            ]);
        }

        $user = User::where("email",$passwordReset['email'])->update(['password'=>bcrypt($credentials['password'])]);

        if($user <= 0){
            return response([
                'success' => false,
                'message' => "User doesn't exist",
                'status_code' => 400
            ]);
        }

        PasswordResets::where('token',$credentials['token'])->delete();
        return response([
            "success" => true,
            "status_code" => 200,
            "message" => "The password has been changed successfully"
        ]);


    }


}
