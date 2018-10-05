<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

    public function councilor(){
        return $this->belongsTo('App\CouncilorDetail','councilor_id');
    }
}
