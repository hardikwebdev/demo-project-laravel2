<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BillingInfo extends Model
{
    public $table  = 'billing_info';
    
    public function country()
    {
        return $this->belongsTo('App\Country','country_id','id');
    }
}
