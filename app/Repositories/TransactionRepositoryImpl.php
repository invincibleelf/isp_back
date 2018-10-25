<?php

namespace App\Repositories;


use App\Transaction;

class TransactionRepositoryImpl implements TransactionRepository
{


    public function getTransactionsByStudentId($studentId)
    {
        $transactions = Transaction::where("student_id", $studentId)->get();

        return $transactions;
    }


}