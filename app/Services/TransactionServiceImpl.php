<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 1/11/18
 * Time: 4:24 PM
 */

namespace App\Services;

use App\Utilities;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TransactionServiceImpl implements TransactionService
{

    public function createTransaction($transaction, $credentials, $student,$merchant)
    {
        Log::info("Create Transaction for student $student->email");
        $transaction->amount = $credentials['totalAmount'];
        $transaction->service_ids = json_encode($credentials['services']);
        $transaction->transaction_sn = strtoupper(substr(md5($student->id), 0, 6) . time());
        $transaction->status = Config::get('enums.t_status.INITIATED');

        Log::info("Associate merchant with transaction");
        $transaction->merchant()->associate($merchant);

        Log::info("Associate student with transaction");
        $transaction->student()->associate($student->studentDetails);

        Log::info("Save Transaction");
        $transaction->save();


        return $transaction;
    }

    public function createTransactionAtBux($transaction)
    {
        Log::info("Create transaction $transaction->transaction_sn in BUX API " . Config::get('constants.bux_base_url'));
        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");

        //TODO call to bux api to create transactions
        return null;
        //$buxResponse = $buxAPI->post(Config::get('constants.bux_base_url') . Config::get('constants.bux_student'), ['json' => Utilities::getJsonRequestForUpdateStudent($transaction)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        //$contents = json_decode($buxResponse->getBody());


    }


}