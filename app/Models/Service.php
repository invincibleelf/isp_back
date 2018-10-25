<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    public function merchants(){
        return $this->belongsToMany('App\Models\Merchant','merchant_service','service_id','merchant_id');
    }
}
