<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartExtra extends Model
{
    public $table  = 'cart_extra';

    public function service_extra()
    {
        return $this->belongsTo('App\ServiceExtra','service_extra_id','id');
    }
}
