<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $table = 'favourites';

    /*
     * Relationships
     * */
    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

    public function transaction(){
        return $this->belongsTo('App\Transaction','transaction_id');
    }
}
