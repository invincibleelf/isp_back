<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceCollection;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\Services\UserService;
use App\User;
use App\Utilities;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CouncilorController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $currentUser = Auth::user();

        Log::info("Get Councilor for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilors = $this->userRepository->getCouncilorsByCurrentUser($currentUser);

        return response(new UserResourceCollection($councilors));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();
        Log::info("Initlaize Councilor Registration by agent " . $currentUser->email);

        $fields = ['firstName', 'lastName', 'middleName', 'email', 'password', 'confirmPassword', 'phone', 'nationalId', 'url', 'countryCode'];

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
                'url' => 'required|url'
            ]
        );

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response(Utilities::getResponseMessage($validator->messages(), false, '400'));
        }


        Log::info("Validate mobile number");
        if (array_key_exists('countryCode', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
            if (!$isValid) {
                Log::error("Phone number " . $credentials['phone'] . " is not valid ");
                return response(Utilities::getResponseMessage("Phone number " . $credentials['phone'] . " is not valid ", false, 400));

            }
            $credentials['phone'] = Utilities::formatPhoneNumber($credentials['countryCode'], $credentials['phone']);
        }

        try {
            DB::beginTransaction();

            $councilor = new User();
            $councilor = $this->userService->createCouncilor($councilor, $credentials);

            $this->emailService->sendEmailToResetPasswordCreateUser($councilor, $credentials['url']);

            DB::commit();

            return response(new UserResource($councilor));

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
        Log::info("Get Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = $this->userRepository->getCouncilorByIdAndCurrentUser($id, $currentUser);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Councilor with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
        } else {
            return response(new UserResource($councilor));
        }
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
        Log::info("Update Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = $this->userRepository->getCouncilorByIdAndCurrentUser($id, $currentUser);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Student with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
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
            Log::error("Validation Error");
            return response(Utilities::getResponseMessage($validator->messages(), false, '400'));
        }


        Log::info("Validate mobile number");
        if (array_key_exists('countryCode', $credentials)) {
            $isValid = Utilities::validatePhoneNumber($credentials['countryCode'], $credentials['phone']);
            if (!$isValid) {
                Log::error("Phone number " . $credentials['phone'] . " is not valid ");
                return response(Utilities::getResponseMessage("Phone number " . $credentials['phone'] . " is not valid ", false, 400));

            }

            $credentials['phone'] = Utilities::formatPhoneNumber($credentials['countryCode'], $credentials['phone']);
        }

        try {
            DB::beginTransaction();

            $councilor = $this->userService->updateCouncilor($councilor, $credentials);

            DB::commit();

            return response(new UserResource($councilor));
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
        Log::info("Delete Councilor with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $councilor = $this->userRepository->getCouncilorByIdAndCurrentUser($id, $currentUser);

        if ($councilor == null) {
            Log::error("Councilor with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Student with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
        }

        try {
            DB::beginTransaction();

            $councilor->status = Config::get('enums.status.DELETED');
            $councilor->save();

            DB::commit();

            return response(Utilities::getResponseMessage("Councilor with id " . $councilor->id . " deleted successfully", true, 200));

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }

    public function transferStudents(Request $request)
    {
        $fields = ['fromId', 'toId'];
        $credentials = $request->only($fields);

        $validator = Validator::make(

            $credentials,
            [
                'fromId' => 'required|integer',
                'toId' => 'required|integer'
            ]
        );
        if ($validator->fails()) {
            return response(Utilities::getResponseMessage($validator->messages(), false, 400));
        }

        $currentUser = Auth::user();
        $status = Config::get("enums.status.ACTIVE");

        $oldCouncilor = $this->userRepository->getVerifiedCouncilorByIdAndStatusAndCurrentAgent($credentials['fromId'], $status, $currentUser);

        $newCouncilor = $this->userRepository->getVerifiedCouncilorByIdAndStatusAndCurrentAgent($credentials['toId'], $status, $currentUser);

        if (!$oldCouncilor || !$newCouncilor) {
            Log::error("Councilor doesn't exists");
            return response(Utilities::getResponseMessage("Councilor doesn't exist", false, 400));
        }

        $students = $this->userRepository->getStudentsByCurrentUser($oldCouncilor);

        if ($students->isEmpty()) {
            Log::error("Students doesn't exist for councilor with id " . $oldCouncilor->id);
            return response(Utilities::getResponseMessage("Students doesn't exist for councilor $oldCouncilor->email with id: $oldCouncilor->id", false, 400));
        }

        try {
            DB::beginTransaction();

            Log::info("Update all the students with new councilor id " . $newCouncilor->councilorDetails->id);

            $this->userRepository->transferStudents($oldCouncilor, $newCouncilor);

            DB::commit();

            return response(Utilities::getResponseMessage("Students transfered between councilor $oldCouncilor->email and $newCouncilor->email successfully", true, 200));


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

}
