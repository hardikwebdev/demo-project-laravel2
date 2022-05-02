<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AffiliateEarning extends Model
{
    public $table  = 'affiliate_earning';

    public function user() 
    {
        return $this->belongsTo('App\User','seller_id','id');
    }
    public function order()
    {
    	return $this->belongsTo('App\Order','order_id','id');	
    }
}
