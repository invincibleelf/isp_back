<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "transaction";
    public function student()
    {
        return $this->belongsTo('App\StudentDetail','student_id');
    }

    public function payer(){
        return $this->belongsTo('App\PayerDetail','payer_id');
    }

    public function merchant(){
        return $this->belongsTo('App\Models\Merchant', 'merchant_id');
    }

   
}
