<?php

namespace App\Http\Controllers;


use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionResourceCollection;
use App\Models\PaymentMethod;
use App\Repositories\MerchantRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\Services\TransactionService;
use App\Utilities;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    private $transctionRepository;

    private $userRepository;

    private $merchantRepository;

    private $transactionService;

    private $emailService;


    public function __construct(TransactionRepository $transactionRepository, UserRepository $userRepository, MerchantRepository $merchantRepository,
                                TransactionService $transactionService, EmailService $emailService)
    {
        $this->transctionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->merchantRepository = $merchantRepository;
        $this->transactionService = $transactionService;
        $this->emailService = $emailService;
    }

    public function index()
    {
        $currentUser = Auth::user();
        Log::info("Get transactions for {$currentUser->role->name}  $currentUser->email");

        $transactions = $this->transctionRepository->getTransactionsByCurrentUser($currentUser);

        return response(new TransactionResourceCollection($transactions));


    }

    /**
     * Get list of resources by provided params.
     *
     * @param  int $studentId
     * @return \Illuminate\Http\Response
     */

    public function transactionsByStudent($studentId)
    {
        $currentUser = Auth::user();
        Log::info("Get transactions for student with id : $studentId by user $currentUser->email");

        $student = $this->userRepository->getStudentByIdAndCurrentUser($studentId, $currentUser);

        if ($student == null) {
            return response(Utilities::getResponseMessage("Student with id : $studentId doesn't exist.", false, 400));
        }

        $transactions = $this->transctionRepository->getTransactionsbyStudent($student, $currentUser);

        return response(new TransactionResourceCollection($transactions));
    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        Log::info("Get transaction id : $id ");

        $transaction = $this->transctionRepository->getTransactionById($id);

        if ($transaction == null) {
            return response(Utilities::getResponseMessage("Transaction with id  $id is not available", false, 400));
        }

        return response(new TransactionResource($transaction));
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
        Log::info("Initiate transaction create by user $currentUser->email and id $currentUser->id");

        $fields = ["merchantId", "services", "totalAmount", "studentId", "url"];
        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'merchantId' => 'required|numeric',
                'totalAmount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'services' => 'required|array',
                'services.*.id' => "required|numeric",
                'services.*.amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
            ]);

//      StudentId and url must be present if transaction is initiated by coucilor
        $validator->sometimes(['studentId', 'url'], 'required', function () use ($currentUser) {
            return ($currentUser && $currentUser->role->name == 'councilor');
        });


        if ($validator->fails()) {
            Log::error("Validation Error");
            return response(Utilities::getResponseMessage($validator->messages(), false, '400'));
        }

        $sum = Utilities::validateTotalPaymentAmount($credentials['services']);

        if ($credentials['totalAmount'] != $sum) {
            return response(Utilities::getResponseMessage("Payment amount differs the sum of amount of selected services", false, '400'));
        }

        $merchant = $this->merchantRepository->getMerchantById($credentials['merchantId']);

        if (!$merchant) {
            return response(Utilities::getResponseMessage("Merchant with id : " . $credentials['merchantId'] . " doesn't exist", false, '400'));
        }

        //TODO validate services ids if belongs to the selected merchants

        switch ($currentUser->role->name) {
            case 'student';
                $student = $currentUser;
                break;

            case 'councilor':
                $student = $this->userRepository->getStudentByIdAndCurrentUser($credentials['studentId'], $currentUser);
                break;

            case 'payer':
                $student = $this->userRepository->getStudentByPayer($currentUser);
                break;

            case 'agent':
                break;
        }

        if (!$student) {
            return response(Utilities::getResponseMessage("Student doesn't exist", false, 400));
        }


        try {

            DB::beginTransaction();
            $transaction = new \App\Transaction();
            $transaction = $this->transactionService->createTransaction($transaction, $credentials, $student, $merchant);

//            $responseBux = $this->transactionService->createTransactionAtBux($transaction);

            DB::commit();

            if ($currentUser->role->name == "councilor") {
                $this->emailService->sendEmailToConfirmPayment($student, $credentials['url']);
            }

            return response(new TransactionResource($transaction));

        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
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
        Log::info("Update transaction with transction SN $id by user $currentUser->email");
        $fields = ['payerId', 'paymentMethodId'];
        $credentials = $request->only($fields);

        $validator = Validator::make($credentials,
            [
                "payerId" => "required",
                "paymentMethodId" => "required"
            ]);

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response(Utilities::getResponseMessage($validator->messages(), false, '400'));
        }

        $transaction = $this->transctionRepository->getTransactionByTransactionSNAndStudentId($id, $currentUser->studentDetails->id);

        if (!$transaction) {
            Log::error("Transaction with id $id doesn't exist for student $currentUser->email");
            return response(Utilities::getResponseMessage("Transaction with id $id doesn't exist for student $currentUser->email", false, 400));
        }

        $student = $this->userRepository->getUserByStudentDetailsId($transaction->student_id);

        if (!$student) {
            Log::error("Student with id $transaction->student_id doesn't exist");
            return response(Utilities::getResponseMessage("Student with id $transaction->student_id doesn't exist", false, 400));
        }

        $payer = $this->userRepository->getPayerByIdAndCurrentUser($credentials['payerId'], $student);

        if (!$payer) {
            Log::info("Payer with id " . $credentials['payerId'] . " doesn't exist for student with id $transaction->student_id");
            return response(Utilities::getResponseMessage("Payer with id " . $credentials['payerId'] . " doesn't exist for student", false, 400));
        }

        $paymentMethod = PaymentMethod::find($credentials['paymentMethodId']);

        if (!$paymentMethod) {
            Log::info("Payment method with id " . $credentials['paymentMethodId'] . " doesn't exist");
            return response(Utilities::getResponseMessage("Invalid Payment Method", false, 400));
        }


        try {

            DB::beginTransaction();

            $transaction = $this->transactionService->updateTransaction($transaction, $credentials, $payer, $paymentMethod);



            $responseBux = $this->transactionService->createTransactionAtBux($transaction);
            DB::commit();

            return response(new TransactionResource($transaction));


        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }


}
