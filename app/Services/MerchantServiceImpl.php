<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 24/10/18
 * Time: 4:47 PM
 */

namespace App\Services;


use App\Models\Merchant;
use App\Models\Service;
use App\Repositories\MerchantRepository;
use App\Utilities;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MerchantServiceImpl implements MerchantService
{
    private $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }



    public function createMerchantServices()
    {
        Log::info("Initiate merchants and services create ");
        $contents = $this->getMerchantsServicesFromBux();

        if (!array_key_exists('code', $contents) || !$contents->code) {
            return Utilities::getResponseMessage($contents->message, false, 500);
        }

        $merchants_bux = $contents->details;
        $m = [];

        try {
            DB::beginTransaction();
            foreach ($merchants_bux as $m_bux) {
                $ids = [];
                $merchant = $this->merchantRepository->getMerchantByBuxId($m_bux['id']);
                if ($merchant == null) {
                    $merchant = new Merchant();
                    $merchant = $this->updateMerchant($merchant, $m_bux);
                }

                foreach ($m_bux['services'] as $s_bux) {
                    $service = $this->merchantRepository->getServiceByBuxId($s_bux['id']);

                    if ($service == null) {
                        $service = new Service();
                        $service = $this->updateService($service, $s_bux);
                    }
                    array_push($ids, $service->id);

                }

                $merchant->services()->sync($ids);
                $merchant->load('services');
                array_push($m, $merchant);
            }

            //DB::commit();
            return Utilities::getResponseMessage("Merchants and services created successfully", true, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Utilities::getResponseMessage($e->getMessage(), false, 500);
        }
    }

    public function getMerchantsServicesFromBux()
    {
        Log::info("Get merchants  in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API " . Config::get('constants.bux_base_url') . Config::get('constants.bux_student')."1");

        //TODO Call to bux merchant api
        $buxResponse = $buxAPI->get(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . '1');
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;


    }

    public function updateMerchant($merchant, $m_bux)
    {
        //TODO save merchant params according to bux api

        $merchant->bux_id = $m_bux['id'];
        $merchant->alias = $m_bux['alias'];
        $merchant->full_name = $m_bux['name'];
        $merchant->status = $m_bux['status'];

        $merchant->save();
        return $merchant;
    }

    public function updateService($service, $s_bux)
    {

        //TODO save merchant params according to bux api
        $service->bux_id = $s_bux['id'];
        $service->name = $s_bux['name'];
        $service->save();

        return $service;


    }
}