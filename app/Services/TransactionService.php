<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 1/11/18
 * Time: 4:24 PM
 */

namespace App\Services;


interface TransactionService
{

    public function createTransaction($transaction, $credentials, $student, $merchant);

    public function createTransactionAtBux($transaction);

    public function updateTransaction($transaction, $credentials, $payer, $paymentMethod);
}