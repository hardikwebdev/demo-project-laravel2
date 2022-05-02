<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellerEarning extends Model
{
    public $table  = 'seller_earning';

    public function order()
    {
    	return $this->belongsTo('App\Order','order_id','id');	
    }
    public function user() 
    {
        return $this->belongsTo('App\User','seller_id','id');
    }
    public function service_order(){
    	return $this->belongsTo('App\BoostedServicesOrder','order_id','id');	
    }
}
