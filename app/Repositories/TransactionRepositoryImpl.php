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


    public function getTransactionsByCurrentUser($currentUser)
    {

        $transactions = [];
        switch ($currentUser->role->name) {

            case "agent":
                $transactions = Transaction::with('student', 'payer')->whereHas('student.councilor.agent', function ($q) use ($currentUser) {
                    $q->where("id", $currentUser->agentDetails->id);
                })->get();
                break;

            case "councilor":
                $transactions = Transaction::with('student', 'payer')->whereHas('student.councilor', function ($q) use ($currentUser) {
                    $q->where("id", $currentUser->councilorDetails->id);
                })->get();
                break;

            case "student":
                $transactions = Transaction::with('payer')->where('student_id', '=', $currentUser->studentDetails->id)->get();
                break;

            case "payer":
                $transactions = Transaction::with('student')->where('payer_id', '=', $currentUser->payerDetails->id)->get();
                break;
        }

        return $transactions;

    }

    public function getTransactionsByStudent($student, $currentUser)
    {

        $transactions = Transaction::with('student', 'payer')->where('student_id', '=', $student->studentDetails->id)->get();

        return $transactions;
    }


    public function getTransactionById($id)
    {
        $transaction = Transaction::with('student', 'payer')->find($id);

        return $transaction;
    }

    public function getTransactionByTransactionSNAndStudentId($transactionSN, $studentId)
    {
        $transaction = Transaction::with('payer', 'student')->where('transaction_sn', $transactionSN)->where('student_id', '=', $studentId)->first();

        return $transaction;
    }

    public function getTransctionsByFavouriteIds($favouriteIds)
    {
        $transaction = Transaction::whereIn('id', $favouriteIds)->get();
        return $transaction;
    }


}