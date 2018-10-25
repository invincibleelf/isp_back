<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceCollection;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\User;
use App\Services\UserService;
use App\Utilities;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PayerController extends Controller
{

    private $userService;
    private $userRepository;
    private $emailService;

    public function __construct(UserService $userService, UserRepository $userRepository, EmailService $emailService)
    {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentUser = Auth::user();
        Log::info("Get Payers for " . $currentUser->role->name . " with email " . $currentUser->email);

        $payers = $this->userRepository->getPayersByCurrentUser($currentUser);

        return response(new UserResourceCollection($payers));

    }

    /**
     * Store a newly created resource in storage.
     * Create Payer by Student
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = ['firstName', 'lastName', 'middleName', 'chineseName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'url', 'countryCode', 'bankAccountNumber'];
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
                'email' => 'required|email|max:255|unique:login_users_c',
                'nationalId' => 'required',
                'password' => 'required|min:6',
                'confirmPassword' => 'required_with:password|same:password',
                'url' => 'required|url',
                'bankAccountNumber' => 'required'
            ]
        );
        if ($validator->fails()) {
            Log::error("Validation Error");
            return response($this->userService->getFailureResponse($validator->messages(), 400));
        }


        if (array_key_exists('countryCode', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
            if (!$isValid) {
                return response($this->userService->getFailureResponse("Phone number " . $credentials['phone'] . " is not valid ", 400));

            }
        }

        try {

            DB::beginTransaction();

            $payer = new User();
            $payer = $this->userService->createPayer($payer, $credentials);
            $this->emailService->sendEmailToResetPasswordCreateUser($payer, $credentials['url']);

            DB::commit();

            return response([
                "success" => true,
                "status_code" => 200,
                "email" => $payer->email,
                "role" => $payer->role->name
            ]);


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $currentUser = Auth::user();
        Log::info("Get payer with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $payer = $this->userRepository->getPayerByIdAndCurrentUser($id, $currentUser);

        if ($payer == null) {
            Log::error("Payer with id " . $id . " doesn't exist for " . $currentUser->email);

            return response($this->userService->getFailureResponse("Payer with id " . $id . " doesn't exist for " . $currentUser->email, 404));
        }

        return response(new UserResource($payer));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        Log::info("Get payer with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $payer = $this->userRepository->getPayerByIdAndCurrentUser($id, $currentUser);

        if ($payer === null) {
            Log::error("Payer with id " . $id . " doesn't exist for " . $currentUser->email);

            return response($this->userService->getFailureResponse("Payer with id " . $id . " doesn't exist for " . $currentUser->email, 404));
        }

        $fields = ['firstName', 'lastName', 'middleName', 'chineseName', 'dob', 'gender', 'phone', 'countryCode', 'bankAccountNumber', 'nationalId'];
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
                'bankAccountNumber' => 'required',
                'nationalId' => 'required'

            ]
        );
        if ($validator->fails()) {
            Log::error("Validation Error");
            return response($this->userService->getFailureResponse($validator->messages(), 400));
        }


        if (array_key_exists('countryCode', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
            if (!$isValid) {
                return response($this->userService->getFailureResponse("Phone number " . $credentials['phone'] . " is not valid ", 400));

            }
        }

        try {
            DB::beginTransaction();
            $payer = $this->userService->updatePayer($payer, $credentials);

            DB::commit();

            return response(new UserResource($payer));

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        Log::info("Delete payer with id " . $id . " by student " . $currentUser->email);

        $payer = $this->userRepository->getPayerByIdAndCurrentUser($id, $currentUser);

        if ($payer == null) {
            Log::error("Payer with id " . $id . " doesn't exist for " . $currentUser->email);

            return response($this->userService->getFailureResponse("Payer with id " . $id . " doesn't exist for " . $currentUser->email, 404));
        }

        try {
            DB::beginTransaction();

            $payer->status = Config::get('enums.status.DELETED');
            $payer->save();

            DB::commit();

            return response($this->userService->successMessage("Payer deleted successfully", 200));
        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }
    }
}
