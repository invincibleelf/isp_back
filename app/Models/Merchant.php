<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchants';

    public function services()
    {
        return $this->belongsToMany('App\Models\Service', 'merchant_service', 'merchant_id', 'service_id');
    }
}
