<?php

namespace App\Http\Controllers;

use App\Http\Resources\Transaction;
use App\Http\Resources\TransactionResourceCollection;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Utilities;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    private $transctionRepository;

    private $userRepository;


    public function __construct(TransactionRepository $transactionRepository, UserRepository $userRepository)
    {
        $this->transctionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
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

        return response(new Transaction($transaction));
    }


}
