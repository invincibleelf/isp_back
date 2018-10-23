<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayerDetail extends Model
{
    public function student(){
        return $this->belongsTo('App\StudentDetail','student_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }
}
