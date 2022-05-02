<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartExtraCombo extends Model
{
    public $table  = 'cart_extra_combos';

    public function service_extra()
    {
        return $this->belongsTo('App\ServiceExtra','service_extra_id','id');
    }
}
