<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderExtendRequest extends Model
{
    public function order(){
    	return $this->belongsTo('App\Order','order_id','id');
    }
}
