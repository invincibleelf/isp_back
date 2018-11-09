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

class StudentController extends Controller
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

        Log::info("Get Students for " . $currentUser->role->name . " with email " . $currentUser->email);

        $students = $this->userRepository->getStudentsByCurrentUser($currentUser);

        return response(new UserResourceCollection($students));


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

        Log::info("Create student by " . $currentUser->role->name . " with email " . $currentUser->email);

        $fields = ['firstName', 'lastName', 'middleName', 'chineseFirstName', 'chineseLastName', 'dob', 'email', 'gender', 'password', 'confirmPassword', 'phone', 'nationalId', 'studentIdNumber', 'url', 'countryCode'];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName' => 'max:255',
                'chineseFirstName' => 'max:255',
                'chineseLastName' => 'max:255',
                'dob' => 'required',
                'countryCode' => 'required_with:phone|numeric',
                'phone' => 'required_with:countryCode|numeric',
                'email' => 'required|email|max:255|unique:login_users_c',
                'nationalId' => 'required',
                'password' => 'required|min:6',
                'confirmPassword' => 'required_with:password|same:password',
                'studentIdNumber' => 'required',
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

            $student = new User();
            $student = $this->userService->createStudent($student, $credentials);
            $student->studentDetails->councilor()->associate($currentUser->councilorDetails);

            $responseBux = $this->userService->createStudentAtBux($student);
            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }

            $student->studentDetails->bux_id = $responseBux->details->id;
            $student->studentDetails->save();

            $this->emailService->sendEmailToResetPasswordCreateUser($student, $credentials['url']);

            DB::commit();

            return response([
                "success" => true,
                "status_code" => 200,
                "email" => $student->email,
                "role" => $student->role->name
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
        Log::info("Get Student with id " . $id . " for " . $currentUser->role->name . " with email " . $currentUser->email);

        $student = $this->userRepository->getStudentByIdAndCurrentUser($id, $currentUser);

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Student with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
        } else {
            return response(new UserResource($student));
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
        Log::info("Update student with id $id by " . $currentUser->role->name . " " . $currentUser->email);

        $student = $this->userRepository->getStudentByIdAndCurrentUser($id, $currentUser);
        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Student with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
        }

        $fields = ['firstName', 'lastName', 'middleName', 'chineseFirstName', 'chineseLastName', 'dob', 'gender', 'phone', 'nationalId', 'studentIdNumber', 'countryCode'];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'firstName' => 'required|max:255',
                'lastName' => 'required|max:255',
                'middleName' => 'max:255',
                'chineseFirstName' => 'max:255',
                'chineseLastName' => 'max:255',
                'dob' => 'required',
                'gender' => 'required',
                'countryCode' => 'required_with:phone|numeric',
                'phone' => 'required_with:countryCode|numeric',
                'nationalId' => 'required',
                'studentIdNumber' => 'required'
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
            $student = $this->userService->updateStudent($student, $credentials);

            $responseBux = $this->userService->updateStudentAtBux($student);
            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }

            DB::commit();

            return response(new UserResource($student));

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
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
        Log::info("Delete student with id " . $id . " by councilor " . $currentUser->email);

        $student = $this->userRepository->getStudentByIdAndCurrentUser($id, $currentUser);

        if ($student == null) {
            Log::error("Student with id " . $id . " doesn't exist for " . $currentUser->email);
            return response(Utilities::getResponseMessage("Student with id " . $id . " doesn't exist for " . $currentUser->email, false, 404));
        }

        try {
            DB::beginTransaction();

            $student->status = Config::get('enums.status.DELETED');

            $student->save();

            $responseBux = $this->userService->deleteStudentAtBux($student);

            if (!$responseBux->code) {
                Log::error("Error from Bux API");
                throw new \Exception("Error from bux API");
            }

            DB::commit();


            return response(Utilities::getResponseMessage("Student with id $id deleted successfully", true, 200));


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database. " . $e->getMessage());
            DB::rollback();
            return response()->json(['Error' => $e->getMessage()], 500);
        }


    }
}
