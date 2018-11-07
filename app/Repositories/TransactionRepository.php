<?php

namespace App\Repositories;


interface TransactionRepository
{

    public function getTransactionsByCurrentUser($currentUser);

    public function getTransactionsbyStudent($student, $currentUser);

    public function getTransactionById($id);

    public function getTransactionByTransactionSNAndStudentId($transactionSN, $studentId);

    public function getTransctionsByFavouriteIds($favouriteIds);


}