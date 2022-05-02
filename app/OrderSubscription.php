<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderSubscription extends Model
{
    public function order()
    {
        return $this->belongsTo('App\Order','order_id','id');
    }
}
