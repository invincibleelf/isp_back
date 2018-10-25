<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 24/10/18
 * Time: 11:46 AM
 */

namespace App\Repositories;


use App\Models\Merchant;
use Illuminate\Support\Facades\Log;

class MerchantRepositoryImpl implements MerchantRepository
{

    public function getMerchants()
    {
        $merchants = Merchant::with(array('services' => function ($q) {
            $q->where('status', true);
        }))->whereHas('services', function ($q) {
            $q->where('status', true);
        })->where('status', '=', true)->get();


        return $merchants;
    }

    public function getMerchantById($id)
    {
        $merchant = Merchant::with(['services' => function ($q) {
            $q->where('status', true);
        }])->whereHas('services', function ($q) {
            $q->where('status', true);
        })->where('status', '=', true)->find($id);

        return $merchant;
    }


}