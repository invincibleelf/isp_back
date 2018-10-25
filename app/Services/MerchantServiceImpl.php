<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 24/10/18
 * Time: 4:47 PM
 */

namespace App\Services;


use App\Utilities;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MerchantServiceImpl implements MerchantService
{

    public function getMerchantsServicesFromBux()
    {
        Log::info("Get merchant  in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");

        //TODO Call to bux merchant api
        $buxResponse = $buxAPI->get(Config::get('constants.bux_base_url') . Config::get('constants.bux_list_students'));
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;


    }

}