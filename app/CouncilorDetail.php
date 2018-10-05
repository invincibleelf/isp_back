<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CouncilorDetail extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo('App\AgentDetail','agent_id');
    }

    public function students(){
        return $this->hasMany('App\StudentDetail');
    }
}
