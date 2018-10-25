<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 24/10/18
 * Time: 11:46 AM
 */

namespace App\Repositories;


interface MerchantRepository
{

    public function getMerchants();

    public function getMerchantById($id);
}