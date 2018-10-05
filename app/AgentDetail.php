<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AgentDetail extends Model
{

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

    public function councilors(){
        return $this->hasMany('App\CouncilorDetail');
    }
}
