<?php

namespace App\Repositories;


interface TransactionRepository
{

    public function getTransactionsByCurrentUser($currentUser);

   
}